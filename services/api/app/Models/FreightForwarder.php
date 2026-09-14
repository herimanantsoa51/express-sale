<?php

namespace App\Models;

use App\Casts\FullUrl;
use Illuminate\Database\Eloquent\Model;

class FreightForwarder extends Model
{
    protected $fillable = [
        'name',
        'coordinate_id',
        'logo_url',
        'contact',
        'service_score',
        'notes',
        'type',
        'is_active',
    ];

    protected $casts = [
        'service_score' => 'decimal:2',
        'is_active' => 'boolean',
        'logo_url' => FullUrl::class,
    ];

    protected $with = ['coordinate']; // Charger automatiquement la coordonnée

    protected $appends = ['logo_url'];
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

    /**
     * Réceptions de stock liées à ce transitaire
     */
    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }

    // =====================
    // SCOPES MÉTIER
    // =====================s

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
