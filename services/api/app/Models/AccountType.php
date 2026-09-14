<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model AccountType
 * Représente les types de comptes monétaires (Cash, Mobile Money, Banque)
 */
class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'display_name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /* ===================== RELATIONS ===================== */

    /**
     * Un type de compte peut avoir plusieurs comptes
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /* ===================== SCOPES ===================== */

    /**
     * Recherche par code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /* ===================== MÉTHODES STATIQUES ===================== */

    /**
     * Récupère un type de compte par son code
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
        return [
            [
                'code' => 'CASH',
                'name' => 'cash',
                'display_name' => 'Espèces',
                'description' => 'Compte en espèces (liquide)',
            ],
            [
                'code' => 'MOBILE_MONEY',
                'name' => 'mobile_money',
                'display_name' => 'Mobile Money',
                'description' => 'Compte Mobile Money (MVola, Orange Money, etc.)',
            ],
            [
                'code' => 'BANK',
                'name' => 'bank',
                'display_name' => 'Banque',
                'description' => 'Compte bancaire',
            ],
        ];
    }
}
