<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model TransactionType
 * Représente les types de transactions (revenus, dépenses, transferts)
 */
class TransactionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'display_name',
        'category',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /* ===================== RELATIONS ===================== */

    /**
     * Un type peut avoir plusieurs transactions
     */
    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    /* ===================== SCOPES ===================== */

    /**
     * Filtre par catégorie
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Filtre par code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /* ===================== MÉTHODES ===================== */

    /**
     * Vérifie si c'est un revenu
     */
    public function isIncome(): bool
    {
        return $this->category === 'income';
    }

    /**
     * Vérifie si c'est une dépense
     */
    public function isExpense(): bool
    {
        return $this->category === 'expense';
    }

    /**
     * Vérifie si c'est un transfert
     */
    public function isTransfer(): bool
    {
        return $this->category === 'transfer';
    }

    /* ===================== MÉTHODES STATIQUES ===================== */

    /**
     * Récupère un type par son code
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * Données initiales pour le seeding
     */
    public static function getDefaultTypes(): array
    {
        return[];
    }
}