<?php

// filepath: /home/christian/Programming/express-sale/backend/app/Models/ReservationItemBatch.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItemBatch extends Model
{
    protected $fillable = [
        'reservation_item_id',
        'batch_id',
        'quantity',
        'unit_cost',
        'unit_price_at_sale',
        'discount_amount',
        'profit',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price_at_sale' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function reservationItem(): BelongsTo
    {
        return $this->belongsTo(ReservationItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    /**
     * Scopes par statut
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
