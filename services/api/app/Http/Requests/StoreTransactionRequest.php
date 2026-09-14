<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour créer une transaction
 */
class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'transaction_type_id' => 'required|exists:transaction_types,id',
            'amount' => 'required|numeric|not_in:0',
            'transaction_date' => 'nullable|date',
            'related_account_id' => 'nullable|exists:accounts,id|different:account_id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'freight_forwarder_id' => 'nullable|exists:freight_forwarders,id',
            'stock_receipt_id' => 'nullable|exists:stock_receipts,id',
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'recipient_name' => 'nullable|string|max:255',
            'sale_id' => 'nullable|exists:sales,id',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Le compte est requis',
            'account_id.exists' => 'Le compte sélectionné n\'existe pas',
            'transaction_type_id.required' => 'Le type de transaction est requis',
            'transaction_type_id.exists' => 'Le type de transaction sélectionné n\'existe pas',
            'amount.required' => 'Le montant est requis',
            'amount.not_in' => 'Le montant ne peut pas être zéro',
            'related_account_id.different' => 'Le compte destination doit être différent du compte source',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier que le compte a un solde suffisant pour les dépenses
            if ($this->account_id && $this->transaction_type_id) {
                $account = \App\Models\Account::find($this->account_id);
                $transactionType = \App\Models\TransactionType::find($this->transaction_type_id);

                if ($account && $transactionType && in_array($transactionType->category, ['expense', 'transfer'])) {
                    if ($account->current_balance < abs($this->amount)) {
                        $validator->errors()->add('amount', 'Solde insuffisant sur le compte');
                    }
                }
            }
        });
    }
}
