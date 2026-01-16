<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayCompleteStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_receipt_id' => ['required', 'integer', 'exists:stock_receipts,id'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'supplier_amount' => ['required', 'numeric', 'min:0'],
            'freight_amount' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['nullable', 'date'],
            'supplier_reference' => ['nullable', 'string', 'max:255'],
            'freight_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'stock_receipt_id.required' => 'La réception de stock est requise',
            'stock_receipt_id.exists' => 'Cette réception de stock n\'existe pas',
            'account_id.required' => 'Le compte est requis',
            'account_id.exists' => 'Ce compte n\'existe pas',
            'supplier_amount.required' => 'Le montant fournisseur est requis',
            'supplier_amount.numeric' => 'Le montant fournisseur doit être un nombre',
            'supplier_amount.min' => 'Le montant fournisseur doit être positif',
            'freight_amount.required' => 'Le montant transitaire est requis',
            'freight_amount.numeric' => 'Le montant transitaire doit être un nombre',
            'freight_amount.min' => 'Le montant transitaire doit être positif',
            'transaction_date.date' => 'La date de transaction doit être une date valide',
        ];
    }

    /**
     * Validation personnalisée : au moins un montant doit être > 0
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $supplierAmount = $this->input('supplier_amount', 0);
            $freightAmount = $this->input('freight_amount', 0);

            if ($supplierAmount <= 0 && $freightAmount <= 0) {
                $validator->errors()->add(
                    'amounts',
                    'Au moins un des montants (fournisseur ou transitaire) doit être supérieur à 0'
                );
            }
        });
    }
}