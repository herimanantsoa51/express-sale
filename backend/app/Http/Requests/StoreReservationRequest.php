<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'expiry_date' => 'required|date|after:today',
            'deposit_amount' => 'required|numeric|min:0',
            'account_id' => 'required_if:deposit_amount,>,0|nullable|exists:accounts,id',
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            
            // Items de réservation
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Le client est requis pour une réservation',
            'customer_id.exists' => 'Le client sélectionné n\'existe pas',
            'expiry_date.required' => 'La date d\'expiration est requise',
            'expiry_date.after' => 'La date d\'expiration doit être dans le futur',
            'deposit_amount.required' => 'Le montant de l\'acompte est requis',
            'deposit_amount.min' => 'L\'acompte ne peut pas être négatif',
            'account_id.required_if' => 'Le compte est requis si un acompte est versé',
            'account_id.exists' => 'Le compte sélectionné n\'existe pas',
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
}
