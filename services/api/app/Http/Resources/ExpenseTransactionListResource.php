<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseTransactionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'expense_category_id' => $this->expense_category_id,

            'account' => [
                'id' => $this->account_id,
                'name' => $this->account?->name,
                'number' => $this->account?->account_number,
            ],

            'recipient_name' => $this->recipient_name,

            'amount' => (float) abs($this->amount),

            'planned_expense' => $this->whenLoaded('plannedExpense', fn () => [
                'id' => $this->plannedExpense?->id,
                'name' => $this->plannedExpense?->name,
            ]),

            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),
        ];
    }
}
