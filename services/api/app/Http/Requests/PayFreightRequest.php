<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayFreightRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
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
            'amount.required' => 'Le montant est requis',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être supérieur à 0',
            'transaction_date.date' => 'La date de transaction doit être une date valide',
        ];
    }
}
