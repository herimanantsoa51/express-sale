<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->whenLoaded('sale', fn() => $this->sale->sale_number),
            
            // ===== INFORMATIONS FINANCIÈRES =====
            'subtotal' => $this->whenLoaded('sale', fn() => (float) $this->sale->subtotal),
            'discount_amount' => $this->whenLoaded('sale', fn() => (float) $this->sale->discount_amount),
            'discount_reason' => $this->whenLoaded('sale', fn() => $this->sale->discount_reason),
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            
            // ===== DATES =====
            'credit_date' => $this->credit_date->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'last_payment_date' => $this->last_payment_date?->toISOString(),
            
            // ===== STATUT ET NOTES =====
            'status' => $this->status,
            'notes' => $this->notes,
            'updated_at' => $this->updated_at->toISOString(),
            
            // ===== CALCULS =====
            'payment_percentage' => round($this->getPaymentPercentage(), 2),
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->getDaysOverdue(),
            'remaining_installments' => $this->getRemainingInstallments(),
            
            // ===== RELATIONS =====
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            
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
                            // Note: votre ProductVariant a 'image_path'
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
                                'product_image_url' => !empty($item->variant->product->image_url) 
                                    ? $this->getImageUrl($item->variant->product->image_url)
                                    : null,
                            ] : null,
                            
                            'variant' => $hasVariantLoaded ? [
                                'id' => $item->variant->id,
                                'sku' => $item->variant->sku,
                                'variant_image_url' => !empty($item->variant->image_path) 
                                    ? $this->getImageUrl($item->variant->image_path)
                                    : null,
                                'attributes' => $hasAttributesLoaded ? 
                                    $item->variant->attributeValues->map(function ($attr) {
                                        // Vérifier si les relations d'attributs sont chargées
                                        if (!$attr->relationLoaded('attributeValue') || 
                                            !$attr->attributeValue->relationLoaded('attributeType')) {
                                            return null;
                                        }
                                        
                                        return [
                                            'attribute_type_id' => $attr->attributeValue->attributeType->id,
                                            'attribute_type' => $attr->attributeValue->attributeType->display_name 
                                                ?? $attr->attributeValue->attributeType->name,
                                            'attribute_value_id' => $attr->attributeValue->id,
                                            'attribute_value' => $attr->attributeValue->value,
                                        ];
                                    })->filter()->values() 
                                    : [],
                            ] : null,
                        ];
                    });
                }
            ),
            
            'installments' => CreditInstallmentResource::collection($this->whenLoaded('installments')),
            
            'next_installment' => $this->when(
                $this->relationLoaded('installments'),
                fn() => $this->getNextInstallment() ? new CreditInstallmentResource($this->getNextInstallment()) : null
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
        // Note: ajustez selon votre configuration
        return asset('storage/' . ltrim($path, '/'));
    }
}