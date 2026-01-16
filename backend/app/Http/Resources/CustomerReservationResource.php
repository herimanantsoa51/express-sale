<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_amount' => (float) $this->total_amount,
            'sale_number' => $this->sale->sale_number,
            'deposit_amount' => (float) $this->deposit_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'reservation_date' => $this->reservation_date?->toISOString(),
            'expiry_date' => $this->expiry_date?->toISOString(),
            'status' => $this->status,
            'creator' => $this->sale && $this->sale->user ? [
                'id' => $this->sale->user->id,
                'name' => $this->sale->user->name,
            ] : null,
        ];
    }
}
