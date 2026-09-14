<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'freight_forwarder_id' => 'nullable|exists:freight_forwarders,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'sometimes|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.unit_cost_ariary' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }
}
