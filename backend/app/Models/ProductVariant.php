<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Casts\FullUrl;
class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price_adjustment',
        'stock_quantity',
        'reserved_quantity',
        'credit_quantity',
        'low_stock_threshold',
        'is_active',
        'image_path'
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'credit_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
        'image_path' => FullUrl::class,
    ];

    // Relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class, 'variant_id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class, 'variant_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProductVariantLocation::class, 'variant_id');
    }

    public function stockReceiptItems(): HasMany
    {
        return $this->hasMany(StockReceiptItem::class, 'variant_id');
    }

    // Accessors
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity - $this->credit_quantity);
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->product->base_price + $this->price_adjustment;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
    // app/Models/ProductVariant.php

    /**
     * Obtient la description du variant sous forme de chaîne
     * Ex: "Rouge, Taille L"
     */
    public function getAttributeValuesString(): string
    {
        return $this->attributeValues()
            ->with('attributeValue.attribute')
            ->get()
            ->map(function ($variantAttrValue) {
                return $variantAttrValue->attributeValue->value;
            })
            ->filter()
            ->implode(', ');
    }

    // Méthodes de gestion des emplacements
    /**
     * Recalculer le stock total à partir de tous les emplacements
     */
    public function recalculateTotalStock(): void
    {
        $totalQuantity = $this->locations()->sum('quantity');
        $this->update(['stock_quantity' => $totalQuantity]);
    }

    /**
     * Ajouter du stock à un emplacement spécifique
     */
    public function addStockToLocation(int $locationId, int $quantity, ?string $notes = null): ProductVariantLocation
    {
        $location = ProductVariantLocation::firstOrCreate(
            [
                'variant_id' => $this->id,
                'location_id' => $locationId
            ],
            ['quantity' => 0]
        );

        $location->addStock($quantity, $notes);

        return $location->fresh();
    }

    /**
     * Retirer du stock d'un emplacement spécifique
     */
    public function removeStockFromLocation(int $locationId, int $quantity): void
    {
        $location = $this->locations()
            ->where('location_id', $locationId)
            ->firstOrFail();

        $location->removeStock($quantity);
    }

    /**
     * Obtenir la répartition du stock par emplacement
     */
    public function getStockDistribution(): array
    {
        return $this->locations()
            ->with('location')
            ->get()
            ->map(function ($variantLocation) {
                return $variantLocation->getSummary();
            })
            ->toArray();
    }

    /**
     * Trouver le meilleur emplacement pour prélever du stock
     * (celui qui a le plus de stock disponible)
     */
    public function getBestPickLocation(int $requiredQuantity): ?ProductVariantLocation
    {
        return $this->locations()
            ->where('quantity', '>=', $requiredQuantity)
            ->orderBy('quantity', 'desc')
            ->first();
    }

    /**
     * Répartir une quantité sur plusieurs emplacements si nécessaire
     */
    public function allocateStock(int $requiredQuantity): array
    {
        $locations = $this->locations()
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'desc')
            ->get();

        $allocation = [];
        $remaining = $requiredQuantity;

        foreach ($locations as $location) {
            if ($remaining <= 0) break;

            $allocated = min($location->quantity, $remaining);
            $allocation[] = [
                'location_id' => $location->location_id,
                'location_name' => $location->location->name,
                'quantity' => $allocated
            ];

            $remaining -= $allocated;
        }

        if ($remaining > 0) {
            throw new \Exception("Stock insuffisant. Manque {$remaining} unités.");
        }

        return $allocation;
    }

    /**
     * Vérifier si le variant a suffisamment de stock
     */
    public function hasAvailableStock(int $quantity): bool
    {
        return $this->available_quantity >= $quantity;
    }

    /**
     * Réserver du stock
     */
    public function reserveStock(int $quantity): void
    {
        if (!$this->hasAvailableStock($quantity)) {
            throw new \Exception("Stock disponible insuffisant");
        }

        $this->increment('reserved_quantity', $quantity);
    }

    /**
     * Libérer du stock réservé
     */
    public function releaseReservedStock(int $quantity): void
    {
        $this->decrement('reserved_quantity', min($quantity, $this->reserved_quantity));
    }

    /**
     * Marquer du stock comme vendu à crédit
     */
    public function markAsCreditStock(int $quantity): void
    {
        if (!$this->hasAvailableStock($quantity)) {
            throw new \Exception("Stock disponible insuffisant");
        }

        $this->increment('credit_quantity', $quantity);
    }

    /**
     * Libérer du stock à crédit (quand le crédit est payé)
     */
    public function releaseCreditStock(int $quantity): void
    {
        $this->decrement('credit_quantity', min($quantity, $this->credit_quantity));
    }

    /**
     * Obtenir les statistiques du variant
     */
    public function getStatistics(): array
    {
        return [
            'stock' => [
                'total' => $this->stock_quantity,
                'available' => $this->available_quantity,
                'is_low_stock' => $this->is_low_stock,
                'threshold' => $this->low_stock_threshold
            ],
            'locations' => [
                'count' => $this->locations()->count(),
                'distribution' => $this->getStockDistribution()
            ],
            'price' => [
                'base' => (float) $this->product->base_price,
                'adjustment' => (float) $this->price_adjustment,
                'final' => (float) $this->final_price
            ]
        ];
    }

    
}