<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayCreditInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est requis',
            'amount.min' => 'Le montant doit être supérieur à 0',
            'account_id.required' => 'Le compte de destination est requis',
            'account_id.exists' => 'Le compte sélectionné n\'existe pas',
        ];
    }
}
