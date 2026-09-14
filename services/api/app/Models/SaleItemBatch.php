<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemBatch extends Model
{
    protected $fillable = [
        'sale_item_id',
        'batch_id',
        'quantity',
        'status',
        'unit_price_at_sale',
        'location_id',
        'discount_at_sale',
    ];

    protected $casts = [
        'unit_price_at_sale' => 'decimal:2',
        'discount_at_sale' => 'decimal:2',
    ];

    // ===== RELATIONS =====

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // ===== ACCESSORS (Calculs dynamiques) =====

    /**
     * Coût unitaire au moment de la vente (lu depuis le batch)
     */
    public function getUnitCostAttribute(): float
    {
        if (! $this->batch) {
            throw new \LogicException(
                "SaleItemBatch #{$this->id} n'a pas de batch associé"
            );
        }

        return (float) $this->batch->total_unit_cost;
    }

    /**
     * Coût total de cette portion
     */
    public function getTotalCostAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }

    /**
     * Revenu total de cette portion
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->quantity * $this->unit_price_at_sale;
    }

    /**
     * Bénéfice total de cette portion
     */
    public function getTotalProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /**
     * Marge bénéficiaire en %
     */
    public function getProfitMarginPercentAttribute(): float
    {
        if ($this->total_revenue == 0) {
            return 0;
        }

        return ($this->total_profit / $this->total_revenue) * 100;
    }

    /**
     * Bénéfice par unité
     */
    public function getUnitProfitAttribute(): float
    {
        return $this->unit_price_at_sale - $this->unit_cost;
    }

    // ===== MÉTHODES MÉTIER =====

    /**
     * Vérifie si cette vente est rentable
     */
    public function isProfitable(): bool
    {
        return $this->total_profit > 0;
    }

    /**
     * Retourne un résumé de la transaction
     */
    public function getSummary(): array
    {
        return [
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price_at_sale,
            'unit_profit' => $this->unit_profit,
            'total_cost' => $this->total_cost,
            'total_revenue' => $this->total_revenue,
            'total_profit' => $this->total_profit,
            'profit_margin_percent' => round($this->profit_margin_percent, 2),
            'batch_number' => $this->batch->batch_number,
        ];
    }
}
