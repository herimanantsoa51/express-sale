<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'sale_date' => $this->sale_date->toISOString(),
            'sale_type' => $this->sale_type->value,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'discount_reason' => $this->discount_reason,
            'total_amount' => (float) $this->total_amount,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method?->value,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relations
            'customer' => $this->whenLoaded('customer', fn() => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'reliability_score' => (float) $this->customer->reliability_score,
            ]),
            
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            
            'credit' => $this->whenLoaded('credit', fn() => new CreditResource($this->credit)),
            
            'reservation' => $this->whenLoaded('reservation', fn() => new ReservationResource($this->reservation)),
            
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            
            // Méthodes calculées
            'remaining_amount' => $this->when(
                $this->relationLoaded('credit') || $this->relationLoaded('reservation'),
                fn() => $this->getRemainingAmount()
            ),
            'paid_amount' => $this->when(
                $this->relationLoaded('credit') || $this->relationLoaded('reservation'),
                fn() => $this->getPaidAmount()
            ),
        ];
    }
}
