<?php

namespace App\Models;
use App\Casts\FullUrl;
use Illuminate\Database\Eloquent\Model;
// app/Models/Product.php
class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'category_id', 'subcategory_id',
         'is_active','base_price','image_url'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'image_url' => FullUrl::class,
        'base_price' => 'decimal:2'
    ];
    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory() {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributeDefinitions()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function attributeTypes()
    {
        return $this->belongsToMany(
            AttributeType::class,
            'product_attributes'
        )->withPivot('is_required');
    }


    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
}