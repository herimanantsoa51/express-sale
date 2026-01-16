<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountTransactionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
        
            'type' => [
                'id'       => $this->transactionType?->id,
                'name'     => $this->transactionType?->name,
                'category' => $this->transactionType?->category,
            ],
        
            'expense_category' => $this->when(
                $this->expense_category_id,
                fn () => [
                    'id'   => $this->expenseCategory?->id,
                    'name' => $this->expenseCategory?->name,
                ]
            ),
        
            'related_account' => $this->when(
                $this->related_account_id,
                fn () => [
                    'id'     => $this->relatedAccount?->id,
                    'name'   => $this->relatedAccount?->name,
                    'number' => $this->relatedAccount?->account_number,
                ]
            ),
        
            'recipient_name' => $this->recipient_name,
        
            'amount' => [
                'value' => (float) $this->amount,
                'after' => (float) $this->balance_after,
            ],
        
            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),
        ];
        
    }
}
