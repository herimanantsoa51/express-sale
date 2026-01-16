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
            'attribute_type' => $this->attribute_type_id ? [
                'id' => $this->attributeType->id,
                'name' => $this->attributeType->name,
                'display_name' => $this->attributeType->display_name
            ] : null,
            'attribute_conformity_rating' => (float) $this->attribute_conformity_rating,
            'conformity_level' => $this->conformity_level,
            'is_conforming' => $this->isConforming(),
            'quality_rating' => (float) $this->quality_rating,
            'quality_level' => $this->quality_level,
            'overall_rating' => round($this->calculateOverallRating(), 2),
            'quality_notes' => $this->quality_notes,
            'rated_by' => [
                'id' => $this->ratedBy->id,
                'name' => $this->ratedBy->name
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}