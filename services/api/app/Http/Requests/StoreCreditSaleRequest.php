<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'due_date' => 'required|date|after_or_equal:today',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',

            // Items de vente
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.quantity' => 'required|integer|min:1',

            // Échéances (optionnel - si non fourni, une seule échéance à due_date)
            // Échéances (optionnelles)
            'installments' => 'nullable|array',

            'installments.*.due_date' => 'required_with:installments|date|after_or_equal:today',
            'installments.*.amount' => 'required_with:installments|numeric|min:0',

        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Le client est requis pour une vente à crédit',
            'customer_id.exists' => 'Le client sélectionné n\'existe pas',
            'due_date.required' => 'La date d\'échéance est requise',
            'due_date.after' => 'La date d\'échéance doit être dans le futur',
            'items.required' => 'Au moins un article est requis',
            'items.min' => 'Au moins un article est requis',
            'items.*.variant_id.required' => 'La variante de produit est requise',
            'items.*.variant_id.exists' => 'La variante de produit n\'existe pas',
            'items.*.location_id.required' => 'L\'emplacement est requis',
            'items.*.location_id.exists' => 'L\'emplacement n\'existe pas',
            'items.*.quantity.required' => 'La quantité est requise',
            'items.*.quantity.min' => 'La quantité doit être au moins 1',
            'installments.*.due_date.required' => 'La date d\'échéance est requise pour chaque échéance',
            'installments.*.amount.required' => 'Le montant est requis pour chaque échéance',
        ];
    }
}
