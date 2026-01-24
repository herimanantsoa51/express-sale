<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
class StockReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'supplier_id',
        'freight_forwarder_id',
        'expected_delivery_date',
        'actual_delivery_date',
        'validated_at',
        'total_cost_ariary',
        'status',
        'notes',
        'created_by',
        'cost_validated_by',
        'cost_validated_at'
    ];

    protected $casts = [
        'validated_at' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'datetime',
        'total_cost_ariary' => 'decimal:2',
         'cost_validated_at' => 'date'
    ];

    // Status possibles: pending, sent, in_transit, arrived, validated, cancelled
    // Delivery status: ordered, shipped, in_transit, delivered, delayed

    // Relations
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function freightForwarder(): BelongsTo
    {
        return $this->belongsTo(FreightForwarder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function costValidator():BelongsTo
    {
        return $this->belongsTo(User::class,'cost_validated_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    /**
     * Obtenir tous les batches liés à cette réception
     */
    

    public function batches(): HasManyThrough
    {
        return $this->hasManyThrough(
            StockBatch::class,
            StockReceiptItem::class,
            'stock_receipt_id',        // FK sur stock_receipt_items
            'stock_receipt_item_id',   // FK sur stock_batches
            'id',
            'id'
        );
    }

    /**
     * Vérifier si les batches ont été créés
     */
    public function hasBatches(): bool
    {
        return StockBatch::whereIn('stock_receipt_item_id', 
            $this->items->pluck('id')
        )->exists();
    }

    // Scopes
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByFreightForwarder($query, $freightForwarderId)
    {
        return $query->where('freight_forwarder_id', $freightForwarderId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('actual_delivery_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Méthodes
    public static function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $lastReceipt = static::whereDate('created_at', now())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastReceipt ? (int) substr($lastReceipt->receipt_number, -4) + 1 : 1;
        
        return 'RCP-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Créer une réception de stock avec ses items
     */
    public static function createWithItems(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $data['receipt_number'] = self::generateReceiptNumber();
            $data['created_by'] = Auth::id();
            $data['status'] = 'pending';
            
            $receipt = self::create([
                'receipt_number' => $data['receipt_number'],
                'supplier_id' => $data['supplier_id'],
                'freight_forwarder_id' => $data['freight_forwarder_id'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'total_cost_ariary' => 0,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by']
            ]);

            $totalCost = 0;
            foreach ($data['items'] as $itemData) {
                $item = $receipt->items()->create([
                    'variant_id' => $itemData['variant_id'],
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost_ariary' => $itemData['unit_cost_ariary'],
                    'notes' => $itemData['notes'] ?? null
                ]);

                $totalCost += $item->quantity_ordered * $item->unit_cost_ariary;
            }

            $receipt->update(['total_cost_ariary' => $totalCost]);

            return $receipt->load(['items.variant.product', 'supplier', 'freightForwarder']);
        });
    }

    /**
     * Marquer comme envoyé
     */
    public function markAsShipped(?string $notes = null): void
    {
        $this->update([
            'status' => 'sent',
            'notes' => $notes ?? $this->notes
        ]);
    }

    /**
     * Marquer comme en transit
     */
    public function markAsInTransit(?string $notes = null): void
    {
        $this->update([
            'status' => 'in_transit',
            'notes' => $notes ?? $this->notes
        ]);
    }

    /**
     * Marquer comme arrivé et assigner les emplacements
     * Crée également les mouvements de stock correspondants
     */
    public function markAsArrived(array $itemsWithLocations): void
    {
        DB::transaction(function () use ($itemsWithLocations) {
            $this->update([
                'status' => 'arrived',
                'actual_delivery_date' => now()
            ]);

            // Générer un batch_id unique pour regrouper tous les mouvements de cette réception
            $batchId = 'RCP-' . $this->receipt_number . '-' . Str::random(4);

            // Mettre à jour les quantités reçues et assigner les emplacements
            foreach ($itemsWithLocations as $itemData) {
                $item = $this->items()->findOrFail($itemData['item_id']);
                
                $item->update([
                    'quantity_received' => $itemData['quantity_received']
                ]);

                // // Assigner à un emplacement si fourni et créer le mouvement de stock
                // if (isset($itemData['location_id']) && $itemData['quantity_received'] > 0) {
                //     // Ajouter le stock à l'emplacement
                //     $variantLocation = ProductVariantLocation::firstOrCreate(
                //         [
                //             'variant_id' => $item->variant_id,
                //             'location_id' => $itemData['location_id']
                //         ],
                //         ['quantity' => 0]
                //     );
                    
                //     $variantLocation->increment('quantity', $itemData['quantity_received']);

                //     // Recalculer le stock total de la variante
                //     $item->variant->recalculateTotalStock();

                //     // Créer le mouvement de stock de type 'receipt'
                //     StockMovement::create([
                //         'variant_id' => $item->variant_id,
                //         'from_location_id' => null, // Pas de source pour une réception
                //         'to_location_id' => $itemData['location_id'],
                //         'quantity' => $itemData['quantity_received'],
                //         'movement_type' => StockMovement::TYPE_RECEIPT,
                //         'stock_receipt_id' => $this->id,
                //         'performed_by' => Auth::id(),
                //         'reason' => "Réception {$this->receipt_number}",
                //         'notes' => $item->notes,
                //         'batch_id' => $batchId
                //     ]);
                // }
            }

            // Recalculer le coût total basé sur les quantités reçues
            $totalCost = 0;
            foreach ($this->items()->get() as $item) {
                $totalCost += $item->quantity_received * $item->unit_cost_ariary;
            }
            $this->update(['total_cost_ariary' => $totalCost]);
        });
    }

    /**
     * Valider la réception et mettre à jour les scores
     */
    public function validate(array $itemsWithLocations): void
    {
        if ($this->status !== 'cost_allocated') {
            throw new \Exception(
                'La réception doit être marquée comme arrivée et coûts répartis avant validation'
            );
        }
    
        DB::transaction(function () use ($itemsWithLocations) {
            $this->update([
                'status' => 'validated',
                'cost_status' => 'validated',
                'cost_validated_by' => Auth::id(),
                'cost_validated_at' => now(),
            ]);
    
            foreach ($itemsWithLocations as $itemData) {
                $item = $this->items()->findOrFail($itemData['item_id']);
    
                if (!isset($itemData['location_id']) || $item->quantity_received <= 0) {
                    continue;
                }
    
                $variantLocation = ProductVariantLocation::firstOrCreate(
                    [
                        'variant_id' => $item->variant_id,
                        'location_id' => $itemData['location_id'],
                    ],
                    ['quantity' => 0]
                );
    
                $batches = StockBatch::where('stock_receipt_item_id', $item->id)
                    ->lockForUpdate()
                    ->get();
    
                foreach ($batches as $batch) {
                    if (is_null($batch->received_date)) {
                        $batch->received_date =
                            $this->actual_delivery_date ?? $this->created_at;
                    }
    
                    $batch->update([
                        'cost_status' => 'validated',
                        'cost_validated_at' => now(),
                    ]);
    
                    // stock par batch
                    $variantLocation->increment('quantity', $batch->initial_quantity);
                }
    
                $item->variant->recalculateTotalStock();
    
                StockMovement::create([
                    'variant_id' => $item->variant_id,
                    'from_location_id' => null,
                    'to_location_id' => $itemData['location_id'],
                    'quantity' => $item->quantity_received,
                    'movement_type' => StockMovement::TYPE_RECEIPT,
                    'stock_receipt_id' => $this->id,
                    'performed_by' => Auth::id(),
                    'reason' => "Réception {$this->receipt_number}",
                    'notes' => $item->notes,
                ]);
            }
    
            $this->supplier->updateReliabilityScore();
    
            if ($this->freight_forwarder_id) {
                $this->updateFreightForwarderScore();
            }
        });
    }
    

    /**
     * Mettre à jour le score du fournisseur avec système de fiabilité amélioré
     * Prend en compte : qualité, taux de réception, et historique
     */
    protected function updateSupplierScore(): void
    {
        $supplier = $this->supplier;
        
        $totalOrdered = 0;
        $totalReceived = 0;
        $totalValueOrdered = 0;
        $totalValueReceived = 0;
        $weightedQualitySum = 0;
        $totalWeightedValue = 0;
        
        // Collecter les données de cette réception
        foreach ($this->items as $item) {
            $totalOrdered += $item->quantity_ordered;
            $totalReceived += $item->quantity_received;
            $totalValueOrdered += $item->quantity_ordered * $item->unit_cost_ariary;
            $totalValueReceived += $item->quantity_received * $item->unit_cost_ariary;
            
            $averageQuality = $item->getAverageQualityRating();
            if ($averageQuality > 0) {
                $itemValue = $item->quantity_received * $item->unit_cost_ariary;
                $weightedQualitySum += $averageQuality * $itemValue;
                $totalWeightedValue += $itemValue;
            }
        }
      
        
        // Calculer le score avec facteur de fiabilité
        $newScore = $this->calculateReliabilityScore($supplier);
        
        $supplier->update(['reliability_score' => $newScore]);
    }

   
    /**
     * Mettre à jour le score du transitaire basé uniquement sur la ponctualité
     */
    protected function updateFreightForwarderScore(): void
    {
        $forwarder = $this->freightForwarder;
        
        // Calculer le score de livraison basé sur la date prévue vs date réelle
        $deliveryScore = $this->calculateDeliveryScore();
        
        // Calculer la valeur totale de la réception pour la pondération
        $receiptValue = 0;
        foreach ($this->items as $item) {
            $receiptValue += $item->quantity_received * $item->unit_cost_ariary;
        }
        
        // Mettre à jour les statistiques du transitaire
        $forwarder->increment('total_shipments');
        $forwarder->increment('weighted_service_sum', $deliveryScore * $receiptValue);
        $forwarder->increment('total_weighted_shipment_value', $receiptValue);
        
        // Calculer le nouveau score moyen pondéré
        $newScore = $forwarder->total_weighted_shipment_value > 0
            ? ($forwarder->weighted_service_sum / $forwarder->total_weighted_shipment_value)
            : 5.0;
        
        $forwarder->update(['service_score' => $newScore]);
    }

    /**
     * Calculer le score de livraison basé sur la ponctualité
     * Score de 0 à 10 basé sur la différence entre date prévue et date réelle
     */
    protected function calculateDeliveryScore(): float
    {
        // Si pas de date prévue ou pas de date d'arrivée, score neutre
        if (!$this->expected_delivery_date || !$this->actual_delivery_date) {
            return 5.0;
        }
        
        $expectedDate = $this->expected_delivery_date->startOfDay();
        $actualDate = $this->actual_delivery_date->startOfDay();
        
        // Livraison exactement à la date prévue = score parfait
        if ($actualDate->equalTo($expectedDate)) {
            return 10.0;
        }
        
        // Livraison en avance = score parfait
        if ($actualDate->lessThan($expectedDate)) {
            return 10.0;
        }
        
        // Livraison en retard = pénalité progressive
        $daysLate = $expectedDate->diffInDays($actualDate);
        
        // Système de pénalité:
        // - 1 jour de retard: 9.0 points (-1.0)
        // - 2 jours de retard: 8.0 points (-2.0)
        // - 5 jours de retard: 5.0 points (-5.0)
        // - 10 jours et plus: 0.0 points
        $score = max(0, 10 - $daysLate);
        
        return (float) $score;
    }

      /**
     * Annuler la réception en créant des transactions inverses
     */
    public function cancel(): void
    {
        if ($this->status === 'validated') {
            throw new \Exception('Impossible d\'annuler une réception déjà validée');
        }

        DB::transaction(function () {
            // Annuler toutes les transactions liées en créant des transactions inverses
            // $transactions = $this->transactions()->get();
            
            // foreach ($transactions as $transaction) {
            //     $transaction->reverse();
            // }

            // Mettre à jour le statut de la réception
            $this->update([
                'status' => 'cancelled',
            ]);
        });
    }
    /**
     * Vérifier si en retard
     */
    public function isDelayed(): bool
    {
        if (!$this->expected_delivery_date) {
            return false;
        }

        return now()->greaterThan($this->expected_delivery_date) 
            && !in_array($this->status, ['arrived', 'validated', 'cancelled']);
    }

    /**
     * Obtenir le nombre de jours de retard
     */
    public function getDaysDelayed(): int
    {
        if (!$this->isDelayed()) {
            return 0;
        }

        return now()->startOfDay()->diffInDays($this->expected_delivery_date);
    }

     /**
     * Créer automatiquement les batches pour tous les items reçus
     * Appelé après markAsArrived()
     * 
     * @return Collection<StockBatch>
     */
    public function createBatches(): \Illuminate\Database\Eloquent\Collection
{
    if ($this->status !== 'rated') {
        throw new \Exception(
            'Impossible de créer les batches. La réception doit être marquée comme arrivée.'
        );
    }

    return DB::transaction(function () {
        $batches = new \Illuminate\Database\Eloquent\Collection();

        foreach ($this->items as $item) {
            if ($item->quantity_received <= 0) {
                continue;
            }

            $batch = StockBatch::create([
                'variant_id' => $item->variant_id,
                'stock_receipt_item_id' => $item->id,
                'initial_quantity' => $item->quantity_received,
                'remaining_quantity' => $item->quantity_received,
                'supplier_unit_cost' => $item->unit_cost_ariary,
                'freight_cost_per_unit' => 0,
                'other_costs_per_unit' => 0,
                'cost_status' => 'pending',
                'received_date' => $this->actual_delivery_date ?? now(),
            ]);

            $batches->push($batch);
        }

        return $batches;
    });
}


    // =========================================================================
    // GESTION DES DÉPENSES LIÉES
    // =========================================================================

    /**
     * Obtenir toutes les dépenses liées à cette réception
     * (paiements fournisseur, transitaire, et autres dépenses)
     */
    public function getAllExpenses(): Collection
    {
        return AccountTransaction::where('stock_receipt_id', $this->id)
            ->whereHas('transactionType', function($q) {
                $q->where('category', 'expense');
            })
            ->with(['expenseCategory', 'account'])
            ->get();
    }

   

    public function getExpensesByCategory(): Collection
    {
        return $this->getAllExpenses()
            ->groupBy('expense_category_id')
            ->map(function ($transactions, $categoryId) {
                $category = ExpenseCategory::find($categoryId);

                return [
                    'category_id' => $categoryId,
                    'category_name' => $category?->name ?? 'Non catégorisé',
                    'total_amount' => $transactions->sum('amount'),
                    'transactions_count' => $transactions->count(),
                    'transactions' => $transactions,
                ];
            });
    }

    /**
     * Calculer le total des dépenses liées
     */
    public function getTotalExpenses(): float
    {
        return (float) $this->getAllExpenses()->sum('amount');
    }

     /**
     * Ajouter une dépense liée à cette réception
     * (autre que paiement fournisseur/transitaire)
     */
    public function addExpense(array $data): AccountTransaction
    {
        return DB::transaction(function () use ($data) {
            $transactionTypeId = TransactionType::where('code', 'EXPENSE')
                ->firstOrFail()
                ->id;

            return AccountTransaction::createTransaction([
                'account_id' => $data['account_id'],
                'transaction_type_id' => $transactionTypeId,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? "Dépense liée à {$this->receipt_number}",
                'notes' => $data['notes'] ?? null,
                'expense_category_id' => $data['expense_category_id'],
                'recipient_name' => $data['recipient_name'] ?? null,
                'stock_receipt_id' => $this->id,
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);
        });
    }
   


    // =========================================================================
    // RÉPARTITION DES COÛTS SUR LES BATCHES (PAR PRODUIT)
    // =========================================================================

    /**
     * Calculer et appliquer la répartition des frais sur les batches
     * GROUPÉ PAR PRODUIT (pas par variant)
     * 
     * @param string $method Méthode de répartition: 'weight', 'value', 'quantity'
     * @param array $customAllocations Répartition manuelle par produit (optionnel)
     * @return array Résumé de la répartition
     */
    public function allocateCostsToBatches(
        string $method = 'value',
        array $customAllocations = []
    ): array {
        if (!$this->hasBatches()) {
            throw new \Exception('Aucun batch trouvé pour cette réception');
        }
    
        return DB::transaction(function () use ($method, $customAllocations) {
            // CORRECTION: Ajouter ->get() pour convertir en Collection
            $batches = $this->batches()
                ->with(['variant.product', 'variant.attributeValues.attributeType'])
                ->get();
            
            $expenses = $this->getExpensesByCategory();
    
            // Convertir en collection si nécessaire
            if (!$expenses instanceof \Illuminate\Support\Collection) {
                $expenses = collect($expenses);
            }
    
            // Séparer les dépenses avec filter() au lieu de where()
            $freightCosts = $expenses
                ->filter(function ($expense) {
                    return isset($expense['category_name']) && 
                           $expense['category_name'] === 'Transport & Transit';
                })
                ->sum('total_amount');
            
            $otherCosts = $expenses
                ->filter(function ($expense) {
                    return isset($expense['category_name']) && 
                           !in_array($expense['category_name'], [
                               'Transport & Transit',
                               'Approvisionnement'
                           ]);
                })
                ->sum('total_amount');
    
            // Grouper par produit - Maintenant que $batches est une Collection
            $batchesByProduct = $batches->groupBy(function($batch) {
                return $batch->variant->product_id;
            });
    
            // Calculer les totaux pour la répartition AU NIVEAU PRODUIT
            $productTotals = $this->calculateProductAllocationTotals($batchesByProduct, $method);
    
            $allocations = [];
    
            // Répartir par PRODUIT
            foreach ($batchesByProduct as $productId => $productBatches) {
                $firstBatch = $productBatches->first();
                
                // Vérifier que le variant et le produit existent
                if (!$firstBatch->variant || !$firstBatch->variant->product) {
                    continue;
                }
                
                $product = $firstBatch->variant->product;
                
                // COÛT UNIQUE PAR PRODUIT (tous les variants ont le même coût fournisseur)
                $supplierUnitCost = $firstBatch->supplier_unit_cost;
                
                // Quantité totale de ce produit (tous variants confondus)
                $totalProductQuantity = $productBatches->sum('initial_quantity');
    
                // Utiliser allocation personnalisée si fournie
                if (isset($customAllocations[$productId])) {
                    $freightPerUnit = $customAllocations[$productId]['freight_cost_per_unit'];
                    $otherPerUnit = $customAllocations[$productId]['other_costs_per_unit'];
                } else {
                    // Calculer automatiquement selon la méthode
                    $weight = $this->getProductWeight($productBatches, $method, $productTotals);
                    
                    $freightPerUnit = $weight > 0 
                        ? ($weight * $freightCosts) / $totalProductQuantity
                        : 0;
                    
                    $otherPerUnit = $weight > 0
                        ? ($weight * $otherCosts) / $totalProductQuantity
                        : 0;
                }
    
                // APPLIQUER LE MÊME COÛT À TOUS LES VARIANTS DU PRODUIT
                foreach ($productBatches as $batch) {
                    $batch->update([
                        'freight_cost_per_unit' => round($freightPerUnit, 2),
                        'other_costs_per_unit' => round($otherPerUnit, 2),
                        'cost_status' => 'estimated',
                    ]);
                }
    
                // Résumé par produit
                $allocations[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'supplier_unit_cost' => $supplierUnitCost,
                    'freight_cost_per_unit' => round($freightPerUnit, 2),
                    'other_costs_per_unit' => round($otherPerUnit, 2),
                    'total_unit_cost' => round($supplierUnitCost + $freightPerUnit + $otherPerUnit, 2),
                    'total_quantity' => $totalProductQuantity,
                    'variants_count' => $productBatches->count(),
                    'batches' => $productBatches->map(function($batch) {
                        // Vérifier si les attributs sont chargés
                        $attributes = '';
                        if ($batch->variant && $batch->variant->relationLoaded('attributeValues')) {
                            $attributes = $batch->variant->attributeValues
                                ->map(function ($attrValue) {
                                    return $attrValue->value;
                                })
                                ->filter()
                                ->join(', ');
                        }
                        
                        return [
                            'batch_id' => $batch->id,
                            'batch_number' => $batch->batch_number,
                            'variant_id' => $batch->variant_id,
                            'variant_attributes' => $attributes,
                            'quantity' => $batch->initial_quantity,
                            'total_unit_cost' => $batch->fresh()->total_unit_cost,
                        ];
                    })->values(),
                ];
            }
    
            return [
                'method' => $method,
                'total_freight_costs' => $freightCosts,
                'total_other_costs' => $otherCosts,
                'total_allocated' => $freightCosts + $otherCosts,
                'products_count' => $batchesByProduct->count(),
                'batches_count' => $batches->count(),
                'allocations' => $allocations,
            ];
        });
    }

    /**
     * Calculer les totaux pour la répartition PAR PRODUIT
     */
    protected function calculateProductAllocationTotals(Collection $batchesByProduct, string $method): array
    {
        $totals = [
            'weight' => 0,
            'value' => 0,
            'quantity' => 0,
        ];

        foreach ($batchesByProduct as $productId => $productBatches) {
            // Poids moyen du produit (moyenne des variants)
            $avgUnitWeight = $productBatches->avg(function($batch) {
                return $batch->variant->unit_weight ?? 0;
            });
            $totalProductQty = $productBatches->sum('initial_quantity');
            $totals['weight'] += $avgUnitWeight * $totalProductQty;

            // Valeur (coût fournisseur × quantité totale du produit)
            $supplierCost = $productBatches->first()->supplier_unit_cost;
            $totals['value'] += $supplierCost * $totalProductQty;

            // Quantité
            $totals['quantity'] += $totalProductQty;
        }

        return $totals;
    }

    /**
     * Calculer le coefficient de répartition d'un produit
     */
    protected function getProductWeight(Collection $productBatches, string $method, array $totals): float
    {
        $totalProductQty = $productBatches->sum('initial_quantity');
        $supplierCost = $productBatches->first()->supplier_unit_cost;

        switch ($method) {
            case 'weight':
                $avgUnitWeight = $productBatches->avg(fn($b) => $b->variant->unit_weight ?? 0);
                $productWeight = $avgUnitWeight * $totalProductQty;
                return $totals['weight'] > 0 ? ($productWeight / $totals['weight']) : 0;

            case 'value':
                $productValue = $supplierCost * $totalProductQty;
                return $totals['value'] > 0 ? ($productValue / $totals['value']) : 0;

            case 'quantity':
                return $totals['quantity'] > 0 ? ($totalProductQty / $totals['quantity']) : 0;

            default:
                throw new \Exception("Méthode de répartition invalide: {$method}");
        }
    }

    /**
     * Valider les coûts de tous les batches de cette réception
     */
    public function validateBatchCosts(int $userId): bool
    {
        return DB::transaction(function () use ($userId) {
            $batches = $this->batches();

            foreach ($batches as $batch) {
                $batch->update([
                    'cost_status' => 'validated',
                
                ]);
            }

            return true;
        });
    }

    // =========================================================================
    // RECOMMANDATIONS DE PRIX (PAR PRODUIT)
    // =========================================================================

    /**
     * Générer des recommandations de prix de vente PAR PRODUIT
     * (puisque tous les variants ont le même prix)
     * 
     * @param float $targetMargin Marge cible en % (ex: 30 pour 30%)
     * @return Collection
     */
    public function getPricingRecommendations(float $targetMargin = 30.0): Collection
    {
        if (!$this->hasBatches()) {
            throw new \Exception('Aucun batch trouvé pour générer les recommandations');
        }

        $batches = $this->batches();
        $recommendations = collect();

        // ⭐ GROUPER PAR PRODUIT (pas variant)
        $batchesByProduct = $batches->groupBy(function($batch) {
            return $batch->variant->product_id;
        });

        foreach ($batchesByProduct as $productId => $productBatches) {
            $product = Product::find($productId);
            
            if (!$product) continue;

            // Calculer le coût moyen pondéré du PRODUIT
            $totalQuantity = $productBatches->sum('initial_quantity');
            $totalCost = $productBatches->sum(function($batch) {
                return $batch->initial_quantity * $batch->total_unit_cost;
            });

            $avgCost = $totalQuantity > 0 ? ($totalCost / $totalQuantity) : 0;

            // ⭐ Prix actuel du PRODUIT (même pour tous les variants)
            $currentPrice = $product->base_price;

            // Prix recommandé basé sur la marge cible
            // Formule: Prix = Coût / (1 - Marge%)
            $recommendedPrice = $avgCost / (1 - ($targetMargin / 100));

            // Marge actuelle
            $currentMargin = $currentPrice > 0 
                ? (($currentPrice - $avgCost) / $currentPrice) * 100
                : 0;

            // Liste des variants concernés
            $variantsInfo = $productBatches->groupBy('variant_id')->map(function($variantBatches, $variantId) {
                $variant = ProductVariant::find($variantId);
                return [
                    'variant_id' => $variantId,
                    'sku' => $variant->sku,
                    'attributes' => $variant->attributeValues->pluck('value')->join(', '),
                    'quantity_in_batches' => $variantBatches->sum('initial_quantity'),
                    'price_adjustment' => $variant->price_adjustment,
                ];
            });

            $recommendations->push([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'total_quantity_in_batches' => $totalQuantity,
                'variants_count' => $variantsInfo->count(),
                
                // Coûts
                'avg_unit_cost' => round($avgCost, 2),
                
                // Prix actuel
                'current_base_price' => round($currentPrice, 2),
                'current_margin_percent' => round($currentMargin, 2),
                
                // Recommandation
                'target_margin_percent' => $targetMargin,
                'recommended_price' => round($recommendedPrice, 2),
                'price_adjustment_needed' => round($recommendedPrice - $currentPrice, 2),
                'needs_price_increase' => $recommendedPrice > $currentPrice,
                
                // Variants concernés
                'variants' => $variantsInfo->values(),
                
                // Batches
                'batches_summary' => $productBatches->map(function($batch) {
                    return [
                        'batch_number' => $batch->batch_number,
                        'variant_attributes' => $batch->variant->attributeValues->pluck('value')->join(', '),
                        'quantity' => $batch->initial_quantity,
                        'total_unit_cost' => $batch->total_unit_cost,
                        'cost_status' => $batch->cost_status,
                    ];
                }),
            ]);
        }

        return $recommendations->sortByDesc('price_adjustment_needed');
    }

    /**
     * Obtenir un résumé complet de la réception avec coûts
     */
    public function getCostSummary(): array
    {
        $batchesByProduct = $this->hasBatches() 
            ? $this->batches()->groupBy(fn($b) => $b->variant->product_id)
            : collect();

        return [
            'receipt_number' => $this->receipt_number,
            'status' => $this->status,
            'supplier' => $this->supplier->name,
            
            // Coûts
            'total_supplier_cost' => $this->total_cost_ariary,
            'total_expenses' => $this->getTotalExpenses(),
            'total_cost_with_expenses' => $this->total_cost_ariary + $this->getTotalExpenses(),
            
            // Dépenses par catégorie
            'expenses_by_category' => $this->getExpensesByCategory()->values(),
            
            // Batches (par produit)
            'has_batches' => $this->hasBatches(),
            'products_count' => $batchesByProduct->count(),
            'batches_count' => $this->hasBatches() ? $this->batches()->count() : 0,
            'batches_cost_status' => $this->hasBatches() 
                ? $this->batches()->pluck('cost_status')->unique()->values()
                : [],
            
            // Stats
            'items_count' => $this->items->count(),
            'total_quantity_ordered' => $this->items->sum('quantity_ordered'),
            'total_quantity_received' => $this->items->sum('quantity_received'),
        ];
    }
}