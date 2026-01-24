<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle SaleItem - Article d'une vente
 * 
 * Représente un article (variant de produit) vendu dans une vente.
 * Stocke la quantité, le prix unitaire et le sous-total au moment de la vente.
 * 
 * Important: Les prix sont figés au moment de la vente pour garder
 * l'historique exact même si les prix produits changent ultérieurement.
 * 
 * @property int $id
 * @property int $sale_id Vente parente
 * @property int $variant_id Variant du produit vendu
 * @property int $quantity Quantité vendue (toujours > 0)
 * @property float $unit_price Prix unitaire au moment de la vente
 * @property float $subtotal Total ligne = quantity × unit_price
 * @property \Carbon\Carbon $created_at
 * 
 * Relations:
 * @property-read Sale $sale
 * @property-read ProductVariant $variant
 */
class SaleItem extends Model
{
    use HasFactory;

    // Désactive updated_at car non nécessaire pour les lignes de vente
    public $timestamps = false;
    
    protected $fillable = [
        'sale_id',
        'variant_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Vente parente de cet article
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Variant de produit vendu
     * Permet d'accéder aux informations du produit et ses attributs
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function saleItemBatches()
    {
        return $this->hasMany(SaleItemBatch::class);
    }


    /**
     * Scopes pour analyses
     */

    // Articles d'un produit spécifique
    public function scopeOfProduct($query, int $productId)
    {
        return $query->whereHas('variant', function($q) use ($productId) {
            $q->where('product_id', $productId);
        });
    }

    // Articles vendus dans une période
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereHas('sale', function($q) use ($startDate, $endDate) {
            $q->whereBetween('sale_date', [$startDate, $endDate]);
        });
    }

    /**
     * Méthodes utilitaires
     */

    // Calcule le prix total de la ligne
    public function calculateSubtotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    // Accède aux informations du produit principal
    public function getProduct()
    {
        return $this->variant->product;
    }

    // Obtient le nom complet de l'article (produit + variant)
    public function getFullName(): string
    {
        $product = $this->getProduct();
        $variant = $this->variant;
        
        $variantDescription = $variant->getAttributeValuesString();
        
        return $variantDescription 
            ? "{$product->name} ({$variantDescription})"
            : $product->name;
    }
}