<?php

namespace App\Http\Controllers;

use App\Models\StockReceipt;
use App\Models\AccountTransaction;
use App\Http\Requests\StoreStockReceiptRequest;
use App\Http\Requests\UpdateStockReceiptRequest;
use App\Http\Requests\MarkAsShippedRequest;
use App\Http\Requests\MarkAsArrivedRequest;
use App\Http\Requests\RateStockReceiptItemRequest;
use App\Http\Resources\StockReceiptResource;
use App\Http\Resources\StockReceiptCollection;
use App\Models\ExpenseCategory;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockReceiptController extends Controller
{
    /**
     * Liste des réceptions de stock
     * GET /api/stock-receipts
     */
    public function index(Request $request): StockReceiptCollection
    {
        $query = StockReceipt::with([
            'supplier:id,name,logo_url,reliability_score', // Spécifiez les champs à charger
            'freightForwarder:id,name,logo_url,service_score',
            'items.variant.product',
            'creator:id,name'
        ]);
        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par plage de dates de création
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Filtre par plage de dates de livraison prévue
        if ($request->filled('delivery_from')) {
            $query->whereDate('expected_delivery_date', '>=', $request->delivery_from);
        }
        if ($request->filled('delivery_to')) {
            $query->whereDate('expected_delivery_date', '<=', $request->delivery_to);
        }

        // Filtre par retard
        if ($request->has('delayed') && $request->delayed == 'true') {
            $query->whereNotNull('expected_delivery_date')
                ->where('expected_delivery_date', '<', now())
                ->whereNotIn('status', ['arrived', 'validated', 'cancelled']);
        }

        // Recherche textuelle
        // Dans votre contrôleur, modifier la recherche pour inclure les relations
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('supplier', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('freightForwarder', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['created_at', 'expected_delivery_date', 'total_cost_ariary', 'status', 'receipt_number'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->get('per_page', 20);
        $receipts = $query->paginate($perPage);

        return new StockReceiptCollection($receipts);
    }

    /**
     * Créer une nouvelle réception
     * POST /api/stock-receipts
     */
    public function store(StoreStockReceiptRequest $request): JsonResponse
    {
        try {
            $receipt = StockReceipt::createWithItems($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Réception de stock créée avec succès',
                'data' => new StockReceiptResource($receipt)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la réception',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une réception spécifique
     * GET /api/stock-receipts/{id}
     */
    public function show(StockReceipt $stockReceipt): JsonResponse
    {
        $stockReceipt->load([
            'supplier',
            'freightForwarder',
            'items.variant.product.category',
            'items.variant.locations.location',
            'items.ratings.attributeType',
            'items.ratings.ratedBy',
            'creator',
            'transactions.transactionType'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new StockReceiptResource($stockReceipt)
        ]);
    }

    /**
     * Mettre à jour une réception
     * PUT /api/stock-receipts/{id}
     */
    public function update(UpdateStockReceiptRequest $request, StockReceipt $stockReceipt): JsonResponse
    {
        if (in_array($stockReceipt->status, ['validated', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de modifier une réception validée ou annulée'
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $stockReceipt) {
                $stockReceipt->update($request->only([
                    'supplier_id',
                    'freight_forwarder_id',
                    'expected_delivery_date',
                    'notes'
                ]));

                if ($request->has('items')) {
                    $stockReceipt->items()->delete();

                    $totalCost = 0;
                    foreach ($request->items as $itemData) {
                        $item = $stockReceipt->items()->create([
                            'variant_id' => $itemData['variant_id'],
                            'quantity_ordered' => $itemData['quantity_ordered'],
                            'quantity_received' => 0,
                            'unit_cost_ariary' => $itemData['unit_cost_ariary'],
                            'notes' => $itemData['notes'] ?? null
                        ]);

                        $totalCost += $item->quantity_ordered * $item->unit_cost_ariary;
                    }

                    $stockReceipt->update(['total_cost_ariary' => $totalCost]);
                }
            });

            $stockReceipt->load(['supplier', 'freightForwarder', 'items.variant.product']);

            return response()->json([
                'status' => 'success',
                'message' => 'Réception mise à jour avec succès',
                'data' => new StockReceiptResource($stockReceipt)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer comme envoyé
     * POST /api/stock-receipts/{id}/mark-shipped
     */
    public function markAsShipped(MarkAsShippedRequest $request, StockReceipt $stockReceipt): JsonResponse
    {
        if ($stockReceipt->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seules les commandes en attente peuvent être marquées comme envoyées'
            ], 422);
        }

        try {
            $stockReceipt->markAsShipped($request->notes);

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme envoyée',
                'data' => new StockReceiptResource($stockReceipt)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer comme en transit
     * POST /api/stock-receipts/{id}/mark-in-transit
     */
    public function markAsInTransit(Request $request, StockReceipt $stockReceipt): JsonResponse
    {
        if (!in_array($stockReceipt->status, ['pending', 'sent'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Statut invalide pour cette action'
            ], 422);
        }

        try {
            $stockReceipt->markAsInTransit($request->notes);

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme en transit',
                'data' => new StockReceiptResource($stockReceipt)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer comme arrivé et assigner les emplacements
     * POST /api/stock-receipts/{id}/mark-arrived
     */
    public function markAsArrived(MarkAsArrivedRequest $request, StockReceipt $stockReceipt): JsonResponse
    {
        if ($stockReceipt->status === 'validated') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cette réception est déjà validée'
            ], 422);
        }

        try {
            $stockReceipt->markAsArrived($request->items);

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme arrivée. Les stocks ont été mis à jour dans les emplacements.',
                'data' => new StockReceiptResource($stockReceipt->load([
                    'supplier',
                    'freightForwarder',
                    'items.variant.product',
                    'items.variant.locations.location'
                ]))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider la réception et mettre à jour les scores
     * POST /api/stock-receipts/{id}/validate
     */
    public function validate(StockReceipt $stockReceipt): JsonResponse
    {
        try {
            $stockReceipt->validate();

            return response()->json([
                'status' => 'success',
                'message' => 'Réception validée avec succès. Les scores ont été mis à jour.',
                'data' => new StockReceiptResource($stockReceipt->load([
                    'supplier',
                    'freightForwarder',
                    'items.variant.product'
                ]))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Annuler une réception
     * POST /api/stock-receipts/{id}/cancel
     */
    public function cancel(StockReceipt $stockReceipt): JsonResponse
    {
        try {
            $stockReceipt->cancel();

            return response()->json([
                'status' => 'success',
                'message' => 'Réception annulée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Évaluer la qualité d'un item
     * POST /api/stock-receipts/{id}/items/{itemId}/rate
     */
    public function rateItem(RateStockReceiptItemRequest $request, StockReceipt $stockReceipt, int $itemId): JsonResponse
    {
        $item = $stockReceipt->items()->findOrFail($itemId);

        try {
            DB::transaction(function () use ($request, $item) {
                foreach ($request->ratings as $ratingData) {
                    $item->addRating($ratingData);
                }
            });

            $item->load(['ratings.attributeType', 'ratings.ratedBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Évaluation enregistrée avec succès',
                'data' => [
                    'item' => $item,
                    'quality_summary' => $item->getQualitySummary(),
                    'conformity' => $item->getAttributeConformityRate()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'évaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un paiement pour cette réception
     * POST /api/stock-receipts/{id}/payment
     */
    public function recordPayment(Request $request, StockReceipt $stockReceipt): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:supplier,freight',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255'
        ]);

        try {
            $transaction = DB::transaction(function () use ($request, $stockReceipt) {
                $data = [
                    'account_id' => $request->account_id,
                    'transaction_type_id' => TransactionType::where('code', 'EXPENSE')->first()->id,
                    'amount' => $request->amount,
                    'description' => $request->payment_type === 'supplier' 
                        ? "Paiement fournisseur - {$stockReceipt->receipt_number}"
                        : "Paiement transitaire - {$stockReceipt->receipt_number}",
                    'notes' => $request->notes,
                    'reference_number' => $request->reference_number,
                    'stock_receipt_id' => $stockReceipt->id,

                ];

                if ($request->payment_type === 'supplier') {
                    $data['supplier_id'] = $stockReceipt->supplier_id;
                    $data['expense_category_id'] = ExpenseCategory::where('name', 'Approvisionnement')->first()->id;
                   
                } else {
                    $data['freight_forwarder_id'] = $stockReceipt->freight_forwarder_id;
                    $data['expense_category_id'] =  ExpenseCategory::where('name', 'Transport & Transit')->first()->id;
                }

                return AccountTransaction::createTransaction($data);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Paiement enregistré avec succès',
                'data' => $transaction
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques globales des réceptions
     * GET /api/stock-receipts/global-statistics
     */
    public function globalStatistics(): JsonResponse
    {
        $stats = [
            'total' => StockReceipt::count(),
            'by_status' => [
                'pending' => StockReceipt::where('status', 'pending')->count(),
                'sent' => StockReceipt::where('status', 'sent')->count(),
                'in_transit' => StockReceipt::where('status', 'in_transit')->count(),
                'arrived' => StockReceipt::where('status', 'arrived')->count(),
                'validated' => StockReceipt::where('status', 'validated')->count(),
                'cancelled' => StockReceipt::where('status', 'cancelled')->count(),
            ],
            'not_arrived' => StockReceipt::whereNotIn('status', ['arrived', 'validated', 'cancelled'])->count(),
            'delayed' => StockReceipt::where('expected_delivery_date', '<', now())
                ->whereNotIn('status', ['arrived', 'validated', 'cancelled'])
                ->count(),
            'this_month' => StockReceipt::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'total_value_pending' => StockReceipt::whereNotIn('status', ['validated', 'cancelled'])
                ->sum('total_cost_ariary'),
            'total_value_validated' => StockReceipt::where('status', 'validated')
                ->sum('total_cost_ariary'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Obtenir les statistiques d'une réception
     * GET /api/stock-receipts/{id}/statistics
     */
    public function statistics(StockReceipt $stockReceipt): JsonResponse
    {
        $items = $stockReceipt->items()->with(['ratings', 'variant.product'])->get();
        
        $totalOrdered = $items->sum('quantity_ordered');
        $totalReceived = $items->sum('quantity_received');
        $totalCost = $items->sum('total_cost');
        
        $qualityRatings = [];
        $conformityRates = [];
        
        foreach ($items as $item) {
            $qualityRatings[] = $item->getAverageQualityRating();
            $conformityRates[] = $item->getAttributeConformityRate()['rate'];
        }
        
        $averageQuality = count($qualityRatings) > 0 ? array_sum($qualityRatings) / count($qualityRatings) : 0;
        $averageConformity = count($conformityRates) > 0 ? array_sum($conformityRates) / count($conformityRates) : 0;
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'receipt_info' => [
                    'receipt_number' => $stockReceipt->receipt_number,
                    'status' => $stockReceipt->status,
                    'delivery_status' => $stockReceipt->delivery_status,
                    'expected_delivery_date' => $stockReceipt->expected_delivery_date,
                    'actual_delivery_date' => $stockReceipt->actual_delivery_date,
                    'is_delayed' => $stockReceipt->isDelayed(),
                    'days_delayed' => $stockReceipt->getDaysDelayed(),
                    'supplier' => $stockReceipt->supplier->name,
                    'freight_forwarder' => $stockReceipt->freightForwarder?->name
                ],
                'quantities' => [
                    'total_ordered' => $totalOrdered,
                    'total_received' => $totalReceived,
                    'fulfillment_rate' => $totalOrdered > 0 ? ($totalReceived / $totalOrdered) * 100 : 0,
                    'items_count' => $items->count()
                ],
                'costs' => [
                    'total_cost_ariary' => $totalCost,
                    'average_unit_cost' => $totalReceived > 0 ? $totalCost / $totalReceived : 0
                ],
                'quality' => [
                    'average_quality_rating' => round($averageQuality, 2),
                    'average_conformity_rate' => round($averageConformity, 2),
                    'overall_score' => round(($averageQuality + ($averageConformity / 10)) / 2, 2)
                ],
                'payments' => [
                    'total_paid' => $stockReceipt->transactions()->sum('amount'),
                    'remaining' => $totalCost - $stockReceipt->transactions()->sum('amount'),
                    'transactions_count' => $stockReceipt->transactions()->count()
                ]
            ]
        ]);
    }
}