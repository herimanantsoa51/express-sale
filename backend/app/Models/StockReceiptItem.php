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
        'notes'
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost_ariary' => 'decimal:2',
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

    /**
     * Calculer la note de qualité moyenne de cet item
     */
    public function getAverageQualityRating(): float
    {
        if ($this->ratings->isEmpty()) {
            return 0;
        }

        $totalWeightedQuality = 0;
        $totalWeight = 0;

        foreach ($this->ratings as $rating) {
            // Calculer la note globale de ce rating
            $overallRating = $rating->calculateOverallRating();
            
            // Pondérer par la valeur de l'item
            $weight = $this->unit_cost_ariary;
            $totalWeightedQuality += $overallRating * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalWeightedQuality / $totalWeight : 0;
    }

    /**
     * Obtenir le taux de conformité des attributs
     */
    public function getAttributeConformityRate(): array
    {
        $product = $this->variant->product;
        $requiredAttributes = $product->attributeTypes()->wherePivot('is_required', true)->get();
        
        if ($requiredAttributes->isEmpty()) {
            return [
                'total' => 0,
                'conforming' => 0,
                'rate' => 100,
                'details' => []
            ];
        }

        $totalAttributes = $requiredAttributes->count();
        $conformingAttributes = 0;
        $details = [];

        foreach ($requiredAttributes as $attributeType) {
            $rating = $this->ratings()
                ->where('attribute_type_id', $attributeType->id)
                ->first();

            $isConforming = $rating && $rating->attribute_conformity_rating >= 7.0;
            
            if ($isConforming) {
                $conformingAttributes++;
            }

            $details[] = [
                'attribute_type' => $attributeType->name,
                'display_name' => $attributeType->display_name,
                'rating' => $rating ? $rating->attribute_conformity_rating : 0,
                'is_conforming' => $isConforming
            ];
        }

        return [
            'total' => $totalAttributes,
            'conforming' => $conformingAttributes,
            'rate' => ($conformingAttributes / $totalAttributes) * 100,
            'details' => $details
        ];
    }

    /**
     * Ajouter ou mettre à jour une évaluation
     */
    public function addRating(array $data): StockReceiptItemRating
    {
        return $this->ratings()->create([
            'attribute_type_id' => $data['attribute_type_id'] ?? null,
            'attribute_conformity_rating' => $data['attribute_conformity_rating'] ?? 5.0,
            'quality_rating' => $data['quality_rating'],
            'quality_notes' => $data['quality_notes'] ?? null,
            'rated_by' => Auth::id()
        ]);
    }

    /**
     * Obtenir un résumé de la qualité
     */
    public function getQualitySummary(): array
    {
        $averageQuality = $this->getAverageQualityRating();
        $conformityRate = $this->getAttributeConformityRate();
        $quantityRate = $this->quantity_ordered > 0
            ? ($this->quantity_received / $this->quantity_ordered) * 100
            : 0;

        // Score global pondéré
        $overallScore = (
            $averageQuality * 0.5 +          // 50% qualité
            ($conformityRate['rate'] / 10) * 0.3 +  // 30% conformité attributs
            ($quantityRate / 10) * 0.2             // 20% quantité livrée
        );

        return [
            'average_quality_rating' => round($averageQuality, 2),
            'attribute_conformity_rate' => round($conformityRate['rate'], 2),
            'quantity_fulfillment_rate' => round($quantityRate, 2),
            'overall_score' => round($overallScore, 2),
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'quantity_variance' => $this->quantity_variance,
            'total_cost' => $this->total_cost,
            'ratings_count' => $this->ratings->count()
        ];
    }
}