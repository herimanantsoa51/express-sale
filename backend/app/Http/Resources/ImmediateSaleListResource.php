<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class ImmediateSaleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'sale_date' => $this->sale_date,

            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            

            'transaction_reference_number' => optional($this->accountTransaction)->reference_number,

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'customer_number' => $this->customer->customer_number,
                'loyalty_points' => $this->customer->loyalty_points,
            ] : null,
        ];
    }
}

