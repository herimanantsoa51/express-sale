<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Credit - Vente à crédit
 *
 * Statuts possibles:
 * - active: Crédit actif, en attente de paiements
 * - partial_paid: Au moins un paiement reçu
 * - completed: Entièrement payé
 * - overdue: Échéance(s) dépassée(s)
 * - defaulted: Défaut de paiement grave
 * - recovered: Marchandises récupérées suite à non-paiement
 *
 * Fonctionnement:
 * - À la création: stock_quantity -> credit_quantity
 * - Paiements: via CreditInstallment
 * - Finalisation: credit_quantity libéré
 * - Récupération: credit_quantity -> stock_quantity
 *
 * @property int $id
 * @property int $sale_id Vente associée
 * @property int $customer_id Client (obligatoire pour crédits)
 * @property float $total_amount Montant total du crédit
 * @property float $amount_paid Montant déjà payé
 * @property float $amount_due Montant restant à payer
 * @property \Carbon\Carbon $credit_date Date de création du crédit
 * @property \Carbon\Carbon|null $due_date Date d'échéance finale
 * @property \Carbon\Carbon|null $last_payment_date Date du dernier paiement
 * @property string $status active, partial_paid, completed, overdue, defaulted, recovered
 * @property int|null $default_account_id Compte par défaut pour paiements
 * @property string|null $notes Notes additionnelles
 *
 * Relations:
 * @property-read Sale $sale
 * @property-read Customer $customer
 * @property-read Collection|CreditInstallment[] $installments Échéances
 * @property-read Account|null $defaultAccount Compte bancaire par défaut
 */
class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'user_id',
        'credit_number',
        'credit_date',
        'due_date',
        'total_amount',
        'subtotal',
        'discount_amount',
        'discount_reason',
        'payment_method',
        'notes',
        'amount_paid',
        'amount_due',
        'status',
        'installment_count',
        'installment_frequency',
    ];

    protected $casts = [
        'credit_date' => 'date',
        'due_date' => 'date',
        'last_payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
    ];

    /**
     * Vente associée au crédit
     * Contient les articles vendus à crédit dans sale->items
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Client bénéficiant du crédit
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Utilisateur (vendeur) ayant créé le crédit
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Échéances de paiement du crédit
     * Peut être vide si paiement libre (sans plan d'échéances)
     */
    public function installments(): HasMany
    {
        return $this->hasMany(CreditInstallment::class);
    }

    /**
     * Articles du crédit (propres, indépendants de Sale depuis la migration
     * 2026_03_09 decouple_reservations_and_credits_from_sales).
     */
    public function items(): HasMany
    {
        return $this->hasMany(CreditItem::class);
    }

    /**
     * Transactions financières liées directement à ce crédit
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    /**
     * Scopes pour analyses et gestion
     */

    // Crédits actifs (non payés)
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'partial_paid', 'overdue']);
    }

    // Crédits finalisés
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public static function generateNumber(): string
    {
        $today = today();
        $dateStr = now()->format('Ymd');

        $lastCredit = self::whereDate('created_at', $today)
            ->orderByDesc('credit_number')
            ->lockForUpdate()
            ->first();

        if ($lastCredit && preg_match('/-(\d{4})$/', $lastCredit->credit_number, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = 1;
        }

        return sprintf('CRD-%s-%04d', $dateStr, $next);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('credit_number', 'ILIKE', "%{$searchTerm}%")
                ->orWhereHas('sale', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('sale_number', 'ILIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('customer', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('customer_number', 'ILIKE', "%{$searchTerm}%")
                        ->orWhere('name', 'ILIKE', "%{$searchTerm}%");
                });
        });
    }

    // Crédits en retard
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['active', 'partial_paid'])
                    ->whereHas('installments', function ($q2) {
                        $q2->where('status', 'overdue')
                            ->orWhere(function ($q3) {
                                $q3->where('status', 'pending')
                                    ->where('due_date', '<', now());
                            });
                    });
            });
    }

    // Crédits par client
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Crédits avec échéance proche (X jours)
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereIn('status', ['active', 'partial_paid'])
            ->whereHas('installments', function ($q) use ($days) {
                $q->where('status', 'pending')
                    ->whereBetween('due_date', [
                        now(),
                        now()->addDays($days),
                    ]);
            });
    }

    // Crédits par période de création
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('credit_date', [$startDate, $endDate]);
    }

    /**
     * Méthodes utilitaires
     */

    // Vérifie si le crédit est actif
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'partial_paid', 'overdue']);
    }

    // Vérifie si le crédit est finalisé
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // Vérifie si le crédit est en retard
    public function isOverdue(): bool
    {
        if ($this->status === 'overdue') {
            return true;
        }

        return $this->installments()
            ->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'pending')
                            ->where('due_date', '<', now());
                    });
            })
            ->exists();
    }

    // Calcule le pourcentage payé
    public function getPaymentPercentage(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->amount_paid / $this->total_amount) * 100;
    }

    // Calcule le montant total des échéances en retard
    public function getOverdueAmount(): float
    {
        return $this->installments()
            ->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'pending')
                            ->where('due_date', '<', now());
                    });
            })
            ->sum('amount_due');
    }

    // Nombre de jours de retard (basé sur la prochaine échéance)
    public function getDaysOverdue(): int
    {
        $overdueInstallment = $this->installments()
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->first();

        if (! $overdueInstallment) {
            return 0;
        }

        return now()->diffInDays($overdueInstallment->due_date);
    }

    // Prochaine échéance à payer
    public function getNextInstallment()
    {
        return $this->installments()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();
    }

    // Nombre d'échéances restantes
    public function getRemainingInstallments(): int
    {
        return $this->installments()
            ->whereIn('status', ['pending', 'overdue'])
            ->count();
    }

    // Accède aux articles du crédit (propres, indépendants de Sale)
    public function getItems()
    {
        return $this->items;
    }

    // Historique des paiements
    public function getPaymentHistory()
    {
        return $this->installments()
            ->where('amount_paid', '>', 0)
            ->orderBy('paid_date')
            ->get();
    }

    protected static function booted()
    {
        // Quand un crédit est annulé, annuler la vente
        static::updating(function ($credit) {
            if ($credit->isDirty('status') && $credit->status === 'cancelled') {
                $credit->sale()->update(['status' => 'CANCELLED']);
            }
        });

        // Quand un crédit est complété, marquer la vente comme payée
        static::updating(function ($credit) {
            if ($credit->isDirty('status') && $credit->status === 'completed') {
                $credit->sale()->update(['payment_status' => 'paid']);
            }
        });
    }
}
