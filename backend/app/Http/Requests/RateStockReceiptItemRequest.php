<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateStockReceiptItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ratings' => 'required|array|min:1',
            'ratings.*.attribute_type_id' => 'nullable|exists:attribute_types,id',
            'ratings.*.attribute_conformity_rating' => 'nullable|numeric|min:0|max:10',
            'ratings.*.quality_rating' => 'required|numeric|min:0|max:10',
            'ratings.*.quality_notes' => 'nullable|string|max:1000'
        ];
    }

    public function messages(): array
    {
        return [
            'ratings.required' => 'Au moins une évaluation est requise',
            'ratings.*.quality_rating.required' => 'La note de qualité est requise',
            'ratings.*.quality_rating.min' => 'La note doit être entre 0 et 10',
            'ratings.*.quality_rating.max' => 'La note doit être entre 0 et 10',
            'ratings.*.attribute_conformity_rating.min' => 'La note de conformité doit être entre 0 et 10',
            'ratings.*.attribute_conformity_rating.max' => 'La note de conformité doit être entre 0 et 10'
        ];
    }
}