<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle ReservationDeposit
 * Liaison entre une réservation et ses acomptes (paiements multiples)
 */
class ReservationDeposit extends Model
{
    protected $fillable = [
        'reservation_id',
        'transaction_id',
        'amount',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class, 'transaction_id');
    }
}
