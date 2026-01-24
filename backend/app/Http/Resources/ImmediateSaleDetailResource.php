<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImmediateSaleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /* =======================
             |  INFOS VENTE
             ======================= */
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'sale_date' => $this->sale_date,

            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'status'=>$this->status,

            /* =======================
             |  CUSTOMER (FIXE)
             ======================= */
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'customer_number' => $this->customer->customer_number,
                'loyalty_points' => $this->customer->loyalty_points,
            ] : null,

            /* =======================
             |  USER
             ======================= */
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],

            /* =======================
             |  LIGNES DE VENTE
             ======================= */
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->subtotal,

                    'product' => [
                        'id' => $item->variant->product->id,
                        'name' => $item->variant->product->name,
                    ],

                    'variant' => [
                        'id' => $item->variant->id,
                        'attributes' => $item->variant->attributeValues->map(function ($attr) {
                            return [
                                'attribute_type' => $attr->attributeValue->attributeType->display_name
                                    ?? $attr->attributeValue->attributeType->name,
                                'attribute_value' => $attr->attributeValue->value,
                            ];
                        })->values(),
                    ],
                ];
            }),

            /* =======================
             |  TRANSACTION
             ======================= */
            'transaction' => $this->accountTransaction ? [
                'id' => $this->accountTransaction->id,
                'reference_number' => $this->accountTransaction->reference_number,
                'amount' => (float) $this->accountTransaction->amount,
                'balance_before' => (float) $this->accountTransaction->balance_before,
                'balance_after' => (float) $this->accountTransaction->balance_after,

                'account' => [
                    'id' => $this->accountTransaction->account->id,
                    'name' => $this->accountTransaction->account->name,
                    'account_number' => $this->accountTransaction->account->account_number,
                    'type' => $this->accountTransaction->account->accountType->name ?? null,
                ],

                'transaction_type' => [
                    'id' => $this->accountTransaction->transactionType->id,
                    'name' => $this->accountTransaction->transactionType->display_name,
                ],
            ] : null,
        ];
    }
}
