<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'code',
        'warehouse',
        'aisle',
        'shelf',
        'bin',
        'description',
        'capacity',
        'is_active'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relations
    public function variantLocations(): HasMany
    {
        return $this->hasMany(ProductVariantLocation::class, 'location_id');
    }

    public function stockMovementsFrom(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function stockMovementsTo(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByWarehouse($query, $warehouse)
    {
        return $query->where('warehouse', $warehouse);
    }

    // Méthodes utilitaires
    /**
     * Obtenir le chemin complet de la location
     */
    public function getFullPath(): string
    {
        $parts = array_filter([
            $this->warehouse,
            $this->aisle ? "Allée {$this->aisle}" : null,
            $this->shelf ? "Étagère {$this->shelf}" : null,
            $this->bin ? "Bac {$this->bin}" : null
        ]);

        return implode(' / ', $parts);
    }

    /**
     * Obtenir la quantité totale de stock dans cette location
     */
    public function getTotalQuantity(): int
    {
        return $this->variantLocations()->sum('quantity');
    }

    /**
     * Obtenir le nombre de variantes différentes dans cette location
     */
    public function getVariantCount(): int
    {
        return $this->variantLocations()->count();
    }

    /**
     * Vérifier si la location est pleine (si capacité définie)
     */
    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }
        return $this->getTotalQuantity() >= $this->capacity;
    }

    /**
     * Obtenir le pourcentage d'occupation
     */
    public function getOccupancyPercentage(): ?float
    {
        if (!$this->capacity) {
            return null;
        }
        return ($this->getTotalQuantity() / $this->capacity) * 100;
    }

    /**
     * Vérifier si la location peut être supprimée
     */
    public function canBeDeleted(): array
    {
        // Vérifier s'il y a du stock
        if ($this->variantLocations()->withStock()->exists()) {
            return [
                'can_delete' => false,
                'reason' => 'Cette location contient encore du stock'
            ];
        }

        // Vérifier s'il y a un historique de mouvements
        $hasMovements = $this->stockMovementsFrom()->exists() || 
                       $this->stockMovementsTo()->exists();
        
        if ($hasMovements) {
            return [
                'can_delete' => false,
                'reason' => 'Cette location a un historique de mouvements de stock'
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null
        ];
    }

    /**
     * Obtenir les statistiques de la location
     */
    public function getStatistics(): array
    {
        return [
            'total_quantity' => $this->getTotalQuantity(),
            'variant_count' => $this->getVariantCount(),
            'capacity' => $this->capacity,
            'occupancy_percentage' => $this->getOccupancyPercentage(),
            'is_full' => $this->isFull(),
            'movement_count' => $this->stockMovementsFrom()->count() + 
                               $this->stockMovementsTo()->count()
        ];
    }
}