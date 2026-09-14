<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'variant_id' => $this->variant_id,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) $this->subtotal,
            'created_at' => $this->created_at?->toISOString(),

            // Relations
            'variant' => $this->whenLoaded('variant', fn () => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'product' => $this->when($this->variant->relationLoaded('product'), fn () => [
                    'id' => $this->variant->product->id,
                    'name' => $this->variant->product->name,
                    'image_url' => $this->variant->product->image_url,
                ]),
                'attributes' => $this->when(
                    $this->variant->relationLoaded('attributeValues'),
                    fn () => $this->variant->attributeValues->map(fn ($av) => [
                        'type' => $av->attributeType->name,
                        'value' => $av->value,
                    ])
                ),
            ]),
        ];
    }
}
