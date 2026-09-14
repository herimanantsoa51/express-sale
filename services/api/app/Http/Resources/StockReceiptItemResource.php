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
                    'category' => $this->whenLoaded('variant.product.category', function () {
                        return [
                            'id' => $this->variant->product->category->id,
                            'name' => $this->variant->product->category->name,
                        ];
                    }),
                ],
                'attributes' => $this->variant->variantAttributeValues->map(function ($vav) {
                    return [
                        'attribute_id' => $vav->attributeValue->attributeType->id,
                        'type' => $vav->attributeValue->attributeType->display_name,
                        'value' => $vav->attributeValue->value,
                    ];
                }),
            ],
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'quantity_variance' => $this->quantity_variance,
            'quantity_variance_percentage' => round($this->quantity_variance_percentage, 2),
            'unit_cost_ariary' => (float) $this->unit_cost_ariary,
            'total_cost' => (float) $this->total_cost,
            'notes' => $this->notes,

            // Qualité globale de l'item
            'quality_rating' => $this->quality_rating,
            'quality_level' => $this->quality_level,
            'quality_notes' => $this->quality_notes,

            // Conformité des attributs
            'ratings' => StockReceiptItemRatingResource::collection($this->whenLoaded('ratings')),
            'conformity' => $this->when(
                $this->relationLoaded('ratings'),
                fn () => $this->getConformityRate()
            ),

            // Résumé complet
            'summary' => $this->when(
                $this->relationLoaded('ratings'),
                fn () => $this->getSummary()
            ),
        ];
    }
}
