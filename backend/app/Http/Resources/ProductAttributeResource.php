<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'attribute_type_id' => $this->attributeType->id,
            'is_required' => $this->pivot->is_required ?? true,

            'attribute_type' => new AttributeTypeResource(
                $this->whenLoaded('attributeType')
            ),
        ];
    }
}

