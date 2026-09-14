<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:stock_receipt_items,id',

            // Qualité globale de l'item
            'items.*.quality_rating' => 'required|numeric|min:0|max:10',
            'items.*.quality_notes' => 'nullable|string|max:1000',

            // Conformité des attributs
            'items.*.attribute_ratings' => 'nullable|array',
            'items.*.attribute_ratings.*.attribute_type_id' => 'required|exists:attribute_types,id',
            'items.*.attribute_ratings.*.conformity_rating' => 'required|numeric|min:0|max:10',
            'items.*.attribute_ratings.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Au moins un article doit être évalué',
            'items.*.quality_rating.required' => 'La note de qualité est requise',
            'items.*.quality_rating.min' => 'La note doit être entre 0 et 10',
            'items.*.quality_rating.max' => 'La note doit être entre 0 et 10',
            'items.*.attribute_ratings.*.conformity_rating.required' => 'La note de conformité est requise',
            'items.*.attribute_ratings.*.conformity_rating.min' => 'La note doit être entre 0 et 10',
            'items.*.attribute_ratings.*.conformity_rating.max' => 'La note doit être entre 0 et 10',
        ];
    }
}
