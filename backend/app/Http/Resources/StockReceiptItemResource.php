<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;




class StockReceiptItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variant' => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'product' => [
                    'id' => $this->variant->product->id,
                    'name' => $this->variant->product->name,
                    'category' => $this->whenLoaded('variant.product.category', function() {
                        return [
                            'id' => $this->variant->product->category->id,
                            'name' => $this->variant->product->category->name
                        ];
                    })
                ],
                'attributes' => $this->variant->variantAttributeValues->map(function($vav) {
                    return [
                        'attribute_id' => $vav->attributeValue->attributeType->id,
                        'type' => $vav->attributeValue->attributeType->display_name,
                        'value' => $vav->attributeValue->value
                    ];
                })
            ],
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'quantity_variance' => $this->quantity_variance,
            'quantity_variance_percentage' => round($this->quantity_variance_percentage, 2),
            'unit_cost_ariary' => (float) $this->unit_cost_ariary,
            'total_cost' => (float) $this->quantity_ordered * $this->unit_cost_ariary,
            'notes' => $this->notes,
            'ratings' => StockReceiptItemRatingResource::collection($this->whenLoaded('ratings')),
            'quality_summary' => $this->when(
                $this->relationLoaded('ratings') && $this->ratings->isNotEmpty(),
                fn() => $this->getQualitySummary()
            ),
            'conformity' => $this->when(
                $this->relationLoaded('ratings') && $this->ratings->isNotEmpty(),
                fn() => $this->getAttributeConformityRate()
            )
        ];
    }
}
