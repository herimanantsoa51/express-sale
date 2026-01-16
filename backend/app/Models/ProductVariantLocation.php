<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProductVariantLocation extends Model
{
    protected $fillable = [
        'variant_id',
        'location_id',
        'quantity',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relations
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // Scopes
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    // Méthodes
    /**
     * Ajouter du stock à cet emplacement
     */
    public function addStock(int $quantity, ?string $notes = null): void
    {
        $this->increment('quantity', $quantity);
        
        if ($notes) {
            $this->update(['notes' => $notes]);
        }

        // Mettre à jour le stock total du variant
        $this->variant->recalculateTotalStock();
    }

    /**
     * Retirer du stock de cet emplacement
     */
    public function removeStock(int $quantity): void
    {
        if ($quantity > $this->quantity) {
            throw new \Exception("Quantité insuffisante à l'emplacement {$this->location->name}");
        }

        $this->decrement('quantity', $quantity);
        
        // Mettre à jour le stock total du variant
        $this->variant->recalculateTotalStock();
    }

    /**
     * Déplacer du stock vers un autre emplacement
     */
    public function moveStock(int $toLocationId, int $quantity, ?string $notes = null): ProductVariantLocation
    {
        if ($quantity > $this->quantity) {
            throw new \Exception("Quantité insuffisante pour le déplacement");
        }

        DB::transaction(function () use ($toLocationId, $quantity, $notes) {
            // Retirer de l'emplacement actuel
            $this->decrement('quantity', $quantity);

            // Ajouter à l'emplacement destination
            $destination = ProductVariantLocation::firstOrCreate(
                [
                    'variant_id' => $this->variant_id,
                    'location_id' => $toLocationId
                ],
                ['quantity' => 0]
            );

            $destination->increment('quantity', $quantity);

            if ($notes) {
                $destination->update(['notes' => $notes]);
            }
        });

        return ProductVariantLocation::where('variant_id', $this->variant_id)
            ->where('location_id', $toLocationId)
            ->first();
    }

    /**
     * Obtenir un résumé de l'emplacement
     */
    public function getSummary(): array
    {
        return [
            'location' => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'code' => $this->location->code,
                'warehouse' => $this->location->warehouse,
                'full_path' => $this->location->getFullPath()
            ],
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'last_updated' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}