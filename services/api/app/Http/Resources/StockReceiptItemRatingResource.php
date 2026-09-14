<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockReceiptItemRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_type' => [
                'id' => $this->attributeType->id,
                'name' => $this->attributeType->name,
                'display_name' => $this->attributeType->display_name,
            ],
            'conformity_rating' => (float) $this->conformity_rating,
            'conformity_level' => $this->conformity_level,
            'is_conforming' => $this->isConforming(),
            'notes' => $this->notes,
            'rated_by' => [
                'id' => $this->ratedBy->id,
                'name' => $this->ratedBy->name,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
