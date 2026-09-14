<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Requests\AddExpenseRequest;
use App\Http\Requests\ApplyCostAllocationRequest;
use App\Http\Requests\GetCostRecommendationsRequest;
use App\Http\Requests\MarkAsArrivedRequest;
use App\Http\Requests\MarkAsShippedRequest;
use App\Http\Requests\MarkAsValidateRequest;
use App\Http\Requests\RateStockReceiptRequest;
use App\Http\Requests\StoreStockReceiptRequest;
use App\Http\Requests\UpdateStockReceiptRequest;
use App\Http\Resources\ReceiptExpenseResource;
use App\Http\Resources\StockReceiptCollection;
use App\Http\Resources\StockReceiptResource;
use App\Models\AccountTransaction;
use App\Models\ExpenseCategory;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\TransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'creator:id,name',
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
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('freightForwarder', function ($q) use ($search) {
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
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_CREATED,
                'a créé un réapprovisionnement',
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $receipt->id,
                    'metadata' => $receipt,

                ],
                "reapprovisionnements/{$receipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception de stock créée avec succès',
                'data' => new StockReceiptResource($receipt),
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_CREATED,
                'Erreur de création du réapprovisionnement',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la réception',
                'error' => $e->getMessage(),
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
            'transactions.transactionType',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => new StockReceiptResource($stockReceipt),
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
                'message' => 'Impossible de modifier une réception validée ou annulée',
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $stockReceipt) {
                $stockReceipt->update($request->only([
                    'supplier_id',
                    'freight_forwarder_id',
                    'expected_delivery_date',
                    'notes',
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
                            'notes' => $itemData['notes'] ?? null,
                        ]);

                        $totalCost += $item->quantity_ordered * $item->unit_cost_ariary;
                    }

                    $stockReceipt->update(['total_cost_ariary' => $totalCost]);
                }
            });

            $stockReceipt->load(['supplier', 'freightForwarder', 'items.variant.product']);
            ActivityLogger::success(ActivityAction::STOCK_RECEIPT_UPDATED,
                " a mis a jour le réapprovisionnement {$stockReceipt->receipt_number}",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception mise à jour avec succès',
                'data' => new StockReceiptResource($stockReceipt),
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_UPDATED,
                'mise à jour non réussi',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
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
                'message' => 'Seules les commandes en attente peuvent être marquées comme envoyées',
            ], 422);
        }

        try {
            $stockReceipt->markAsShipped($request->notes);
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_SHIPPED,
                "a marqué la réapprovisionnement {$stockReceipt->receipt_number} envoyé",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme envoyée',
                'data' => new StockReceiptResource($stockReceipt),
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_SHIPPED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer comme en transit
     * POST /api/stock-receipts/{id}/mark-in-transit
     */
    public function markAsInTransit(Request $request, StockReceipt $stockReceipt): JsonResponse
    {
        if (! in_array($stockReceipt->status, ['pending', 'sent'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Statut invalide pour cette action',
            ], 422);
        }

        try {
            $stockReceipt->markAsInTransit($request->notes);
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_IN_TRANSIT,
                "a marqué la réapprovisionnement {$stockReceipt->receipt_number} en transit",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme en transit',
                'data' => new StockReceiptResource($stockReceipt),
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_IN_TRANSIT,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
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
                'message' => 'Cette réception est déjà validée',
            ], 422);
        }

        try {
            $stockReceipt->markAsArrived($request->items);
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_ARRIVED,
                "a marqué la réapprovisionnement {$stockReceipt->receipt_number} en arrivé",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme arrivée. Les stocks ont été mis à jour dans les emplacements.',
                // 'data' => new StockReceiptResource($stockReceipt->load([
                //     'supplier',
                //     'freightForwarder',
                //     'items.variant.product',
                //     'items.variant.locations.location'
                // ]))
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_ARRIVED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valider la réception et mettre à jour les scores
     * POST /api/stock-receipts/{id}/validate
     */
    public function validateReceipt(MarkAsValidateRequest $request, StockReceipt $stockReceipt): JsonResponse
    {
        try {
            $stockReceipt->validate($request->items);
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_VALIDATED,
                "a marqué la réapprovisionnement {$stockReceipt->receipt_number} en validé",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception validée avec succès. Les scores ont été mis à jour.',
                'data' => new StockReceiptResource($stockReceipt->load([
                    'supplier',
                    'freightForwarder',
                    'items.variant.product',
                ])),
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_VALIDATED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
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
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_CANCELLED,
                "a annulé la réapprovisionnement {$stockReceipt->receipt_number}",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception annulée avec succès',
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_VALIDATED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $stockReceipt,
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marquer la réception comme évaluée
     * POST /api/stock-receipts/{id}/mark-rated
     */
    public function markAsRated(StockReceipt $stockReceipt)
    {
        try {
            $stockReceipt->update(['status' => 'rated']);
            $stockReceipt->createBatches();
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_RATED,
                "a évalué la réapprovisionnement {$stockReceipt->receipt_number}",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception marquée comme évaluée avec succès',
            ]);

        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_RATED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $stockReceipt,
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du marquage en rated',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Évaluer tous les items d'une réception et marquer comme évaluée
     * POST /api/stock-receipts/{id}/rate
     */
    public function rateReceipt(RateStockReceiptRequest $request, StockReceipt $stockReceipt): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $stockReceipt) {
                // 1. Enregistrer toutes les évaluations
                foreach ($request->items as $itemData) {
                    $item = $stockReceipt->items()->findOrFail($itemData['item_id']);

                    // Mettre à jour la qualité globale de l'item
                    $item->updateQuality(
                        $itemData['quality_rating'],
                        $itemData['quality_notes'] ?? null
                    );

                    // Créer les ratings de conformité pour chaque attribut
                    if (! empty($itemData['attribute_ratings'])) {
                        foreach ($itemData['attribute_ratings'] as $attrRating) {
                            $item->addAttributeRating(
                                $attrRating['attribute_type_id'],
                                $attrRating['conformity_rating'],
                                $attrRating['notes'] ?? null
                            );
                        }
                    }
                }

                // 2. Mettre à jour la note du fournisseur (moyenne simple)
                if ($stockReceipt->supplier) {
                    $stockReceipt->supplier->updateReliabilityScore();
                }

                // 3. Marquer la réception comme évaluée
                $stockReceipt->update(['status' => 'rated']);

                // 4. Créer les batches
                $stockReceipt->createBatches();
            });

            // Recharger avec les relations
            $stockReceipt->load([
                'items.ratings.attributeType',
                'items.ratings.ratedBy',
                'items.variant.product',
            ]);
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_RATED,
                "a évalué la réapprovisionnement {$stockReceipt->receipt_number}",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Réception évaluée et validée avec succès',
                'data' => new StockReceiptResource($stockReceipt),
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_RATED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $stockReceipt,
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'évaluation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    * Enregistrer un paiement pour cette réception
    * POST /api/stock-receipts/{id}/payment
    */
    public function recordPayment(Request $request, StockReceipt $stockReceipt): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:supplier,freight,other',
            'expense_category_id' => 'required_if:payment_type,other|exists:expense_categories,id',
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ]);

        try {
            $transaction = DB::transaction(function () use ($request, $stockReceipt) {

                // Déterminer le type de transaction et la catégorie
                if ($request->payment_type === 'supplier') {
                    $data = [
                        'account_id' => $request->account_id,
                        'recipient_name' => $stockReceipt->supplier->name,
                        'transaction_type_id' => TransactionType::where('code', 'EXPENSE')->first()->id,
                        'amount' => $request->amount,
                        'description' => "Paiement fournisseur - {$stockReceipt->receipt_number}",
                        'supplier_id' => $stockReceipt->supplier_id,
                        'expense_category_id' => ExpenseCategory::where('name', 'Approvisionnement')->first()->id,
                        'stock_receipt_id' => $stockReceipt->id,
                        'notes' => $request->notes,
                        'reference_number' => $request->reference_number,
                        'transaction_date' => $request->transaction_date ?? now(),
                    ];

                    return AccountTransaction::createTransaction($data);

                } elseif ($request->payment_type === 'freight') {
                    // Vérifier qu'il y a un transitaire
                    if (! $stockReceipt->freight_forwarder_id) {
                        throw new \Exception('Aucun transitaire associé à cette réception');
                    }

                    // Vérifier qu'on n'a pas déjà payé le transitaire
                    $existingPayment = AccountTransaction::where('stock_receipt_id', $stockReceipt->id)
                        ->where('freight_forwarder_id', $stockReceipt->freight_forwarder_id)
                        ->whereNull('reversed_transaction_id')
                        ->exists();

                    if ($existingPayment) {
                        throw new \Exception('Le transitaire a déjà été payé pour cette réception');
                    }
                    $freightForwarder = $stockReceipt->freightForwarder;

                    if (! $freightForwarder) {
                        throw new \Exception('Transitaire introuvable ou non chargé');
                    }

                    $transaction = AccountTransaction::recordOperatingExpense(
                        accountId: $request->account_id,
                        expenseCategoryId: ExpenseCategory::where('name', 'Transport & Transit')->firstOrFail()->id,
                        amount: $request->amount,
                        recipientName: $freightForwarder->name,
                        notes: $request->notes,
                        transactionDate: $request->transaction_date
                    );

                    // Ajouter les relations manquantes
                    $transaction->update([
                        'freight_forwarder_id' => $stockReceipt->freight_forwarder_id,
                        'stock_receipt_id' => $stockReceipt->id,
                    ]);

                    return $transaction;

                } else {
                    // Autre dépense
                    $transaction = AccountTransaction::recordOperatingExpense(
                        accountId: $request->account_id,
                        expenseCategoryId: $request->expense_category_id,
                        amount: $request->amount,
                        recipientName: $request->recipient_name,
                        notes: $request->notes,
                        transactionDate: $request->transaction_date
                    );

                    // Ajouter la relation stock_receipt
                    $transaction->update([
                        'stock_receipt_id' => $stockReceipt->id,
                    ]);

                    return $transaction;
                }
            });
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_PAYMENT,
                "a payer $transaction->amount pour le réapprovisionnement {$stockReceipt->receipt_number}",
                [
                    'model_type' => AccountTransaction::class,
                    'model_id' => $transaction->id,
                    'metadata' => $transaction,
                ],
                "transactions/{$transaction->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Paiement enregistré avec succès',
                'data' => $transaction,
            ], 201);

        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_PAYMENT,
                'Réapprovisionnement non payé',
                $e,
                [
                    'metadata' => $stockReceipt,
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage(),
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
                'rated' => StockReceipt::where('status', 'rated')->count(),
                'costs_allocated' => StockReceipt::where('status', 'costs_allocated')->count(),
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
            'data' => $stats,
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
            $qualityRatings[] = $item->quality_rating ?? 0;
            $conformityRates[] = $item->getConformityRate()['rate'];
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
                    'freight_forwarder' => $stockReceipt->freightForwarder?->name,
                ],
                'quantities' => [
                    'total_ordered' => $totalOrdered,
                    'total_received' => $totalReceived,
                    'fulfillment_rate' => $totalOrdered > 0 ? ($totalReceived / $totalOrdered) * 100 : 0,
                    'items_count' => $items->count(),
                ],
                'costs' => [
                    'total_cost_ariary' => $totalCost,
                    'average_unit_cost' => $totalReceived > 0 ? $totalCost / $totalReceived : 0,
                ],
                'quality' => [
                    'average_quality_rating' => round($averageQuality, 2),
                    'average_conformity_rate' => round($averageConformity, 2),
                    'overall_score' => round(($averageQuality + ($averageConformity / 10)) / 2, 2),
                ],
                'payments' => [
                    'total_paid' => $stockReceipt->transactions()->sum('amount'),
                    'remaining' => $totalCost - $stockReceipt->transactions()->sum('amount'),
                    'transactions_count' => $stockReceipt->transactions()->count(),
                ],
            ],
        ]);
    }

    /**
     * ⭐ OBTENIR LES RECOMMANDATIONS de répartition des coûts par produit
     * GET /api/stock-receipts/{id}/cost-recommendations
     *
     * Query params:
     * - method: weight|value|quantity (default: value)
     *
     * Retourne les RECOMMANDATIONS calculées automatiquement
     * (L'utilisateur peut ensuite ajuster et appliquer via /apply-costs)
     */
    public function getCostRecommendations(
        GetCostRecommendationsRequest $request,
        StockReceipt $stockReceipt
    ): JsonResponse {
        try {
            if (! $stockReceipt->hasBatches()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Aucun batch trouvé. Créez d\'abord les batches après l\'arrivée de la commande.',
                ], 422);
            }

            $method = $request->get('method', 'weight');

            // CORRECTION : Utiliser le bon nom de relation 'attributeValues'
            $batches = $stockReceipt->batches()
                ->with([
                    'variant.product',
                    'variant.attributeValues.attributeType', // Relation correcte
                ])
                ->get();

            $expenses = $stockReceipt->getExpensesByCategory();

            // Convertir en collection si nécessaire
            if (! $expenses instanceof \Illuminate\Support\Collection) {
                $expenses = collect($expenses);
            }

            // Dépenses avec filter()
            $freightCosts = $expenses
                ->filter(function ($expense) {
                    return isset($expense['category_name']) &&
                           $expense['category_name'] === 'Transport & Transit';
                })
                ->sum('total_amount');

            $otherCosts = $expenses
                ->filter(function ($expense) {
                    return isset($expense['category_name']) &&
                           ! in_array($expense['category_name'], [
                               'Transport & Transit',
                               'Approvisionnement',
                           ]);
                })
                ->sum('total_amount');

            // Grouper par produit
            $batchesByProduct = $batches->groupBy(function ($batch) {
                return $batch->variant->product_id;
            });

            $productTotals = [
                'price' => 0,
                'quantity' => 0,
                'weight' => 0,
            ];

            foreach ($batchesByProduct as $productBatches) {
                $totalQty = $productBatches->sum('initial_quantity');
                $unitPrice = $productBatches->first()->supplier_unit_cost;

                $productTotals['price'] += $unitPrice;
                $productTotals['quantity'] += $totalQty;
                $productTotals['weight'] += $unitPrice * $totalQty;
            }

            $recommendations = [];

            foreach ($batchesByProduct as $productId => $productBatches) {
                if (! $productBatches->first()->variant || ! $productBatches->first()->variant->product) {
                    continue;
                }

                $product = $productBatches->first()->variant->product;
                $totalQty = $productBatches->sum('initial_quantity');
                $unitPrice = $productBatches->first()->supplier_unit_cost;

                switch ($method) {
                    case 'price':
                        $ratio = $productTotals['price'] > 0
                            ? $unitPrice / $productTotals['price']
                            : 0;
                        break;

                    case 'quantity':
                        $ratio = $productTotals['quantity'] > 0
                            ? $totalQty / $productTotals['quantity']
                            : 0;
                        break;

                    case 'weight':
                    default:
                        $productWeight = $unitPrice * $totalQty;
                        $ratio = $productTotals['weight'] > 0
                            ? $productWeight / $productTotals['weight']
                            : 0;
                        break;
                }

                $freightPerUnit = $ratio > 0
                    ? ($ratio * $freightCosts) / $totalQty
                    : 0;

                $otherPerUnit = $ratio > 0
                    ? ($ratio * $otherCosts) / $totalQty
                    : 0;

                $recommendations[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'product_current_base_price' => $product->base_price,
                    'supplier_unit_cost' => round($unitPrice, 2),
                    'recommended_freight_cost_per_unit' => round($freightPerUnit, 2),
                    'recommended_other_costs_per_unit' => round($otherPerUnit, 2),
                    'recommended_total_unit_cost' => round(
                        $unitPrice + $freightPerUnit + $otherPerUnit, 2
                    ),
                    'total_quantity' => $totalQty,
                    'variants_count' => $productBatches->count(),
                    'total_freight_cost' => round($freightPerUnit * $totalQty, 2),
                    'total_other_costs' => round($otherPerUnit * $totalQty, 2),
                    'variants' => $productBatches->map(function ($batch) {
                        // CORRECTION : Vérifier si la méthode attributeTypeValueMap existe
                        $variant = $batch->variant;

                        // Option 1: Si la méthode existe
                        if (method_exists($variant, 'attributeTypeValueMap')) {
                            $attributes = $variant->attributeTypeValueMap();
                        }
                        // Option 2: Construire manuellement
                        else {
                            $attributes = $variant->attributeValues
                                ->map(function ($attrValue) {
                                    return [
                                        'type' => $attrValue->attributeType->name ?? 'Unknown',
                                        'value' => $attrValue->value,
                                    ];
                                })
                                ->toArray();
                        }

                        return [
                            'variant_id' => $batch->variant_id,
                            'variant_attributes' => $attributes,
                            'quantity' => $batch->initial_quantity,
                        ];
                    })->values(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Recommandations calculées avec succès',
                'data' => [
                    'allocation_method' => $method,
                    'total_expenses' => [
                        'freight_costs' => round($freightCosts, 2),
                        'other_costs' => round($otherCosts, 2),
                        'total_to_allocate' => round($freightCosts + $otherCosts, 2),
                    ],
                    'statistics' => [
                        'products_count' => count($recommendations),
                        'batches_count' => $batches->count(),
                    ],
                    'recommendations' => $recommendations,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur getCostRecommendations', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du calcul des recommandations',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }

    /**
     * ⭐ APPLIQUER les coûts validés par l'utilisateur
     * POST /api/stock-receipts/{id}/apply-costs
     *
     * L'utilisateur envoie SA VERSION FINALE (peut être différente des recommandations)
     *
     * Body:
     * {
     *   "allocations": [
     *     {
     *       "product_id": 1,
     *       "freight_cost_per_unit": 150.00,
     *       "other_costs_per_unit": 50.00
     *     }
     *   ]
     * }
     */
    public function applyCosts(
        ApplyCostAllocationRequest $request,
        StockReceipt $stockReceipt
    ): JsonResponse {
        try {
            if (! $stockReceipt->hasBatches()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Aucun batch trouvé.',
                ], 422);
            }

            // Formater les allocations par product_id
            $customAllocations = [];
            foreach ($request->allocations as $allocation) {
                $customAllocations[$allocation['product_id']] = [
                    'freight_cost_per_unit' => floatval($allocation['freight_cost_per_unit']),
                    'other_costs_per_unit' => floatval($allocation['other_costs_per_unit']),
                ];
            }

            // Appliquer les coûts aux batches
            $result = $stockReceipt->allocateCostsToBatches('value', $customAllocations);

            // Mettre à jour le statut si nécessaire
            if ($stockReceipt->status != 'cost_allocated') {
                $stockReceipt->update([
                    'status' => 'cost_allocated',
                    'cost_validated_by' => Auth::id(),
                    'cost_validated_at' => now(),
                ]);
            }
            ActivityLogger::success(
                ActivityAction::STOCK_RECEIPT_COST_DISTRIBUTED,
                "a répartit les couts du réapprovisionnement {$stockReceipt->receipt_number}",
                [
                    'model_type' => StockReceipt::class,
                    'model_id' => $stockReceipt->id,
                    'metadata' => $stockReceipt,
                ],
                "reapprovisionnements/{$stockReceipt->id}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Coûts appliqués avec succès aux batches',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_RECEIPT_COST_DISTRIBUTED,
                'Réapprovisionnement non marqué à jour',
                $e,
                [
                    'metadata' => $stockReceipt,
                ]
            );
            // Ajouter un log détaillé
            Log::error('Erreur applyCosts', [
                'stock_receipt_id' => $stockReceipt->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'application des coûts',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
            ], 500);
        }
    }

    /**
     * ⭐ AJOUTER une dépense autre (douane, manutention, etc.)
     * POST /api/stock-receipts/{id}/expenses
     */
    public function addExpense(
        AddExpenseRequest $request,
        StockReceipt $stockReceipt
    ): JsonResponse {
        try {
            $transaction = $stockReceipt->addExpense($request->validated());
            $transaction->load(['expenseCategory', 'account', 'creator']);

            return response()->json([
                'status' => 'success',
                'message' => 'Dépense ajoutée avec succès',
                'data' => new ReceiptExpenseResource($transaction),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'ajout de la dépense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir toutes les dépenses liées
     * GET /api/stock-receipts/{id}/expenses
     */
    public function getExpenses(StockReceipt $stockReceipt): JsonResponse
    {
        try {
            $expenses = $stockReceipt->getAllExpenses();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_amount' => (float) $expenses->sum('amount'),
                    'expenses_count' => $expenses->count(),
                    'by_category' => $stockReceipt->getExpensesByCategory()->values(),
                    'expenses' => ReceiptExpenseResource::collection($expenses),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des dépenses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer les batches après arrivée
     * POST /api/stock-receipts/{id}/batches/create
     */
    public function createBatches(StockReceipt $stockReceipt): JsonResponse
    {
        try {
            if ($stockReceipt->status !== 'arrived') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La réception doit être marquée comme arrivée.',
                ], 422);
            }

            if ($stockReceipt->hasBatches()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Les batches existent déjà.',
                ], 422);
            }

            $batches = $stockReceipt->createBatches();

            return response()->json([
                'status' => 'success',
                'message' => 'Batches créés avec succès',
                'data' => [
                    'batches_count' => $batches->count(),
                    'batches' => $batches->map(fn ($b) => [
                        'id' => $b->id,
                        'batch_number' => $b->batch_number,
                        'variant_id' => $b->variant_id,
                        'product_name' => $b->variant->product->name,
                        'quantity' => $b->initial_quantity,
                        'supplier_unit_cost' => (float) $b->supplier_unit_cost,
                        'cost_status' => $b->cost_status,
                    ]),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création des batches',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function moveReceivedQuantity(Request $request, $stockReceiptId)
    {
        // 🔹 Valider les données reçues
        $data = $request->validate([
            'from_variant_id' => 'required|exists:product_variants,id',
            'to_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Vérifier que ce ne sont pas les mêmes variants
        if ($data['from_variant_id'] === $data['to_variant_id']) {
            return response()->json([
                'message' => 'Les variants doivent être différents',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($data, $stockReceiptId) {

                // 🔹 Charger la réception
                $receipt = StockReceipt::findOrFail($stockReceiptId);

                // 🔹 Charger le variant source depuis la DB
                $sourceItem = StockReceiptItem::where('stock_receipt_id', $receipt->id)
                    ->where('variant_id', $data['from_variant_id'])
                    ->first();

                if (! $sourceItem) {
                    throw new \Exception('Variant source introuvable dans la réception');
                }

                if ($sourceItem->quantity_received < $data['quantity']) {
                    throw new \Exception('Quantité reçue insuffisante');
                }

                // 🔹 Décrémenter la quantité reçue du source
                $sourceItem->quantity_received -= $data['quantity'];
                $sourceItem->save();

                // 🔹 Charger ou créer le variant destination
                $destinationItem = StockReceiptItem::where('stock_receipt_id', $receipt->id)
                    ->where('variant_id', $data['to_variant_id'])
                    ->first();

                if ($destinationItem) {
                    $destinationItem->quantity_received += $data['quantity'];
                    $destinationItem->save();
                } else {
                    StockReceiptItem::create([
                        'stock_receipt_id' => $receipt->id,
                        'variant_id' => $data['to_variant_id'],
                        'quantity_ordered' => 0,
                        'quantity_received' => $data['quantity'],
                        'unit_cost_ariary' => $sourceItem->unit_cost_ariary,
                    ]);
                }
                ActivityLogger::success(
                    ActivityAction::STOCK_TRANSFERRED,
                    "a répartit les couts du réapprovisionnement {$receipt->receipt_number}",
                    [
                        'model_type' => StockReceiptItem::class,
                        'model_id' => $sourceItem->id,
                        'metadata' => [
                            'source' => $sourceItem,
                            'destination' => $destinationItem,
                        ],
                    ],
                    "reapprovisionnements/{$receipt->id}"
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Quantité corrigée avant création des batches',
                ]);
            });

        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::STOCK_TRANSFERRED,
                'Quantité  non transféré',
                $e,
                [
                    'metadata' => $request,
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getCostAllocated(StockReceipt $stockReceipt)
    {
        try {
            $batches = $stockReceipt->batches()
                ->with('variant.product')
                ->get();

            // Regroupement par produit
            $allocations = $batches
                ->groupBy(fn ($batch) => $batch->variant->product->id)
                ->map(function ($productBatches) {

                    $product = $productBatches->first()->variant->product;

                    return [
                        'product_id' => $product->id,
                        'product_name' => $product->name,

                        // ===== Coûts unitaires (au niveau produit) =====
                        'costs' => [
                            'supplier_unit' => (float) $productBatches->first()->supplier_unit_cost,
                            'freight_unit' => (float) $productBatches->first()->freight_cost_per_unit,
                            'other_unit' => (float) $productBatches->first()->other_costs_per_unit,
                            'total_unit' => (float) $productBatches->first()->total_unit_cost,
                        ],

                        // ===== Quantité totale produit =====
                        'total_quantity' => $productBatches->sum('initial_quantity'),

                        // ===== Variantes =====
                        'variants' => $productBatches->map(function ($batch) {
                            return [
                                'batch_number' => $batch->batch_number,
                                'variant_id' => $batch->variant_id,
                                'attributes' => $batch->variant->attributeTypeValueMap(),
                                'intial_quantity' => $batch->initial_quantity,
                                'remaining_quantity' => $batch->remaining_quantity,
                            ];
                        })->values(),
                    ];
                })
                ->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'validated_at' => $stockReceipt->cost_validated_at,
                    'cost_validated_by' => $stockReceipt->costValidator
                        ? [
                            'id' => $stockReceipt->costValidator->id,
                            'name' => $stockReceipt->costValidator->name,
                        ]
                        : null,
                    'allocations' => $allocations,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des coûts alloués',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
