<?php

namespace App\Models;

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReceiptItemRating extends Model
{
    protected $fillable = [
        'stock_receipt_item_id',
        'attribute_type_id',
        'attribute_conformity_rating',
        'quality_rating',
        'quality_notes',
        'rated_by'
    ];

    protected $casts = [
        'attribute_conformity_rating' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'created_at' => 'datetime'
    ];

    const UPDATED_AT = null;

    // Relations
    public function stockReceiptItem(): BelongsTo
    {
        return $this->belongsTo(StockReceiptItem::class);
    }

    public function attributeType(): BelongsTo
    {
        return $this->belongsTo(AttributeType::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    // Scopes
    public function scopeByAttribute($query, $attributeTypeId)
    {
        return $query->where('attribute_type_id', $attributeTypeId);
    }

    public function scopeHighQuality($query, $threshold = 7.0)
    {
        return $query->where('quality_rating', '>=', $threshold);
    }

    public function scopeLowQuality($query, $threshold = 5.0)
    {
        return $query->where('quality_rating', '<', $threshold);
    }

    // Méthodes
    /**
     * Calculer la note globale de ce rating
     * Prend en compte la qualité générale et la conformité de l'attribut
     */
    public function calculateOverallRating(): float
    {
        // Si c'est une évaluation d'attribut spécifique
        if ($this->attribute_type_id) {
            // 60% qualité générale + 40% conformité attribut
            return ($this->quality_rating * 0.6) + ($this->attribute_conformity_rating * 0.4);
        }
        
        // Si c'est juste une évaluation de qualité générale
        return $this->quality_rating;
    }

    /**
     * Vérifier si l'attribut est conforme (rating >= 7)
     */
    public function isConforming(): bool
    {
        return $this->attribute_conformity_rating >= 7.0;
    }

    /**
     * Obtenir le niveau de qualité textuel
     */
    public function getQualityLevelAttribute(): string
    {
        $rating = $this->quality_rating;
        
        if ($rating >= 9) return 'Excellent';
        if ($rating >= 7) return 'Bon';
        if ($rating >= 5) return 'Moyen';
        if ($rating >= 3) return 'Médiocre';
        return 'Mauvais';
    }

    /**
     * Obtenir le niveau de conformité textuel
     */
    public function getConformityLevelAttribute(): string
    {
        if (!$this->attribute_type_id) {
            return 'N/A';
        }

        $rating = $this->attribute_conformity_rating;
        
        if ($rating >= 9) return 'Totalement conforme';
        if ($rating >= 7) return 'Conforme';
        if ($rating >= 5) return 'Partiellement conforme';
        if ($rating >= 3) return 'Non conforme';
        return 'Totalement non conforme';
    }

    /**
     * Créer une évaluation complète pour un item
     */
    public static function createForItem(StockReceiptItem $item, array $ratingsData): array
    {
        $ratings = [];
        
        foreach ($ratingsData as $ratingData) {
            $ratings[] = self::create([
                'stock_receipt_item_id' => $item->id,
                'attribute_type_id' => $ratingData['attribute_type_id'] ?? null,
                'attribute_conformity_rating' => $ratingData['attribute_conformity_rating'] ?? 5.0,
                'quality_rating' => $ratingData['quality_rating'],
                'quality_notes' => $ratingData['quality_notes'] ?? null,
                'rated_by' => Auth::id()
            ]);
        }
        
        return $ratings;
    }
}