<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Sécurité absolue : si la transaction n'existe pas
        if (! $this->relationLoaded('transaction') || ! $this->transaction) {
            return [];
        }

        return [
            // =====================
            // IDENTITÉ
            // =====================
            'id' => $this->transaction->id,
            'installment_transaction_id' => $this->id,
            'reference' => $this->transaction->reference_number,   
            'status'=> $this->transaction->reversed_transaction_id ? 'cancelled' : 'confirmed',
            // =====================
            // MONTANTS
            // =====================
            'amount' => (float) $this->transaction->amount,
            'formatted_amount' => number_format(
                abs($this->transaction->amount),
                2,
                ',',
                ' '
            ) . ' Ar',

            // =====================
            // DATES
            // =====================
            'transaction_date' => $this->transaction->transaction_date?->toISOString(),

            // =====================
            // ACCOUNT
            // =====================
            'account' => $this->transaction->relationLoaded('account') && $this->transaction->account
                ? [
                    'id' => $this->transaction->account->id,
                    'name' => $this->transaction->account->name,
                    'type' => $this->transaction->account->accountType?->display_name,
                ]
                : null,

            // =====================
            // TRANSACTION TYPE
            // =====================
            'transaction_type' => $this->transaction->relationLoaded('transactionType') && $this->transaction->transactionType
                ? [
                    'id' => $this->transaction->transactionType->id,
                    'name' => $this->transaction->transactionType->name,
                    'category' => $this->transaction->transactionType->category,
                ]
                : null,

            // =====================
            // CREATED BY
            // =====================
            'created_by' => $this->transaction->relationLoaded('creator') && $this->transaction->creator
                ? [
                    'id' => $this->transaction->creator->id,
                    'name' => $this->transaction->creator->name,
                ]
                : null,
        ];
    }
}
