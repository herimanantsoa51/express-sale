<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashCountDenomination extends Model
{
    protected $fillable = [
        'cash_count_id',
        'denomination',
        'quantity',
        'subtotal',
    ];

    public function cashCount()
    {
        return $this->belongsTo(CashCount::class);
    }
}
