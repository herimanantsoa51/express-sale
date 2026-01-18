<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashCount extends Model
{
    protected $fillable = [
        'count_date',
        'total_amount',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'count_date' => 'date',
        'validated_at' => 'datetime',
    ];

    public function denominations(): HasMany
    {
        return $this->hasMany(CashCountDenomination::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

