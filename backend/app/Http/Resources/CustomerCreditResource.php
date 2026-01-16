<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale->sale_number,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'credit_date' => $this->credit_date?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'creator' => $this->sale && $this->sale->user ? [
                'id' => $this->sale->user->id,
                'name' => $this->sale->user->name,
            ] : null,
        ];
    }
}
