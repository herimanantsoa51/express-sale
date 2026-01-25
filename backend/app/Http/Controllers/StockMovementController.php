<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\ProductVariantLocation;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\StockBatch;
use Illuminate\Support\Facades\Log;
class StockMovementController extends Controller
{
    /**
     * Obtenir l'historique des mouvements
     * GET /api/stock-movements
     */
    public function index(Request $request)
    {
        $query = StockMovement::with([
            'variant.product.category',
            'variant.attributeValues.attributeValue.attributeType',
            'fromLocation',
            'toLocation',
            'sale.reservation',
            'sale.credit',
            'stockReceipt',
            'performedBy'
        ])->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('variant_id')) {
            $query->where('variant_id', $request->variant_id);
        }

        if ($request->has('location_id')) {
            $query->byLocation($request->location_id);
        }

        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->has('days')) {
            $query->recent($request->days);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('variant.product', function ($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('variant', function ($q2) use ($search) {
                    $q2->where('sku', 'ilike', "%{$search}%");
                })
                ->orWhereHas('fromLocation', function ($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('toLocation', function ($q2) use ($search) {
                    $q2->where('name', 'ilike', "%{$search}%");
                });
            });
        }

        $movements = $query->paginate($request->per_page ?? 50);

        return response()->json($movements);
    }

    /**
     * Obtenir les mouvements groupés par batch
     * GET /api/stock-movements/grouped
     */
    public function grouped(Request $request)
    {
        $days = $request->get('days', 30);
        $perPage = $request->get('per_page', 20);

        $groupedQuery = StockMovement::select(
            'batch_id',
            'from_location_id',
            'to_location_id',
            'movement_type',
            'reason',
            'performed_by',
            DB::raw('MIN(created_at) as created_at'),
            DB::raw('COUNT(*) as items_count'),
            DB::raw('SUM(quantity) as total_quantity')
        )
        ->with(['fromLocation', 'toLocation', 'performedBy'])
        ->whereNotNull('batch_id')
        ->where('created_at', '>=', now()->subDays($days))
        ->groupBy('batch_id', 'from_location_id', 'to_location_id', 'movement_type', 'reason', 'performed_by')
        ->orderBy('created_at', 'desc');

        if ($request->has('movement_type')) {
            $groupedQuery->where('movement_type', $request->movement_type);
        }

        $grouped = $groupedQuery->paginate($perPage);

        return response()->json($grouped);
    }

    /**
     * Obtenir les détails d'un groupe de mouvements
     * GET /api/stock-movements/batch/{batchId}
     */
    public function batchDetails($batchId)
    {
        $movements = StockMovement::with([
            'variant.product.category',
            'variant.attributeValues.attributeValue.attributeType',
            'fromLocation',
            'toLocation',
            'performedBy'
        ])
        ->where('batch_id', $batchId)
        ->orderBy('created_at', 'desc')
        ->get();

        if ($movements->isEmpty()) {
            return response()->json(['message' => 'Batch non trouvé'], 404);
        }

        $firstMovement = $movements->first();
        
        $summary = [
            'batch_id' => $batchId,
            'movement_type' => $firstMovement->movement_type,
            'from_location' => $firstMovement->fromLocation,
            'to_location' => $firstMovement->toLocation,
            'performed_by' => $firstMovement->performedBy,
            'reason' => $firstMovement->reason,
            'notes' => $firstMovement->notes,
            'created_at' => $firstMovement->created_at,
            'items_count' => $movements->count(),
            'total_quantity' => $movements->sum('quantity'),
            'items' => $movements
        ];

        return response()->json($summary);
    }

    /**
     * Obtenir les détails d'un mouvement
     * GET /api/stock-movements/{id}
     */
    public function show($id)
    {
        $movement = StockMovement::with([
            'variant.product.category',
            'variant.attributeValues.attributeValue.attributeType',
            'fromLocation',
            'toLocation',
            'sale',
            'stockReceipt',
            'performedBy'
        ])->findOrFail($id);

        return response()->json($movement);
    }

    /**
     * Transférer du stock entre deux locations (un seul item)
     * POST /api/stock-movements/transfer
     */
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'from_location_id' => 'required|integer|exists:locations,id',
            'to_location_id' => 'required|integer|exists:locations,id|different:from_location_id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Vérifier la disponibilité
            $fromVariantLocation = ProductVariantLocation::where('variant_id', $data['variant_id'])
                ->where('location_id', $data['from_location_id'])
                ->firstOrFail();

            if ($fromVariantLocation->quantity < $data['quantity']) {
                return response()->json([
                    'message' => 'Quantité insuffisante dans la location source',
                    'available' => $fromVariantLocation->quantity,
                    'requested' => $data['quantity']
                ], 422);
            }

            // Retirer de la location source
            $fromVariantLocation->decrement('quantity', $data['quantity']);

            // Ajouter à la location destination
            $toVariantLocation = ProductVariantLocation::firstOrCreate(
                [
                    'variant_id' => $data['variant_id'],
                    'location_id' => $data['to_location_id']
                ],
                ['quantity' => 0]
            );
            $toVariantLocation->increment('quantity', $data['quantity']);

            // Créer le mouvement
            $movement = StockMovement::createTransfer(
                $data['variant_id'],
                $data['from_location_id'],
                $data['to_location_id'],
                $data['quantity'],
                Auth::id(),
                $data['reason'] ?? null,
                $data['notes'] ?? null
            );

            DB::commit();

            $movement->load([
                'variant.product',
                'fromLocation',
                'toLocation',
                'performedBy'
            ]);

            return response()->json($movement, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfert multiple (plusieurs variantes en même temps)
     * POST /api/stock-movements/bulk-transfer
     */
    public function bulkTransfer(Request $request)
    {
        $data = $request->validate([
            'from_location_id' => 'required|integer|exists:locations,id',
            'to_location_id' => 'required|integer|exists:locations,id|different:from_location_id',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $batchId = 'TRF-' . now()->format('Ymd-His') . '-' . Str::random(6);
            $movements = [];
            $errors = [];

            foreach ($data['items'] as $index => $item) {
                $fromVariantLocation = ProductVariantLocation::where('variant_id', $item['variant_id'])
                    ->where('location_id', $data['from_location_id'])
                    ->first();

                if (!$fromVariantLocation || $fromVariantLocation->quantity < $item['quantity']) {
                    $variant = ProductVariant::with('product')->find($item['variant_id']);
                    $errors[] = [
                        'index' => $index,
                        'variant_id' => $item['variant_id'],
                        'product_name' => $variant?->product?->name ?? 'Inconnu',
                        'sku' => $variant?->sku ?? 'N/A',
                        'available' => $fromVariantLocation?->quantity ?? 0,
                        'requested' => $item['quantity'],
                        'message' => 'Quantité insuffisante'
                    ];
                    continue;
                }

                $fromVariantLocation->decrement('quantity', $item['quantity']);

                $toVariantLocation = ProductVariantLocation::firstOrCreate(
                    [
                        'variant_id' => $item['variant_id'],
                        'location_id' => $data['to_location_id']
                    ],
                    ['quantity' => 0]
                );
                $toVariantLocation->increment('quantity', $item['quantity']);

                $movement = StockMovement::create([
                    'variant_id' => $item['variant_id'],
                    'from_location_id' => $data['from_location_id'],
                    'to_location_id' => $data['to_location_id'],
                    'quantity' => $item['quantity'],
                    'movement_type' => StockMovement::TYPE_TRANSFER,
                    'performed_by' => Auth::id(),
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'batch_id' => $batchId
                ]);

                $movements[] = $movement;
            }

            if (!empty($errors) && empty($movements)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Aucun transfert effectué - erreurs sur tous les items',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            $movementIds = collect($movements)->pluck('id');
            $loadedMovements = StockMovement::with([
                'variant.product',
                'variant.attributeValues.attributeValue.attributeType',
                'fromLocation',
                'toLocation',
                'performedBy'
            ])->whereIn('id', $movementIds)->get();

            return response()->json([
                'message' => count($movements) . ' transfert(s) effectué(s)',
                'batch_id' => $batchId,
                'movements' => $loadedMovements,
                'total_items' => count($movements),
                'total_quantity' => collect($movements)->sum('quantity'),
                'errors' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors du transfert multiple',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajuster le stock (augmentation ou diminution)
     * POST /api/stock-movements/adjustment
     */
    public function adjustment(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'location_id' => 'required|integer|exists:locations,id',
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $isIncrease = $data['quantity'] > 0;
            $quantity = abs($data['quantity']);

            $variantLocation = ProductVariantLocation::firstOrCreate(
                [
                    'variant_id' => $data['variant_id'],
                    'location_id' => $data['location_id']
                ],
                ['quantity' => 0]
            );

            if (!$isIncrease && $variantLocation->quantity < $quantity) {
                return response()->json([
                    'message' => 'Quantité insuffisante pour l\'ajustement négatif',
                    'available' => $variantLocation->quantity,
                    'requested' => $quantity
                ], 422);
            }

            // Appliquer l'ajustement
            if ($isIncrease) {
                $variantLocation->increment('quantity', $quantity);
            } else {
                $variantLocation->decrement('quantity', $quantity);
            }

            // Recalculer le stock total
            $variantLocation->variant->recalculateTotalStock();

            // Créer le mouvement
            $movement = StockMovement::createAdjustment(
                $data['variant_id'],
                $data['location_id'],
                $quantity,
                Auth::id(),
                $data['reason'],
                $isIncrease,
                $data['notes'] ?? null
            );

            DB::commit();

            $movement->load(['variant.product', 'fromLocation', 'toLocation', 'performedBy']);

            return response()->json($movement, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'ajustement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajustement multiple
     * POST /api/stock-movements/bulk-adjustment
     */
    public function bulkAdjustment(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|not_in:0'
        ]);

        try {
            DB::beginTransaction();

            $batchId = 'ADJ-' . now()->format('Ymd-His') . '-' . Str::random(6);
            $movements = [];
            $errors = [];

            foreach ($data['items'] as $index => $item) {
                $isIncrease = $item['quantity'] > 0;
                $quantity = abs($item['quantity']);

                $variantLocation = ProductVariantLocation::firstOrCreate(
                    [
                        'variant_id' => $item['variant_id'],
                        'location_id' => $data['location_id']
                    ],
                    ['quantity' => 0]
                );

                if (!$isIncrease && $variantLocation->quantity < $quantity) {
                    $variant = ProductVariant::with('product')->find($item['variant_id']);
                    $errors[] = [
                        'index' => $index,
                        'variant_id' => $item['variant_id'],
                        'product_name' => $variant?->product?->name ?? 'Inconnu',
                        'available' => $variantLocation->quantity,
                        'requested' => $quantity,
                        'message' => 'Quantité insuffisante'
                    ];
                    continue;
                }

                if ($isIncrease) {
                    $variantLocation->increment('quantity', $quantity);
                } else {
                    $variantLocation->decrement('quantity', $quantity);
                }

                $movement = StockMovement::create([
                    'variant_id' => $item['variant_id'],
                    'from_location_id' => $isIncrease ? null : $data['location_id'],
                    'to_location_id' => $isIncrease ? $data['location_id'] : null,
                    'quantity' => $quantity,
                    'movement_type' => StockMovement::TYPE_ADJUSTMENT,
                    'performed_by' => Auth::id(),
                    'reason' => $data['reason'],
                    'notes' => $data['notes'] ?? null,
                    'batch_id' => $batchId
                ]);

                $movements[] = $movement;
            }

            if (!empty($errors) && empty($movements)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Aucun ajustement effectué',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'message' => count($movements) . ' ajustement(s) effectué(s)',
                'batch_id' => $batchId,
                'total_items' => count($movements),
                'errors' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'ajustement multiple',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique d'une variante
     * GET /api/variants/{variantId}/movements
     */
    public function variantHistory($variantId)
    {
        $movements = StockMovement::with([
            'fromLocation',
            'toLocation',
            'sale',
            'stockReceipt',
            'performedBy'
        ])
        ->where('variant_id', $variantId)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($movements);
    }

    /**
     * Obtenir l'historique d'une location
     * GET /api/locations/{locationId}/movements
     */
    public function locationHistory($locationId)
    {
        $movements = StockMovement::with([
            'variant.product',
            'fromLocation',
            'toLocation',
            'sale',
            'stockReceipt',
            'performedBy'
        ])
        ->byLocation($locationId)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json($movements);
    }

    /**
     * Obtenir les statistiques des mouvements
     * GET /api/stock-movements/statistics
     */
    public function statistics(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $byType = StockMovement::where('created_at', '>=', $startDate)
            ->select('movement_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('movement_type')
            ->get()
            ->keyBy('movement_type');

        $stats = [
            'period' => [
                'days' => $days,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ],
            'total_movements' => StockMovement::where('created_at', '>=', $startDate)->count(),
            'total_transfers' => $byType->get('transfer')?->count ?? 0,
            'total_incoming' => ($byType->get('receipt')?->total_quantity ?? 0) + ($byType->get('return')?->total_quantity ?? 0),
            'total_outgoing' => ($byType->get('sale')?->total_quantity ?? 0),
            'by_type' => $byType
        ];

        return response()->json($stats);
    }

    // app/Http/Controllers/StockMovementController.php

    /**
     * Déclarer une perte de stock (casse, vol, péremption...)
     * POST /api/stock-movements/loss
     */
    public function declareLoss(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'location_id' => 'required|integer|exists:locations,id',
            'quantity' => 'required|integer|min:1',
            'loss_type' => 'required|string|in:breakage,theft,expiry,damage,inventory_shortage,other',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $variantLocation = ProductVariantLocation::where([
                'variant_id' => $data['variant_id'],
                'location_id' => $data['location_id']
            ])->firstOrFail();

            // Vérifier stock disponible
            if ($variantLocation->quantity < $data['quantity']) {
                return response()->json([
                    'message' => 'Quantité insuffisante pour la déclaration de perte',
                    'available' => $variantLocation->quantity,
                    'requested' => $data['quantity']
                ], 422);
            }

            // Consommer les batches en FIFO
            $remainingToLose = $data['quantity'];
            $batches = StockBatch::where('variant_id', $data['variant_id'])
                ->available()
                ->fifoOrder()
                ->lockForUpdate()
                ->get();

            $affectedBatches = [];
            $totalCostImpact = 0;

            foreach ($batches as $batch) {
                if ($remainingToLose <= 0) break;

                $quantityFromBatch = min($batch->remaining_quantity, $remainingToLose);
                
                // Consommer le batch
                $batch->decrement('remaining_quantity', $quantityFromBatch);
                
                // Calculer l'impact financier
                $batchCostImpact = $quantityFromBatch * $batch->total_unit_cost;
                $totalCostImpact += $batchCostImpact;

                $affectedBatches[] = [
                    'batch_number' => $batch->batch_number,
                    'quantity_lost' => $quantityFromBatch,
                    'unit_cost' => $batch->total_unit_cost,
                    'cost_impact' => $batchCostImpact,
                ];

                $remainingToLose -= $quantityFromBatch;
            }

            if ($remainingToLose > 0) {
                throw new \Exception("Stock insuffisant dans les batches disponibles");
            }

            // Mettre à jour l'emplacement
            $variantLocation->decrement('quantity', $data['quantity']);
            $variantLocation->variant->recalculateTotalStock();

            // Créer le mouvement de perte
            $movement = StockMovement::create([
                'variant_id' => $data['variant_id'],
                'from_location_id' => $data['location_id'],
                'to_location_id' => null,
                'quantity' => $data['quantity'],
                'movement_type' => 'loss',
                'loss_type' => $data['loss_type'],
                'performed_by' => Auth::id(),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Perte déclarée avec succès',
                'movement' => $movement->load(['variant.product', 'fromLocation', 'performedBy']),
                'affected_batches' => $affectedBatches,
                'total_cost_impact' => $totalCostImpact
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la déclaration de perte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réconcilier l'inventaire après comptage physique
     * POST /api/stock-movements/reconcile-inventory
     */
    public function reconcileInventory(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'inventory_date' => 'required|date|before_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.system_quantity' => 'required|integer|min:0',
            'items.*.physical_count' => 'required|integer|min:0',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $results = [
                'confirmed' => [],
                'surplus' => [],
                'shortages' => [],
                'total_cost_impact' => 0
            ];

            foreach ($data['items'] as $item) {
                $difference = $item['physical_count'] - $item['system_quantity'];

                // Cas 1 : Stock conforme
                if ($difference === 0) {
                    $results['confirmed'][] = [
                        'variant_id' => $item['variant_id'],
                        'quantity' => $item['system_quantity'],
                        'status' => 'confirmed'
                    ];
                    continue;
                }

                // Cas 2 : Stock excédentaire - ALERTE
                if ($difference > 0) {
                    Log::warning('Stock surplus detected during inventory reconciliation', [
                        'variant_id' => $item['variant_id'],
                        'location_id' => $data['location_id'],
                        'expected' => $item['system_quantity'],
                        'found' => $item['physical_count'],
                        'surplus' => $difference,
                        'inventory_date' => $data['inventory_date']
                    ]);

                    $results['surplus'][] = [
                        'variant_id' => $item['variant_id'],
                        'expected' => $item['system_quantity'],
                        'found' => $item['physical_count'],
                        'surplus' => $difference,
                        'action' => 'manual_verification_required'
                    ];
                    
                    // TODO: Créer une notification/alerte pour investigation manuelle
                    continue;
                }

                // Cas 3 : Stock manquant - Déclarer perte FIFO
                if ($difference < 0) {
                    $quantity = abs($difference);

                    // Consommer les batches en FIFO
                    $batches = StockBatch::where('variant_id', $item['variant_id'])
                        ->available()
                        ->fifoOrder()
                        ->lockForUpdate()
                        ->get();

                    $remainingToLose = $quantity;
                    $affectedBatches = [];
                    $costImpact = 0;

                    foreach ($batches as $batch) {
                        if ($remainingToLose <= 0) break;

                        $qtyFromBatch = min($batch->remaining_quantity, $remainingToLose);
                        $batch->decrement('remaining_quantity', $qtyFromBatch);
                        
                        $batchCost = $qtyFromBatch * $batch->total_unit_cost;
                        $costImpact += $batchCost;

                        $affectedBatches[] = [
                            'batch_number' => $batch->batch_number,
                            'quantity' => $qtyFromBatch,
                            'cost' => $batchCost
                        ];

                        $remainingToLose -= $qtyFromBatch;
                    }

                    if ($remainingToLose > 0) {
                        throw new \Exception(
                            "Stock insuffisant dans les batches pour variant {$item['variant_id']}"
                        );
                    }

                    // Créer le mouvement de perte
                    $movement = StockMovement::create([
                        'variant_id' => $item['variant_id'],
                        'from_location_id' => $data['location_id'],
                        'to_location_id' => null,
                        'quantity' => $quantity,
                        'movement_type' => 'loss',
                        'loss_type' => 'inventory_shortage',
                        'performed_by' => Auth::id(),
                        'reason' => $item['notes'] ?? 'Écart constaté lors de l\'inventaire physique',
                        'notes' => $data['notes']
                    ]);

                    // Mettre à jour l'emplacement
                    $variantLocation = ProductVariantLocation::where([
                        'variant_id' => $item['variant_id'],
                        'location_id' => $data['location_id']
                    ])->firstOrFail();

                    $variantLocation->decrement('quantity', $quantity);
                    $variantLocation->variant->recalculateTotalStock();

                    $results['shortages'][] = [
                        'variant_id' => $item['variant_id'],
                        'expected' => $item['system_quantity'],
                        'found' => $item['physical_count'],
                        'shortage' => $quantity,
                        'cost_impact' => $costImpact,
                        'affected_batches' => $affectedBatches,
                        'movement_id' => $movement->id
                    ];

                    $results['total_cost_impact'] += $costImpact;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Inventaire réconcilié avec succès',
                'inventory_date' => $data['inventory_date'],
                'location_id' => $data['location_id'],
                'results' => $results
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la réconciliation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}