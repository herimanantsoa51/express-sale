<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreImmediateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'account_id' => 'required|exists:accounts,id',
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            
            // Items de vente
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Le client sélectionné n\'existe pas',
            'account_id.required' => 'Le compte de destination est requis',
            'account_id.exists' => 'Le compte sélectionné n\'existe pas',
            'payment_method.required' => 'La méthode de paiement est requise',
            'items.required' => 'Au moins un article est requis',
            'items.min' => 'Au moins un article est requis',
            'items.*.variant_id.required' => 'La variante de produit est requise',
            'items.*.variant_id.exists' => 'La variante de produit n\'existe pas',
            'items.*.location_id.required' => 'L\'emplacement est requis',
            'items.*.location_id.exists' => 'L\'emplacement n\'existe pas',
            'items.*.quantity.required' => 'La quantité est requise',
            'items.*.quantity.min' => 'La quantité doit être au moins 1',
        ];
    }

    /**
     * Force JSON response on validation failure
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Erreur de validation',
            'errors' => $validator->errors(),
        ], 422));
    }
}
