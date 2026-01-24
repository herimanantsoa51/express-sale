<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request pour mettre à jour un client
 */
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer') ?? $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'address' => 'nullable|string|max:500',
            'reliability_score' => 'nullable|numeric|min:0|max:10',
            'loyalty_points' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'is_extra_customer' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du client est requis',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé',
            'phone.max' => 'Le téléphone ne peut pas dépasser 50 caractères',
            'address.max' => 'L\'adresse ne peut pas dépasser 500 caractères',
            'reliability_score.min' => 'Le score de fiabilité doit être entre 0 et 10',
            'reliability_score.max' => 'Le score de fiabilité doit être entre 0 et 10',
            'loyalty_points.min' => 'Les points de fidélité ne peuvent pas être négatifs',
            'credit_limit.min' => 'La limite de crédit ne peut pas être négative',
        ];
    }
}
