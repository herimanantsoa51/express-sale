<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockPaymentResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'amount_formatted' => number_format($this->amount, 2, ',', ' ').' Ar',
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),
            'transaction_date_human' => $this->transaction_date?->locale('fr')->isoFormat('LL'),

            // Type de paiement
            'payment_type' => $this->when($this->supplier_id, 'supplier',
                $this->when($this->freight_forwarder_id, 'freight_forwarder', 'unknown')
            ),

            // Informations du compte
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
                'account_number' => $this->account->account_number,
                'type' => [
                    'id' => $this->account->accountType->id,
                    'name' => $this->account->accountType->name,
                    'display_name' => $this->account->accountType->display_name,
                ],
            ],

            // Type de transaction
            'transaction_type' => [
                'id' => $this->transactionType->id,
                'code' => $this->transactionType->code,
                'name' => $this->transactionType->name,
                'display_name' => $this->transactionType->display_name,
                'category' => $this->transactionType->category,
            ],

            // Catégorie de dépense
            'expense_category' => $this->when($this->expenseCategory, [
                'id' => $this->expenseCategory?->id,
                'name' => $this->expenseCategory?->name,
                'icon' => $this->expenseCategory?->icon,
            ]),

            // Informations du fournisseur (si applicable)
            'supplier' => $this->when($this->supplier, [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
                'logo_url' => $this->supplier?->logo_url,
                'reliability_score' => $this->supplier ? (float) $this->supplier->reliability_score : null,
            ]),

            // Informations du transitaire (si applicable)
            'freight_forwarder' => $this->when($this->freightForwarder, [
                'id' => $this->freightForwarder?->id,
                'name' => $this->freightForwarder?->name,
                'logo_url' => $this->freightForwarder?->logo_url,
                'service_score' => $this->freightForwarder ? (float) $this->freightForwarder->service_score : null,
            ]),

            // Informations de la réception de stock
            'stock_receipt' => $this->when($this->stockReceipt, [
                'id' => $this->stockReceipt?->id,
                'receipt_number' => $this->stockReceipt?->receipt_number,
                'total_cost' => $this->stockReceipt ? (float) $this->stockReceipt->total_cost_ariary : null,
                'status' => $this->stockReceipt?->status,
            ]),

            // Bénéficiaire
            'recipient_name' => $this->recipient_name,

            // Références et notes
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'notes' => $this->notes,

            // Métadonnées
            'created_by' => $this->when($this->creator, [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'username' => $this->creator?->username,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at?->locale('fr')->diffForHumans(),
        ];
    }
}
