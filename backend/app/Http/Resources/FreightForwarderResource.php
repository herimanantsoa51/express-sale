<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreightForwarderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'logo_url' => $this->logo_url,
            'contact' => $this->contact,
            'service_score' => $this->service_score ? (float) $this->service_score : 0.0,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            
            // Coordonnée avec full_location
            'coordinate' => $this->when($this->coordinate, function () {
                return [
                    'id' => $this->coordinate->id,
                    'country' => $this->coordinate->country,
                    'city' => $this->coordinate->city,
                    'full_location' => $this->coordinate->full_location,
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}