<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price_adjustment' => $this->price_adjustment,
            'stock_quantity' => $this->stock_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'credit_quantity' => $this->credit_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'image_path' => $this->image_path,
            'is_active' => $this->is_active,

            'attribute_values' => $this->attributeValues->map(function ($vav) {
                return [
                    'id' => $vav->id,
                    'value' => $vav->attributeValue->value,
                    'attribute_type' => new AttributeTypeResource(
                        $vav->attributeValue->attributeType
                    ),
                ];
            }),
        ];
    }
}
