<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaySupplierRequest extends FormRequest
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
            'transaction_date.date' => 'La date de transaction doit être une date valide',
        ];
    }
}