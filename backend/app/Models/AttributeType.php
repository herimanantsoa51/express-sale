<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeType extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'input_type'
    ];

    public $timestamps = false; 
    // seulement created_at dans la table

    // =====================
    // RELATIONS
    // =====================

    // Valeurs possibles (ex: 38, 39, 40)
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    // Attributs liés aux produits
    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }
}
