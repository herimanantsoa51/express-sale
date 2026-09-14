<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_number' => $this->credit_number,
            'sale_number' => $this->credit_number, // Alias pour compatibilité frontend
            'credit_date' => $this->credit_date,
            'due_date' => $this->due_date,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'status' => $this->status,
            'payment_percentage' => (float) $this->getPaymentPercentage(),
            'customer' => $this->when($this->relationLoaded('customer'), function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'customer_number' => $this->customer->customer_number,
                ];
            }),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
        ];
    }
}
