<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\InstallmentTransaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle CreditInstallment - Échéance de paiement d'un crédit
 * 
 * Représente une échéance planifiée dans un plan de paiement.
 * Facultatif: un crédit peut ne pas avoir d'échéances (paiement libre).
 * 
 * Statuts:
 * - pending: En attente de paiement
 * - partial: Partiellement payé (amount_paid < amount_due)
 * - paid: Entièrement payé (amount_paid >= amount_due)
 * - overdue: En retard (due_date dépassée et non payé)
 * 
 * @property int $id
 * @property int $credit_id Crédit parent
 * @property int $installment_number Numéro d'échéance (1, 2, 3...)
 * @property \Carbon\Carbon $due_date Date d'échéance
 * @property float $amount_due Montant à payer
 * @property float $amount_paid Montant déjà payé
 * @property string $status pending, partial, paid, overdue
 * @property \Carbon\Carbon|null $paid_date Date de paiement complet
 * @property int|null $account_id Compte ayant reçu le paiement
 * @property PaymentMethod|null $payment_method Méthode de paiement utilisée
 * @property string|null $reference_number Référence de transaction
 * @property int|null $transaction_id Transaction bancaire associée
 * 
 * Relations:
 * @property-read Credit $credit
 * @property-read Account|null $account
 * @property-read AccountTransaction|null $transaction
 */
class CreditInstallment extends Model
{
    use HasFactory;

    // Désactive updated_at car non nécessaire
    const UPDATED_AT = null;

    protected $fillable = [
        'credit_id',
        'installment_number',
        'due_date',
        'amount_due',
        'amount_paid',
        'status',
        'paid_date',
    
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'installment_number' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Relations
     */

    /**
     * Crédit parent de cette échéance
     */
    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    /**
     * Transaction bancaire associée au paiement
     */
    public function installmentTransactions(): HasMany
    {
        return $this->hasMany(InstallmentTransaction::class, 'installment_id');
    }

    /**
     * Scopes pour requêtes et analyses
     */

    /**
     * Échéances en attente de paiement (pending ou partial)
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }

    /**
     * Échéances entièrement payées
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Échéances en retard
     * Inclut: status='overdue' OU status='pending' avec due_date dépassée
     */
    public function scopeOverdue($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'overdue')
              ->orWhere(function($q2) {
                  $q2->where('status', 'pending')
                     ->where('due_date', '<', now());
              });
        });
    }

    /**
     * Échéances à venir dans les X jours
     * 
     * @param int $days Nombre de jours (défaut: 7)
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'pending')
                    ->whereBetween('due_date', [
                        now(),
                        now()->addDays($days)
                    ]);
    }

    /**
     * Échéances d'un crédit spécifique (triées par numéro)
     */
    public function scopeOfCredit($query, int $creditId)
    {
        return $query->where('credit_id', $creditId)
                    ->orderBy('installment_number');
    }

    /**
     * Échéances par période d'échéance
     */
    public function scopeDueBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Échéances d'aujourd'hui
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
                    ->where('status', '!=', 'paid');
    }

    /**
     * Méthodes de vérification d'état
     */

    /**
     * Vérifie si l'échéance est entièrement payée
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Vérifie si l'échéance est en retard
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' 
            || ($this->status === 'pending' && $this->due_date < now());
    }

    /**
     * Vérifie si l'échéance est partiellement payée
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partial' 
            || ($this->amount_paid > 0 && $this->amount_paid < $this->amount_due);
    }

    /**
     * Méthodes de calcul
     */

    /**
     * Calcule le montant restant à payer
     */
    public function getRemainingAmount(): float
    {
        return max(0, $this->amount_due - $this->amount_paid);
    }

    /**
     * Calcule le pourcentage payé (0-100)
     */
    public function getPaymentPercentage(): float
    {
        if ($this->amount_due == 0) {
            return 0;
        }
        return ($this->amount_paid / $this->amount_due) * 100;
    }

    /**
     * Calcule le nombre de jours de retard
     * Retourne 0 si non en retard
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }

    /**
     * Calcule le nombre de jours avant échéance
     * Retourne 0 si déjà payé ou en retard
     */
    public function getDaysUntilDue(): int
    {
        if ($this->isPaid() || $this->isOverdue()) {
            return 0;
        }
        return max(0, now()->diffInDays($this->due_date, false));
    }

    /**
     * Méthodes d'accès aux relations parentes
     */

    /**
     * Accède au client via le crédit parent
     */
    public function getCustomer()
    {
        return $this->credit->customer;
    }

    /**
     * Accède à la vente via le crédit parent
     */
    public function getSale()
    {
        return $this->credit->sale;
    }

    /**
     * Obtient le libellé formaté de l'échéance
     * Ex: "Échéance #1/3 - Client: Rakoto Jean"
     */
    public function getFormattedLabel(): string
    {
        $totalInstallments = $this->credit->installments()->count();
        $customerName = $this->credit->customer->name ?? 'Client inconnu';
        
        return sprintf(
            "Échéance #%d/%d - %s",
            $this->installment_number,
            $totalInstallments,
            $customerName
        );
    }

    /**
     * Obtient une description du statut avec détails
     */
    public function getStatusDescription(): string
        {
            // =====================
            // PAYÉ
            // =====================
            if ($this->isPaid()) {
                if ($this->paid_date) {
                    return "Payée le " . $this->paid_date->format('d/m/Y');
                }

                // fallback sécurité
                return "Payée";
            }

            // =====================
            // EN RETARD
            // =====================
            if ($this->isOverdue()) {
                $days = $this->getDaysOverdue();
                return "En retard de {$days} jour(s)";
            }

            // =====================
            // PARTIEL
            // =====================
            if ($this->isPartiallyPaid()) {
                $percentage = round($this->getPaymentPercentage());
                return "Partiellement payée ({$percentage}%)";
            }

            // =====================
            // À VENIR
            // =====================
            $daysUntil = $this->getDaysUntilDue();

            if ($daysUntil === 0) {
                return "Échéance aujourd'hui";
            }

            if ($daysUntil === 1) {
                return "Échéance demain";
            }

            return "Échéance dans {$daysUntil} jours";
        }

}