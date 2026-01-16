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
    // app/Models/Supplier.php
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
    // ACCESSEURS
    // =====================

}