<?php

// app/Models/ProductAttribute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_type_id',
        'is_required',
    ];

    public $timestamps = false; // ← IMPORTANT : votre table n'a pas timestamps

    protected $casts = [
        'is_required' => 'boolean',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeType()
    {
        return $this->belongsTo(AttributeType::class);
    }
}
