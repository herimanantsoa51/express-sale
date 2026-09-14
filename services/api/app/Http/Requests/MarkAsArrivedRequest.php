<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkAsArrivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:stock_receipt_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            // 'items.*.location_id' => 'required|exists:locations,id',
            // 'items.*.notes' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Les informations des items sont requises',
            'items.min' => 'Au moins un item doit être fourni',
            'items.*.item_id.required' => 'L\'ID de l\'item est requis',
            'items.*.quantity_received.required' => 'La quantité reçue est requise',
            'items.*.quantity_received.min' => 'La quantité ne peut pas être négative',
            // 'items.*.location_id.required' => 'L\'emplacement est requis',
            // 'items.*.location_id.exists' => 'L\'emplacement sélectionné n\'existe pas'
        ];
    }
}
