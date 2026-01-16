<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationListResource extends JsonResource
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
            'sale_number' => $this->whenLoaded('sale', fn() => $this->sale->sale_number ?? null),
            'sale_id' => $this->sale_id,
            'reservation_date' => $this->reservation_date->toISOString(),
            'expiry_date' => $this->expiry_date->toISOString(),
            'total_amount' => (float) $this->total_amount,
            'deposit_amount' => (float) $this->deposit_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'status' => $this->status,
            
            // Client (depuis la vente) - seulement id, name et code
            'customer' => $this->whenLoaded('sale', function () {
                return $this->sale->customer ? [
                    'id' => $this->sale->customer->id,
                    'name' => $this->sale->customer->name,
                    'code' => $this->sale->customer->customer_number ?? null,
                ] : null;
            }),
        ];
    }
}