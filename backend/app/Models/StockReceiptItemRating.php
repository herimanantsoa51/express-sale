<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReceiptItemRating extends Model
{
    protected $fillable = [
        'stock_receipt_item_id',
        'attribute_type_id',
        'conformity_rating',
        'notes',
        'rated_by'
    ];

    protected $casts = [
        'conformity_rating' => 'decimal:2',
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

    public function scopeConforming($query, $threshold = 7.0)
    {
        return $query->where('conformity_rating', '>=', $threshold);
    }

    public function scopeNonConforming($query, $threshold = 7.0)
    {
        return $query->where('conformity_rating', '<', $threshold);
    }

    // Méthodes
    /**
     * Vérifier si l'attribut est conforme (rating >= 7)
     */
    public function isConforming(): bool
    {
        return $this->conformity_rating >= 7.0;
    }

    /**
     * Obtenir le niveau de conformité textuel
     */
    public function getConformityLevelAttribute(): string
    {
        $rating = $this->conformity_rating;
        
        if ($rating >= 9) return 'Excellent';
        if ($rating >= 7) return 'Conforme';
        if ($rating >= 5) return 'Acceptable';
        if ($rating >= 3) return 'Non conforme';
        return 'Critique';
    }

    /**
     * Créer des évaluations pour un item
     */
    public static function createForItem(StockReceiptItem $item, array $ratingsData): array
    {
        $ratings = [];
        
        foreach ($ratingsData as $ratingData) {
            $ratings[] = self::create([
                'stock_receipt_item_id' => $item->id,
                'attribute_type_id' => $ratingData['attribute_type_id'],
                'conformity_rating' => $ratingData['conformity_rating'],
                'notes' => $ratingData['notes'] ?? null,
                'rated_by' => Auth::id()
            ]);
        }
        
        return $ratings;
    }

    
}