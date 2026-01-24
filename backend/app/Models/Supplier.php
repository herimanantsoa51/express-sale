<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\FullUrl;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'coordinate_id',
        'wechat',
        'profile',
        'contact',
        'accessibility_notes',
        'reliability_score',
        'logo_url',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'reliability_score' => 'decimal:2',
        'logo_url' => FullUrl::class,
    ];

    protected $with = ['coordinate']; // Charger automatiquement la coordonnée
    
    // =====================
    // RELATIONS
    // =====================

    /**
     * Coordonnée (pays/ville)
     */
    public function coordinate()
    {
        return $this->belongsTo(Coordinate::class)->withDefault([
            'country' => '',
            'city' => '',
        ]);
    }

    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }

    public function stockReceiptItems()
    {
        return $this->hasManyThrough(
            StockReceiptItem::class,
            StockReceipt::class
        );
    }

    // =====================
    // MÉTHODES DE CALCUL
    // =====================

    /**
     * Mettre à jour la note de qualité du fournisseur après une évaluation
     * Calcule simplement la moyenne de tous les quality_rating des items reçus
     */
    public function updateReliabilityScore(): void
    {
        // Calculer la moyenne de qualité sur tous les items évalués
        $averageQuality = $this->stockReceiptItems()
            ->whereNotNull('quality_rating')
            ->avg('quality_rating');

        if ($averageQuality !== null) {
            $this->update([
                'reliability_score' => round($averageQuality, 2)
            ]);
        }
    }

    // =====================
    // ACCESSEURS
    // =====================

    /**
     * Obtenir le nombre total d'évaluations
     */
    public function getTotalRatingsCountAttribute(): int
    {
        return $this->stockReceiptItems()
            ->whereNotNull('quality_rating')
            ->count();
    }
}