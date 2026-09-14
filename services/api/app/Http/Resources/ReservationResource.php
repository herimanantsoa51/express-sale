<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_number' => $this->reservation_number,
            'sale_number' => $this->reservation_number, // Alias pour compatibilité frontend
            'reservation_date' => $this->reservation_date,
            'expiry_date' => $this->expiry_date,
            'total_amount' => (float) $this->total_amount,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'discount_reason' => $this->discount_reason,
            'deposit_amount' => (float) $this->deposit_amount,
            'remaining_amount' => (float) $this->remaining_amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at,
            'cancellation_reason' => $this->cancellation_reason,

            'customer' => $this->when($this->relationLoaded('customer'), function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                    'customer_number' => $this->customer->customer_number,
                ];
            }),

            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),

            'items' => $this->when($this->relationLoaded('items'), function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'variant_id' => $item->variant_id,
                        'location_id' => $item->location_id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'discount_amount' => (float) $item->discount_amount,
                        'subtotal' => (float) $item->subtotal,
                        'variant' => $item->relationLoaded('variant') ? [
                            'id' => $item->variant->id,
                            'sku' => $item->variant->sku ?? null,
                            'product' => $item->variant->relationLoaded('product') ? [
                                'id' => $item->variant->product->id,
                                'name' => $item->variant->product->name,
                                'image_url' => $item->variant->product->image_url,
                            ] : null,
                            'attribute_values' => $item->variant->relationLoaded('attributeValues')
                                ? $item->variant->attributeValues->map(fn ($av) => [
                                    'type' => $av->attributeValue?->attributeType?->display_name,
                                    'value' => $av->attributeValue?->value,
                                ])
                                : null,
                        ] : null,
                    ];
                });
            }),

            'transactions' => $this->when($this->relationLoaded('transactions'), function () {
                return $this->transactions->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'amount' => (float) $t->amount,
                        'transaction_date' => $t->transaction_date,
                        'description' => $t->description,
                        'reference_number' => $t->reference_number,
                        'account' => $t->relationLoaded('account') ? [
                            'id' => $t->account->id,
                            'name' => $t->account->name,
                        ] : null,
                        'creator' => $t->relationLoaded('creator') ? [
                            'id' => $t->creator->id,
                            'name' => $t->creator->name,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
