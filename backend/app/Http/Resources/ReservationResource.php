<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'reservation_date' => $this->reservation_date->toISOString(),
            'expiry_date' => $this->expiry_date->toISOString(),
            'total_amount' => (float) $this->total_amount,
            'deposit_amount' => (float) $this->deposit_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Calculés
            'payment_percentage' => round($this->getPaymentPercentage(), 2),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),
            'hours_until_expiry' => round($this->getHoursUntilExpiry(), 1),
            
            // ===== INFORMATIONS DU CLIENT =====
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'customer_number' => $this->customer->customer_number,
            ]),
            
            // ===== INFORMATIONS DE LA VENTE =====
            'sale_info' => $this->whenLoaded('sale', function () {
                return [
                    'sale_number' => $this->sale->sale_number,
                    'sale_date' => $this->sale->sale_date->toISOString(),
                    'subtotal' => (float) $this->sale->subtotal,
                    'discount_amount' => (float) $this->sale->discount_amount,
                    'total_amount' => (float) $this->sale->total_amount,
                    'payment_status' => $this->sale->payment_status,
                    
                    // Vendeur
                    'seller' => $this->sale->relationLoaded('user') ? [
                        'id' => $this->sale->user->id,
                        'name' => $this->sale->user->name,
                    ] : null,
                ];
            }),
            
            // ===== ITEMS DE VENTE =====
            'items' => $this->when(
                $this->relationLoaded('sale') && $this->sale->relationLoaded('items'),
                function () {
                    return $this->sale->items->map(function ($item) {
                        // Vérifier si les relations nécessaires sont chargées
                        $hasVariantLoaded = $item->relationLoaded('variant');
                        $hasProductLoaded = $hasVariantLoaded && $item->variant->relationLoaded('product');
                        $hasAttributesLoaded = $hasVariantLoaded && $item->variant->relationLoaded('attributeValues');
                        
                        // Déterminer l'image à utiliser
                        $imageUrl = null;
                        if ($hasVariantLoaded) {
                            // Priorité 1 : image du variant si elle existe
                            if (!empty($item->variant->image_path)) {
                                $imageUrl = $this->getImageUrl($item->variant->image_path);
                            } 
                            // Priorité 2 : image du produit parent
                            else if ($hasProductLoaded && !empty($item->variant->product->image_url)) {
                                $imageUrl = $this->getImageUrl($item->variant->product->image_url);
                            }
                        }
                        
                        return [
                            'id' => $item->id,
                            'quantity' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'line_total' => (float) $item->subtotal,
                            'image_url' => $imageUrl,
                            
                            'product' => $hasProductLoaded ? [
                                'id' => $item->variant->product->id,
                                'name' => $item->variant->product->name,
                            ] : null,
                            
                            'variant' => $hasVariantLoaded ? [
                                'id' => $item->variant->id,
                                'sku' => $item->variant->sku,
                                'attributes' => $hasAttributesLoaded ? 
                                    $item->variant->attributeValues->map(function ($attr) {
                                        // Vérifier si les relations d'attributs sont chargées
                                        if (!$attr->relationLoaded('attributeValue') || 
                                            !$attr->attributeValue->relationLoaded('attributeType')) {
                                            return null;
                                        }
                                        
                                        return [
                                            'attribute_type' => $attr->attributeValue->attributeType->display_name 
                                                ?? $attr->attributeValue->attributeType->name,
                                            'attribute_value' => $attr->attributeValue->value,
                                        ];
                                    })->filter()->values() 
                                    : [],
                            ] : null,
                        ];
                    });
                }
            ),
            
            // ===== TRANSACTIONS BANCAIRES =====
            'transactions' => $this->when(
                $this->relationLoaded('sale') && $this->sale->relationLoaded('transactions'),
                function () {
                    return $this->sale->transactions->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'amount' => (float) $transaction->amount,
                            'notes' => $transaction->notes,
                            'transaction_date' => $transaction->transaction_date->toISOString(),
                            'balance_before' => (float) $transaction->balance_before,
                            'balance_after' => (float) $transaction->balance_after,
                            
                            'account' => $transaction->relationLoaded('account') ? [
                                'id' => $transaction->account->id,
                                'name' => $transaction->account->name,
                            ] : null,
                            
                            'transaction_type' => $transaction->relationLoaded('transactionType') ? [
                                'id' => $transaction->transactionType->id,
                                'name' => $transaction->transactionType->name,
                            ] : null,
                            
                            'creator' => $transaction->relationLoaded('creator') ? [
                                'id' => $transaction->creator->id,
                                'name' => $transaction->creator->name,
                            ] : null,
                        ];
                    });
                }
            ),
        ];
    }
    
    // Méthode helper pour générer les URLs d'image
    private function getImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        // Si le chemin est déjà une URL complète
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Si c'est un chemin local, générer l'URL
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        
        // Par défaut, utiliser le storage public
        return asset('storage/' . ltrim($path, '/'));
    }
}