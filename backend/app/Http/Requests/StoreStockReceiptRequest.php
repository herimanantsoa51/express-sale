<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'freight_forwarder_id' => 'nullable|exists:freight_forwarders,id',
            'expected_delivery_date'=>'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id|distinct',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.quantity_received' => 'integer|min:0',
            'items.*.unit_cost_ariary' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Le fournisseur est requis',
            'supplier_id.exists' => 'Le fournisseur sélectionné n\'existe pas',
            'items.required' => 'Au moins un article est requis',
            'items.min' => 'Au moins un article est requis',
            'items.*.variant_id.required' => 'La variante de produit est requise',
            'items.*.variant_id.exists' => 'La variante de produit n\'existe pas',
            'items.*.quantity_ordered.required' => 'La quantité commandée est requise',
            'items.*.quantity_ordered.min' => 'La quantité commandée doit être au moins 1',
            'items.*.quantity_received.min' => 'La quantité reçue ne peut pas être négative',
            'items.*.unit_cost_ariary.required' => 'Le coût unitaire est requis',
            'items.*.unit_cost_ariary.min' => 'Le coût unitaire ne peut pas être négatif',
            'items.*.variant_id.distinct' => 'Une même variante ne peut pas être ajoutée plusieurs fois.',
        ];
    }
}

