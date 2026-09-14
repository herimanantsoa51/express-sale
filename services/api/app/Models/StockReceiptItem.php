<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class StockReceiptItem extends Model
{
    protected $fillable = [
        'stock_receipt_id',
        'variant_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost_ariary',
        'quality_rating',
        'quality_notes',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost_ariary' => 'decimal:2',
        'quality_rating' => 'decimal:2',
    ];

    public $timestamps = false;

    // Relations
    public function stockReceipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(StockReceiptItemRating::class);
    }

    // Accessors
    public function getTotalCostAttribute(): float
    {
        return $this->quantity_received * $this->unit_cost_ariary;
    }

    public function getQuantityVarianceAttribute(): int
    {
        return $this->quantity_received - $this->quantity_ordered;
    }

    public function getQuantityVariancePercentageAttribute(): float
    {
        if ($this->quantity_ordered == 0) {
            return 0;
        }

        return ($this->quantity_variance / $this->quantity_ordered) * 100;
    }

    public function getQualityLevelAttribute(): string
    {
        if (! $this->quality_rating) {
            return 'Non évalué';
        }

        $rating = $this->quality_rating;

        if ($rating >= 9) {
            return 'Excellent';
        }
        if ($rating >= 7) {
            return 'Bon';
        }
        if ($rating >= 5) {
            return 'Moyen';
        }
        if ($rating >= 3) {
            return 'Médiocre';
        }

        return 'Mauvais';
    }

    /**
     * Obtenir le taux de conformité des attributs
     */
    public function getConformityRate(): array
    {
        $product = $this->variant->product;

        $requiredAttributes = $product->attributeTypes()
            ->wherePivot('is_required', true)
            ->get();

        if ($requiredAttributes->isEmpty()) {
            return [
                'total' => 0,
                'conforming' => 0,
                'rate' => 100,
                'details' => [],
            ];
        }

        $totalAttributes = $requiredAttributes->count();
        $conformingAttributes = 0;
        $details = [];

        $conformitySum = 0;
        $ratedCount = 0;

        foreach ($requiredAttributes as $attributeType) {
            $rating = $this->ratings
                ->firstWhere('attribute_type_id', $attributeType->id);

            $isConforming = $rating?->isConforming() ?? false;

            if ($isConforming) {
                $conformingAttributes++;
            }

            if ($rating) {
                $conformitySum += $rating->conformity_rating;
                $ratedCount++;
            }

            $details[] = [
                'attribute_type_id' => $attributeType->id,
                'attribute_name' => $attributeType->name,
                'display_name' => $attributeType->display_name,
                'rating' => $rating?->conformity_rating,
                'conformity_level' => $rating?->conformity_level ?? 'Non évalué',
                'is_conforming' => $isConforming,
                'notes' => $rating?->notes,
            ];
        }

        $average = $ratedCount > 0
            ? ($conformitySum / $ratedCount)
            : 0;

        return [
            'total' => $totalAttributes,
            'conforming' => $conformingAttributes,
            'rate' => round($average * 10, 2), // note /10 → %
            'details' => $details,
        ];
    }

    /**
     * Ajouter une évaluation d'attribut
     */
    public function addAttributeRating(int $attributeTypeId, float $conformityRating, ?string $notes = null): StockReceiptItemRating
    {
        return $this->ratings()->create([
            'attribute_type_id' => $attributeTypeId,
            'conformity_rating' => $conformityRating,
            'notes' => $notes,
            'rated_by' => Auth::id(),
        ]);
    }

    /**
     * Mettre à jour la qualité globale de l'item
     */
    public function updateQuality(float $qualityRating, ?string $qualityNotes = null): void
    {
        $this->update([
            'quality_rating' => $qualityRating,
            'quality_notes' => $qualityNotes,
        ]);
    }

    /**
     * Obtenir un résumé complet
     */
    public function getSummary(): array
    {
        $conformity = $this->getConformityRate();
        $quantityRate = $this->quantity_ordered > 0
            ? ($this->quantity_received / $this->quantity_ordered) * 100
            : 0;

        return [
            'quality_rating' => $this->quality_rating,
            'quality_level' => $this->quality_level,
            'quality_notes' => $this->quality_notes,
            'conformity_rate' => round($conformity['rate'], 2),
            'conforming_attributes' => $conformity['conforming'],
            'total_attributes' => $conformity['total'],
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'quantity_variance' => $this->quantity_variance,
            'quantity_rate' => round($quantityRate, 2),
            'total_cost' => $this->total_cost,
            'has_ratings' => $this->ratings->isNotEmpty(),
        ];
    }

    public function getAverageQualityRating()
    {
        return $this->quality_rating;
    }
}
