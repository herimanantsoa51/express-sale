<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour une transaction individuelle
 */


 class TransactionResource extends JsonResource
 {
     public function toArray(Request $request): array
     {
         $isCancelled = $this->isReversed();
         $isReversal  = !is_null($this->reversed_transaction_id);
 
         return [
             'id' => $this->id,
 
             /* ===================== ÉTAT ===================== */
             'status' => [
                 'is_cancelled' => $isCancelled,
                 'is_reversal'  => $isReversal,
             ],
 
             /* ===================== COMPTE ===================== */
             'account' => [
                 'id'   => $this->account->id,
                 'name' => $this->account->name,
                 'type' => $this->account->accountType->display_name,
             ],
 
             /* ===================== TYPE ===================== */
             'transaction_type' => [
                 'id'           => $this->transactionType->id,
                 'code'         => $this->transactionType->code,
                 'name'         => $this->transactionType->name,
                 'display_name'=> $this->transactionType->display_name,
                 'category'     => $this->transactionType->category,
             ],
 
             /* ===================== MONTANTS ===================== */
             'amount' => (float) $this->amount,
             'formatted_amount' => number_format(abs($this->amount), 2, ',', ' ') . ' Ar',
             'balance_before' => (float) $this->balance_before,
             'balance_after'  => (float) $this->balance_after,
 
             /* ===================== CONTEXTES ===================== */
             'recipient_name'   => $this->recipient_name,
             'reference_number' => $this->reference_number,
             'description'      => $this->description,
             'notes'            => $this->notes,
 
             /* ===================== RELATIONS OPTIONNELLES ===================== */
             'related_account' => $this->when($this->related_account_id, fn () => [
                 'id'   => $this->relatedAccount->id,
                 'name' => $this->relatedAccount->name,
             ]),
 
             'supplier' => $this->when($this->supplier_id, fn () => [
                 'id'   => $this->supplier->id,
                 'name' => $this->supplier->name,
             ]),
 
             'freight_forwarder' => $this->when($this->freight_forwarder_id, fn () => [
                 'id'   => $this->freightForwarder->id,
                 'name' => $this->freightForwarder->name,
             ]),
 
             'stock_receipt' => $this->when($this->stock_receipt_id, fn () => [
                 'id'             => $this->stockReceipt->id,
                 'receipt_number' => $this->stockReceipt->receipt_number,
                 'total_cost'     => (float) $this->stockReceipt->total_cost_ariary,
             ]),
 
             'expense_category' => $this->when($this->expense_category_id, fn () => [
                 'id'   => $this->expenseCategory->id,
                 'name' => $this->expenseCategory->name,
             ]),
             'planned_expense' => $this->when($this->planned_expense_id, fn () => [
                 'id'          => $this->plannedExpense->id,
                 'title'       => $this->plannedExpense->name,
             ]),

 
             /* ===================== VENTE (LOGIQUE MÉTIER) ===================== */
             'sale_context' => $this->when($this->sale, function () {
                 return match ($this->sale->sale_type->value) {
                     'immediate' => [
                         'type'    => 'immediate',
                         'sale_id' => $this->sale->id,
                         'sale_number' => $this->sale->sale_number,
                     ],
                     'credit' => [
                         'type'      => 'credit',
                         'credit_id' => $this->sale->credit?->id,
                         'sale_number' => $this->sale->sale_number,
                     ],
                     'reservation' => [
                         'type'            => 'reservation',
                         'reservation_id'  => $this->sale->reservation?->id,
                         'sale_number' => $this->sale->sale_number,
                     ],
                     default => null,
                 };
             }),
 
            'cancelled_by_transaction' => $this->when($isCancelled, function () {
                $reversal = $this->reversingTransaction()->first(); // force le chargement
                if (!$reversal) {
                    return [
                        'id' => null,
                        'reference_number' => 'N/A',
                        'transaction_date' => null,
                        'created_at' => null,
                    ];
                }
                return [
                    'id'               => $reversal->id,
                    'reference_number' => $reversal->reference_number,
                    'transaction_date' => $reversal->transaction_date?->format('Y-m-d H:i:s'),
                    'created_at'       => $reversal->created_at?->format('Y-m-d H:i:s'),
                ];
            }), 
             'reverses_transaction' => $this->when($isReversal, function () {
                 return [
                     'id'               => $this->reversedTransaction->id,
                     'reference_number' => $this->reversedTransaction->reference_number,
                 ];
             }),
 
             /* ===================== META ===================== */
             'created_by' => $this->whenLoaded('creator', fn () => [
                 'id'   => $this->creator->id,
                 'name' => $this->creator->name,
             ]),
 
             'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
             'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),
         ];
     }
 }
 
