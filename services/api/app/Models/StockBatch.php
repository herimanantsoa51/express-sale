<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    protected $fillable = [
        'variant_id',
        'stock_receipt_item_id',
        'batch_number',
        'initial_quantity',
        'remaining_quantity',
        'reserved_quantity',
        'supplier_unit_cost',
        'freight_cost_per_unit',
        'other_costs_per_unit',
        'received_date',
        'cost_validated_at',
        // total_unit_cost est auto-calculé (generated column)
        'cost_status',
    ];

    protected $casts = [

        'supplier_unit_cost' => 'decimal:2',
        'freight_cost_per_unit' => 'decimal:2',
        'other_costs_per_unit' => 'decimal:2',
        'total_unit_cost' => 'decimal:2',
        'received_date' => 'datetime',
        'cost_validated_at' => 'datetime',
    ];

    public $timestamps = false;
    // ===== BOOT: Génération automatique du batch_number =====

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_number)) {
                $batch->batch_number = static::generateBatchNumber();
            }

            // Par défaut, remaining_quantity = initial_quantity
            if ($batch->remaining_quantity === 0 && $batch->initial_quantity > 0) {
                $batch->remaining_quantity = $batch->initial_quantity;
            }
        });

        // Empêcher modification des coûts si batch déjà vendu
        static::updating(function ($batch) {
            if ($batch->saleItemBatches()->exists()) {
                $dirtyFields = ['supplier_unit_cost', 'freight_cost_per_unit', 'other_costs_per_unit'];

                if ($batch->isDirty($dirtyFields)) {
                    throw new \Exception(
                        'Impossible de modifier les coûts du batch #'.$batch->batch_number.
                        ' car il a déjà été vendu.'
                    );
                }
            }
        });
    }

    // ===== GÉNÉRATION DU NUMÉRO DE BATCH =====

    /**
     * Génère un numéro de batch unique
     * Format: BATCH-YYYYMMDD-XXX
     * Exemple: BATCH-20250117-001
     */
    protected static function generateBatchNumber(): string
    {
        $date = now()->format('Ymd'); // 20250117
        $prefix = "BATCH-{$date}-";

        // Trouver le dernier batch du jour
        $lastBatch = static::where('batch_number', 'LIKE', $prefix.'%')
            ->orderByDesc('batch_number')
            ->first();

        if ($lastBatch) {
            // Extraire le numéro séquentiel
            $lastNumber = (int) substr($lastBatch->batch_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Formater avec zéros à gauche (001, 002, etc.)
        return $prefix.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // ===== RELATIONS =====

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function stockReceiptItem(): BelongsTo
    {
        return $this->belongsTo(StockReceiptItem::class, 'stock_receipt_item_id');
    }

    public function saleItemBatches(): HasMany
    {
        return $this->hasMany(SaleItemBatch::class, 'batch_id');
    }

    // ===== SCOPES =====

    /**
     * Batches avec stock disponible
     */
    public function scopeAvailable($query)
    {
        return $query->where('remaining_quantity', '>', 0);
    }

    /**
     * Batches validés (coûts finalisés)
     */
    public function scopeValidated($query)
    {
        return $query->where('cost_status', 'validated');
    }

    /**
     * Batches en attente de validation
     */
    public function scopePending($query)
    {
        return $query->where('cost_status', 'pending');
    }

    /**
     * Tri FIFO (First In, First Out)
     */
    public function scopeFifoOrder($query)
    {
        return $query->orderBy('received_date')->orderBy('id');
    }

    // ===== ACCESSORS & MUTATORS =====

    /**
     * Coût total du batch (quantité × coût unitaire)
     */
    public function getTotalBatchCostAttribute(): float
    {
        return $this->remaining_quantity * $this->total_unit_cost;
    }

    /**
     * Quantité déjà vendue
     */
    public function getSoldQuantityAttribute(): int
    {
        return $this->initial_quantity - $this->remaining_quantity;
    }

    /**
     * Pourcentage de stock restant
     */
    public function getStockPercentageAttribute(): float
    {
        if ($this->initial_quantity == 0) {
            return 0;
        }

        return ($this->remaining_quantity / $this->initial_quantity) * 100;
    }

    /**
     * Vérifie si le batch a du stock
     */
    public function hasStock(): bool
    {
        return $this->remaining_quantity > 0;
    }

    /**
     * Vérifie si les coûts sont validés
     */
    public function isCostValidated(): bool
    {
        return $this->cost_status === 'validated';
    }

    // ===== MÉTHODES MÉTIER =====

    /**
     * Valider les coûts du batch
     */
    public function validateCosts(int $userId): bool
    {
        return $this->update([
            'cost_status' => 'validated',
            'cost_validated_at' => now(),
            'cost_validated_by' => $userId,
        ]);
    }

    /**
     * Consommer une quantité du batch (lors d'une vente)
     */
    public function consume(int $quantity): bool
    {
        if ($quantity > $this->remaining_quantity) {
            throw new \Exception(
                "Quantité insuffisante dans le batch {$this->batch_number}. ".
                "Disponible: {$this->remaining_quantity}, Demandé: {$quantity}"
            );
        }

        return $this->decrement('remaining_quantity', $quantity);
    }
}
