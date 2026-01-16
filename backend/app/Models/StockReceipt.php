<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'created_by'
    ];

    protected $casts = [
        'validated_at' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'datetime',
        'total_cost_ariary' => 'decimal:2',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
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

                // Assigner à un emplacement si fourni et créer le mouvement de stock
                if (isset($itemData['location_id']) && $itemData['quantity_received'] > 0) {
                    // Ajouter le stock à l'emplacement
                    $variantLocation = ProductVariantLocation::firstOrCreate(
                        [
                            'variant_id' => $item->variant_id,
                            'location_id' => $itemData['location_id']
                        ],
                        ['quantity' => 0]
                    );
                    
                    $variantLocation->increment('quantity', $itemData['quantity_received']);

                    // Recalculer le stock total de la variante
                    $item->variant->recalculateTotalStock();

                    // Créer le mouvement de stock de type 'receipt'
                    StockMovement::create([
                        'variant_id' => $item->variant_id,
                        'from_location_id' => null, // Pas de source pour une réception
                        'to_location_id' => $itemData['location_id'],
                        'quantity' => $itemData['quantity_received'],
                        'movement_type' => StockMovement::TYPE_RECEIPT,
                        'stock_receipt_id' => $this->id,
                        'performed_by' => Auth::id(),
                        'reason' => "Réception {$this->receipt_number}",
                        'notes' => $item->notes,
                        'batch_id' => $batchId
                    ]);
                }
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
    public function validate(): void
    {
        if ($this->status !== 'arrived') {
            throw new \Exception('La réception doit être marquée comme arrivée avant validation');
        }

        DB::transaction(function () {
            $this->update([
                'status' => 'validated',
                'validated_at' => now()
            ]);

            // Calculer et mettre à jour les scores
            $this->updateSupplierScore();
            
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
        
        // Mettre à jour les statistiques cumulatives
        $supplier->increment('total_orders');
        $supplier->increment('total_items_ordered', $totalOrdered);
        $supplier->increment('total_items_received', $totalReceived);
        $supplier->increment('total_value_ordered', $totalValueOrdered);
        $supplier->increment('total_value_received', $totalValueReceived);
        $supplier->increment('weighted_quality_sum', $weightedQualitySum);
        $supplier->increment('total_weighted_value', $totalWeightedValue);
        
        // Calculer le score avec facteur de fiabilité
        $newScore = $this->calculateReliabilityScore($supplier);
        
        $supplier->update(['reliability_score' => $newScore]);
    }

    /**
     * Calculer le score de fiabilité du fournisseur
     * Prend en compte : qualité, taux de réception, et historique
     */
    protected function calculateReliabilityScore($supplier): float
    {
        // 1. SCORE DE QUALITÉ (0-10) - Basé sur les évaluations
        $qualityScore = $supplier->total_weighted_value > 0
            ? ($supplier->weighted_quality_sum / $supplier->total_weighted_value)
            : 5.0;
        
        // 2. TAUX DE RÉCEPTION (0-10) - Quantité reçue vs commandée
        $fulfillmentRate = $supplier->total_items_ordered > 0
            ? ($supplier->total_items_received / $supplier->total_items_ordered)
            : 1.0;
        $fulfillmentScore = $fulfillmentRate * 10; // Convertir en score sur 10
        
        // 3. FACTEUR D'HISTORIQUE (0-1) - Confiance basée sur le nombre de commandes
        // Plus le fournisseur a d'historique, plus on lui fait confiance
        $totalOrders = $supplier->total_orders;
        
        if ($totalOrders >= 50) {
            $historyFactor = 1.0; // Confiance totale après 50 commandes
        } elseif ($totalOrders >= 20) {
            $historyFactor = 0.9; // Haute confiance après 20 commandes
        } elseif ($totalOrders >= 10) {
            $historyFactor = 0.8; // Bonne confiance après 10 commandes
        } elseif ($totalOrders >= 5) {
            $historyFactor = 0.7; // Confiance moyenne après 5 commandes
        } elseif ($totalOrders >= 3) {
            $historyFactor = 0.6; // Confiance faible après 3 commandes
        } else {
            $historyFactor = 0.5; // Très faible confiance (1-2 commandes)
        }
        
        // 4. CALCUL DU SCORE FINAL
        // Score brut basé sur qualité (70%) et taux de réception (30%)
        $rawScore = ($qualityScore * 0.7) + ($fulfillmentScore * 0.3);
        
        // Appliquer le facteur d'historique pour ramener vers la moyenne (5.0)
        // Les nouveaux fournisseurs sont ramenés vers 5.0, les établis gardent leur score
        $finalScore = ($rawScore * $historyFactor) + (5.0 * (1 - $historyFactor));
        
        // S'assurer que le score reste entre 0 et 10
        return max(0, min(10, round($finalScore, 2)));
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
            $transactions = $this->transactions()->get();
            
            foreach ($transactions as $transaction) {
                $transaction->reverse();
            }

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
}