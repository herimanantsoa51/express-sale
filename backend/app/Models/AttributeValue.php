<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_type_id',
        'value',
        'sort_order'
    ];

    public $timestamps = false;

    // =====================
    // RELATIONS
    // =====================

    public function attributeType()
    {
        return $this->belongsTo(AttributeType::class);
    }

    // Relation avec les variantes via la table de jointure
    public function productVariants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_attribute_values',
            'attribute_value_id',
            'variant_id'
        );
    }

    // Alias plus court
    public function variants()
    {
        return $this->productVariants();
    }

    // Vérifie si utilisé par des variantes
    public function hasVariants()
    {
        return $this->variants()->exists();
    }
}