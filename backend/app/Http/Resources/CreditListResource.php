<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditListResource extends JsonResource
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
            'sale_number' => $this->whenLoaded('sale', fn() => $this->sale->sale_number, null),
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'credit_date' => $this->credit_date->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'last_payment_date' => $this->last_payment_date?->toISOString(),
            'status' => $this->status,
            
            // Calculés
            'payment_percentage' => round($this->getPaymentPercentage(), 2),
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->getDaysOverdue(),
            'remaining_installments' => $this->getRemainingInstallments(),
            
            // Relations
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'customer_number' => $this->customer->customer_number,
                'name' => $this->customer->name,
            ]),
        ];
    }
}