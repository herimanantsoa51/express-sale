<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle InstallmentTransaction
 * Liaison entre une échéance de crédit et ses transactions de paiement
 * Permet de tracker plusieurs paiements partiels pour une même échéance
 */
class InstallmentTransaction extends Model
{
    protected $fillable = [
        'installment_id',
        'transaction_id',
        'amount',
        'payment_date',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(CreditInstallment::class, 'installment_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class, 'transaction_id');
    }
}
