<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\FullUrl;

// app/Models/Category.php
class Category extends Model
{
    protected $fillable = [
        'name', 'description', 'parent_id', 'image_url', 'sort_order'
    ];
    protected $casts = [
        'image_url' => FullUrl::class,
    ];
    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products() {
        return $this->hasMany(Product::class, 'category_id');
    }
}