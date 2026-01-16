<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour créer une dépense opérationnelle
 */
class StoreOperationalExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'nullable|date',
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Le compte est requis',
            'expense_category_id.required' => 'La catégorie de dépense est requise',
            'amount.required' => 'Le montant est requis',
            'amount.min' => 'Le montant doit être supérieur à zéro',
        ];
    }
}