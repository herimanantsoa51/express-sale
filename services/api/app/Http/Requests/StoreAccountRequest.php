<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour créer un compte
 */
class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type_id' => 'required|exists:account_types,id',
            'name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'initial_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'account_type_id.required' => 'Le type de compte est requis',
            'account_type_id.exists' => 'Le type de compte sélectionné n\'existe pas',
            'name.required' => 'Le nom du compte est requis',
            'initial_balance.required' => 'Le solde initial est requis',
            'initial_balance.min' => 'Le solde initial ne peut pas être négatif',
        ];
    }
}
