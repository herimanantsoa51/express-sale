<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Model Account
 * Représente un compte monétaire (Cash, Mobile Money, Banque)
 */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_type_id',
        'name',
        'account_number',
        'initial_balance',
        'current_balance',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ===================== RELATIONS ===================== */

    /**
     * Type de compte
     */
    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * Créateur du compte
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Toutes les transactions du compte
     */
    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class)->latest('transaction_date');
    }

    /**
     * Transactions où ce compte est la source
     */
    public function outgoingTransactions()
    {
        return $this->hasMany(AccountTransaction::class)
            ->whereHas('transactionType', function ($q) {
                $q->whereIn('category', ['expense', 'transfer']);
            });
    }

    /**
     * Transactions où ce compte est la destination
     */
    public function incomingTransactions()
    {
        return $this->hasMany(AccountTransaction::class)
            ->whereHas('transactionType', function ($q) {
                $q->where('category', 'income');
            });
    }

    /**
     * Ventes payées via ce compte
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Crédits payés via ce compte
     */
    public function credits()
    {
        return $this->hasMany(Credit::class, 'default_account_id');
    }

    /* ===================== SCOPES ===================== */

    /**
     * Filtre les comptes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtre par type de compte
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('account_type_id', $typeId);
    }

    /**
     * Tri par solde
     */
    public function scopeOrderByBalance($query, $direction = 'desc')
    {
        return $query->orderBy('current_balance', $direction);
    }

    /* ===================== MÉTHODES ===================== */

    /**
     * Vérifie si le compte a un solde suffisant
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->current_balance >= $amount;
    }

    /**
     * Met à jour le solde du compte
     * NOTE: Le solde est géré automatiquement par les fonctions PostgreSQL
     * Cette méthode est conservée pour compatibilité mais non utilisée
     */
    public function updateBalance(float $amount, string $operation = 'add'): void
    {
        // Le solde est mis à jour automatiquement par les fonctions PostgreSQL
        // record_direct_sale_payment, record_supplier_payment, etc.
    }

    /**
     * Récupère le solde à une date donnée
     */
    public function getBalanceAtDate(string $date): float
    {
        $transaction = $this->transactions()
            ->where('transaction_date', '<=', $date)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $transaction ? (float) $transaction->balance_after : (float) $this->initial_balance;
    }

    /**
     * Statistiques du compte
     */
    public function getStats(?array $dateRange = null): array
    {
        $query = $this->transactions();

        if ($dateRange) {
            $query->whereBetween('transaction_date', $dateRange);
        }

        $income = (clone $query)
            ->whereHas('transactionType', fn ($q) => $q->where('category', 'income'))
            ->sum('amount');

        $expense = (clone $query)
            ->whereHas('transactionType', fn ($q) => $q->where('category', 'expense'))
            ->sum('amount');

        $transferIn = (clone $query)
            ->whereHas('transactionType', fn ($q) => $q->where('category', 'transfer'))
            ->whereNotNull('related_account_id')
            ->where('amount', '>', 0)
            ->sum('amount');

        $transferOut = (clone $query)
            ->whereHas('transactionType', fn ($q) => $q->where('category', 'transfer'))
            ->whereNotNull('related_account_id')
            ->where('amount', '<', 0)
            ->sum('amount');

        return [
            'current_balance' => (float) $this->current_balance,
            'income' => (float) $income,
            'expense' => (float) abs($expense),
            'transfer_in' => (float) $transferIn,
            'transfer_out' => (float) abs($transferOut),
            'net_flow' => (float) ($income - abs($expense) + $transferIn - abs($transferOut)),
        ];
    }

    /* ===================== ÉVÉNEMENTS ===================== */

    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            // Initialiser current_balance avec initial_balance
            if (! isset($model->current_balance)) {
                $model->current_balance = $model->initial_balance ?? 0;
            }
        });
    }
}
