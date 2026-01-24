<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\SaleStatus;

/**
 * Modèle Sale - Représente une vente dans le système
 * 
 * Types de vente possibles:
 * - immediate: Vente payée immédiatement (stock décrémenté)
 * - credit: Vente à crédit (stock -> credit_quantity)
 * - reservation: Réservation avec acompte (stock -> reserved_quantity)
 * 
 * Statuts de paiement:
 * - pending: En attente de paiement
 * - partial: Partiellement payé (réservations, crédits)
 * - paid: Entièrement payé
 * - cancelled: Annulé
 * 
 * @property int $id
 * @property string $sale_number Numéro unique de vente (ex: VNT-20250105-0001)
 * @property int|null $customer_id Client (null pour vente au comptant)
 * @property int $user_id Vendeur ayant effectué la vente
 * @property \Carbon\Carbon $sale_date Date et heure de la vente
 * @property SaleType $sale_type Type: immediate, credit, reservation
 * @property float $subtotal Sous-total avant remise
 * @property float $discount_amount Montant de la remise
 * @property string|null $discount_reason Raison de la remise
 * @property float $total_amount Montant total après remise
 * @property PaymentStatus $payment_status Statut du paiement
 * @property PaymentMethod|null $payment_method Méthode: cash, mobile_money, bank_transfer, mixed
 * @property string|null $notes Notes additionnelles
 * 
 * Relations:
 * @property-read Customer|null $customer
 * @property-read User $user
 * @property-read Collection|SaleItem[] $items Articles de la vente
 * @property-read Credit|null $credit Crédit associé (si sale_type = credit)
 * @property-read Reservation|null $reservation Réservation associée (si sale_type = reservation)
 * @property-read Collection|AccountTransaction[] $transactions Transactions bancaires
 */
class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_number',
        'customer_id',
        'user_id',
        'sale_date',
        'sale_type',
        'subtotal',
        'discount_amount',
        'discount_reason',
        'total_amount',
        'payment_status',
        'payment_method',
        'notes',
        'status',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sale_type' => SaleType::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'status'=> SaleStatus::class
    ];

    /**
     * Client de la vente (optionnel pour ventes au comptant)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Vendeur ayant effectué la vente
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Articles vendus dans cette vente
     * Contient les détails: variant, quantité, prix unitaire, sous-total
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Crédit associé (uniquement si sale_type = 'credit')
     * Contient: échéances, paiements, statut du crédit
     */
    public function credit(): HasOne
    {
        return $this->hasOne(Credit::class);
    }

    /**
     * Réservation associée (uniquement si sale_type = 'reservation')
     * Contient: dates d'expiration, acomptes, statut
     */
    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }

    /**
     * Transactions bancaires liées à cette vente
     * Inclut: paiements initiaux, acomptes, paiements de crédit
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }



    // pour sale Immediate
    public function accountTransaction()
    {
        return $this->hasOne(AccountTransaction::class, 'sale_id');
    }


    /**
     * Scopes pour analyses courantes
     */

    // Ventes d'aujourd'hui
    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    // Ventes par période
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    // Ventes par type
    public function scopeOfType($query, SaleType $type)
    {
        return $query->where('sale_type', $type);
    }

    // Ventes payées
    public function scopePaid($query)
    {
        return $query->where('payment_status', PaymentStatus::PAID);
    }

    // Ventes en attente
    public function scopePending($query)
    {
        return $query->whereIn('payment_status', [
            PaymentStatus::PENDING,
            PaymentStatus::PARTIAL
        ]);
    }

    // Ventes par vendeur
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Méthodes utilitaires pour analyses
     */

    // Vérifie si la vente est entièrement payée
    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::PAID;
    }

    // Vérifie si c'est une vente à crédit
    public function isCredit(): bool
    {
        return $this->sale_type === SaleType::CREDIT;
    }

    // Vérifie si c'est une réservation
    public function isReservation(): bool
    {
        return $this->sale_type === SaleType::RESERVATION;
    }

    // Calcule le montant restant à payer
    public function getRemainingAmount(): float
    {
        if ($this->isPaid()) {
            return 0;
        }

        // Pour les crédits
        if ($this->credit) {
            return (float) $this->credit->amount_due;
        }

        // Pour les réservations
        if ($this->reservation) {
            return (float) $this->reservation->remaining_amount;
        }

        return (float) $this->total_amount;
    }

    // Calcule le montant déjà payé
    public function getPaidAmount(): float
    {
        if ($this->credit) {
            return (float) $this->credit->amount_paid;
        }

        if ($this->reservation) {
            return (float) $this->reservation->deposit_amount;
        }

        return $this->isPaid() ? (float) $this->total_amount : 0;
    }
    protected static function booted()
    {
        static::creating(function ($sale) {
            $sale->status = SaleStatus::CONFIRMED;
        });
    }
    
}