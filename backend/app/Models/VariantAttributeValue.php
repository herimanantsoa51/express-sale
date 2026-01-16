<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VariantAttributeValue extends Model
{   

    public $timestamps = false;
    protected $fillable = [
        'variant_id',
        'attribute_value_id',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }

    // OPTIONNEL : lecture pratique
    public function attributeType()
    {
        return $this->hasOneThrough(
            AttributeType::class,
            AttributeValue::class,
            'id',
            'id',
            'attribute_value_id',
            'attribute_type_id'
        );
    }
}
