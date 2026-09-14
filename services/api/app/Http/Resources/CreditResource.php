<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditResource extends JsonResource
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
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'discount_reason' => $this->discount_reason,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'status' => $this->status,
            'payment_percentage' => (float) $this->getPaymentPercentage(),
            'installment_count' => $this->installment_count,
            'installment_frequency' => $this->installment_frequency,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,

            'customer' => $this->when($this->relationLoaded('customer'), function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                    'customer_number' => $this->customer->customer_number ?? null,
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

            'installments' => $this->when($this->relationLoaded('installments'), function () {
                return $this->installments->map(function ($inst) {
                    return [
                        'id' => $inst->id,
                        'installment_number' => $inst->installment_number,
                        'due_date' => $inst->due_date,
                        'amount_due' => (float) $inst->amount_due,
                        'amount_paid' => (float) $inst->amount_paid,
                        'status' => $inst->status,
                        'transactions' => $inst->relationLoaded('installmentTransactions')
                            ? $inst->installmentTransactions->map(function ($it) {
                                return [
                                    'id' => $it->id,
                                    'amount' => (float) $it->amount,
                                    'payment_date' => $it->payment_date,
                                    'transaction' => $it->relationLoaded('transaction') ? [
                                        'id' => $it->transaction->id,
                                        'reference_number' => $it->transaction->reference_number,
                                        'amount' => (float) $it->transaction->amount,
                                        'transaction_date' => $it->transaction->transaction_date,
                                        'account' => $it->transaction->relationLoaded('account') ? [
                                            'id' => $it->transaction->account->id,
                                            'name' => $it->transaction->account->name,
                                            'type' => $it->transaction->account->accountType?->display_name,
                                        ] : null,
                                        'creator' => $it->transaction->relationLoaded('creator') ? [
                                            'id' => $it->transaction->creator->id,
                                            'name' => $it->transaction->creator->name,
                                        ] : null,
                                    ] : null,
                                ];
                            })
                            : null,
                    ];
                });
            }),
        ];
    }
}
