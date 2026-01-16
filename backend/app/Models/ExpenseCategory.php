<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model ExpenseCategory
 * Représente les catégories de dépenses opérationnelles
 */
class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /* ===================== RELATIONS ===================== */

    /**
     * Une catégorie peut avoir plusieurs transactions
     */
    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }

    /* ===================== SCOPES ===================== */

    /**
     * Filtre les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ===================== MÉTHODES STATIQUES ===================== */

    /**
     * Données initiales pour le seeding
     */
    public static function getDefaultCategories(): array
    {
        return [
            [
                'name' => 'Loyer',
                'description' => 'Loyer du magasin/entrepôt',
                'icon' => 'building',
                'is_active' => true,
            ],
            [
                'name' => 'Électricité',
                'description' => 'Factures d\'électricité',
                'icon' => 'zap',
                'is_active' => true,
            ],
            [
                'name' => 'Eau',
                'description' => 'Factures d\'eau',
                'icon' => 'droplet',
                'is_active' => true,
            ],
            [
                'name' => 'Internet/Téléphone',
                'description' => 'Frais de communication',
                'icon' => 'phone',
                'is_active' => true,
            ],
            [
                'name' => 'Salaires',
                'description' => 'Rémunération du personnel',
                'icon' => 'users',
                'is_active' => true,
            ],
            [
                'name' => 'Transport',
                'description' => 'Frais de transport et carburant',
                'icon' => 'truck',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Publicité et promotion',
                'icon' => 'megaphone',
                'is_active' => true,
            ],
            [
                'name' => 'Fournitures',
                'description' => 'Fournitures de bureau et magasin',
                'icon' => 'package',
                'is_active' => true,
            ],
            [
                'name' => 'Entretien',
                'description' => 'Maintenance et réparations',
                'icon' => 'wrench',
                'is_active' => true,
            ],
            [
                'name' => 'Taxes',
                'description' => 'Impôts et taxes',
                'icon' => 'file-text',
                'is_active' => true,
            ],
            [
                'name' => 'Assurance',
                'description' => 'Assurances diverses',
                'icon' => 'shield',
                'is_active' => true,
            ],
            [
                'name' => 'Autres',
                'description' => 'Autres dépenses non catégorisées',
                'icon' => 'more-horizontal',
                'is_active' => true,
            ],
        ];
    }
}