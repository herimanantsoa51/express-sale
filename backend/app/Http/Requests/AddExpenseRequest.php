<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
/**
 * Request pour ajouter une dépense autre
 * POST /api/stock-receipts/{id}/expenses
 */
class AddExpenseRequest extends FormRequest
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
            'recipient_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'account_id.required' => 'Le compte est requis',
            'account_id.exists' => 'Le compte n\'existe pas',
            'expense_category_id.required' => 'La catégorie de dépense est requise',
            'expense_category_id.exists' => 'La catégorie de dépense n\'existe pas',
            'amount.required' => 'Le montant est requis',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être supérieur à 0',
        ];
    }
}

