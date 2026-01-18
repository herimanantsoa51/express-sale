<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour une dépense liée à la réception
 */
class ReceiptExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'notes' => $this->notes,
            'recipient_name' => $this->recipient_name,
            'reference_number' => $this->reference_number,
            'transaction_date' => $this->transaction_date->format('Y-m-d H:i:s'),
            
            // Catégorie
            'expense_category' => [
                'id' => $this->expenseCategory->id,
                'name' => $this->expenseCategory->name,
            ],
            
            // Compte
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ],
            
            // Audit
            'created_by' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}