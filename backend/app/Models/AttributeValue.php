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
}
