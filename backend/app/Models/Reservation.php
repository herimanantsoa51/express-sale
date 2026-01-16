<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Reservation - Réservation de produits
 * 
 * Statuts possibles:
 * - pending: Réservation créée, en attente d'acompte
 * - confirmed: Acompte reçu, articles réservés (reserved_quantity)
 * - partial_paid: Paiements supplémentaires reçus
 * - completed: Paiement final reçu, articles livrés
 * - expired: Date d'expiration dépassée
 * - cancelled: Réservation annulée
 * 
 * Flux typique:
 * 1. pending -> confirmed (après acompte)
 * 2. confirmed -> completed (après paiement final)
 * 
 * Le stock est géré automatiquement:
 * - confirmed: stock_quantity -> reserved_quantity
 * - completed: reserved_quantity libéré, vente finalisée
 * - expired/cancelled: reserved_quantity libéré
 * 
 * @property int $id
 * @property int $sale_id Vente associée
 * @property int $customer_id Client (obligatoire pour réservations)
 * @property \Carbon\Carbon $reservation_date Date de création
 * @property \Carbon\Carbon $expiry_date Date d'expiration
 * @property float $total_amount Montant total à payer
 * @property float $deposit_amount Acompte versé
 * @property float $remaining_amount Solde restant
 * @property string $status pending, confirmed, completed, expired, cancelled
 * @property string|null $cancellation_reason Raison d'annulation
 * @property \Carbon\Carbon|null $completed_at Date de finalisation
 * 
 * Relations:
 * @property-read Sale $sale
 * @property-read Customer $customer
 */
class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'reservation_date',
        'expiry_date',
        'total_amount',
        'deposit_amount',
        'remaining_amount',
        'status',
        'cancellation_reason',
        'completed_at',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'expiry_date' => 'datetime',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * Vente associée à la réservation
     * Contient les articles réservés dans sale->items
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Client ayant effectué la réservation
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scopes pour analyses et gestion
     */

    // Réservations actives (non expirées, non annulées, non complétées)
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'partial_paid'])
                    ->where('expiry_date', '>', now());
    }

    // Réservations expirées
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere(function($q) {
                        $q->whereIn('status', ['pending', 'confirmed'])
                          ->where('expiry_date', '<=', now());
                    });
    }

    // Réservations à confirmer (sans acompte)
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Réservations confirmées (avec acompte)
    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'partial_paid']);
    }

    // Réservations finalisées
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Réservations expirant bientôt (dans les X jours)
    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->whereIn('status', ['confirmed', 'partial_paid'])
                    ->whereBetween('expiry_date', [
                        now(),
                        now()->addDays($days)
                    ]);
    }

    // Réservations par client
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Méthodes utilitaires
     */

    // Vérifie si la réservation est active
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'partial_paid'])
            && $this->expiry_date > now();
    }

    // Vérifie si la réservation est expirée
    public function isExpired(): bool
    {
        return $this->status === 'expired' 
            || ($this->expiry_date <= now() && !$this->isCompleted());
    }

    // Vérifie si la réservation est complétée
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // Vérifie si la réservation est annulée
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Calcule le pourcentage payé
    public function getPaymentPercentage(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        return ($this->deposit_amount / $this->total_amount) * 100;
    }

    // Calcule les jours restants avant expiration
    public function getDaysUntilExpiry(): int
    {
        if ($this->isExpired() || $this->isCompleted()) {
            return 0;
        }
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    // Calcule les heures restantes avant expiration
    public function getHoursUntilExpiry(): float
    {
        if ($this->isExpired() || $this->isCompleted()) {
            return 0;
        }
        return max(0, now()->diffInHours($this->expiry_date, false));
    }

    // Accède aux articles réservés
    public function getItems()
    {
        return $this->sale->items;
    }

    /**
     * Vérifie si le stock de cette réservation a déjà été traité (libéré ou finalisé)
     */
    public function isStockProcessed(): bool
    {
        // Stock traité si réservation complétée ou annulée
        return in_array($this->status, ['completed', 'cancelled', 'expired']);
    }

    // Dans app/Models/Reservation.php
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->whereHas('sale', function ($q) use ($searchTerm) {
                $q->where('sale_number', 'like', '%' . $searchTerm . '%');
            })->orWhereHas('sale.customer', function ($q) use ($searchTerm) {
                $q->where('customer_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('name', 'like', '%' . $searchTerm . '%');
            });
        });
    }
    /**
     * Vérifie si le stock est encore bloqué (réservé)
     */
    public function isStockReserved(): bool
    {
        // Stock bloqué si réservation active (pending, confirmed, partial_paid)
        return in_array($this->status, ['pending', 'confirmed', 'partial_paid'])
            && !$this->isExpired();
    }
}