<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,

            'category' => new CategoryResource($this->whenLoaded('category')),
            'subcategory' => new CategoryResource($this->whenLoaded('subcategory')),

            'attributes' => ProductAttributeResource::collection(
                $this->whenLoaded('attributeTypes')
            ),

            'variants' => VariantResource::collection(
                $this->whenLoaded('variants')
            ),
        ];
    }
}
