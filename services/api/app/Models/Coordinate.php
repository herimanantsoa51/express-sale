<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinate extends Model
{
    protected $fillable = [
        'country',
        'city',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_location'];

    // =====================
    // RELATIONS
    // =====================

    /**
     * Fournisseurs utilisant cette coordonnée
     */
    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Transitaires utilisant cette coordonnée
     */
    public function freightForwarders()
    {
        return $this->hasMany(FreightForwarder::class);
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    // =====================
    // ACCESSEURS
    // =====================

    /**
     * Retourne la localisation complète formatée
     */
    public function getFullLocationAttribute()
    {
        return "{$this->city}, {$this->country}";
    }
}
