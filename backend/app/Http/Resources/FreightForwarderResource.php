<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreightForwarderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => $this->logo_url,
            'contact' => $this->contact,
            'notes' => $this->notes,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'service_score' => $this->service_score,
            // relation
            'coordinate' => new CoordinateResource($this->whenLoaded('coordinate')),
        ];
    }
}
