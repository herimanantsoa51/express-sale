<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour valider et appliquer les coûts (version finale de l'utilisateur)
 * POST /api/stock-receipts/{id}/apply-costs
 */
class ApplyCostAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allocations' => 'required|array|min:1',
            'allocations.*.product_id' => 'required|exists:products,id',
            'allocations.*.freight_cost_per_unit' => 'required|numeric|min:0',
            'allocations.*.other_costs_per_unit' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'allocations.required' => 'Les répartitions de coûts sont requises',
            'allocations.*.product_id.required' => 'L\'ID du produit est requis',
            'allocations.*.product_id.exists' => 'Le produit n\'existe pas',
            'allocations.*.freight_cost_per_unit.required' => 'Le coût de transport par unité est requis',
            'allocations.*.freight_cost_per_unit.numeric' => 'Le coût de transport doit être un nombre',
            'allocations.*.freight_cost_per_unit.min' => 'Le coût de transport ne peut pas être négatif',
            'allocations.*.other_costs_per_unit.required' => 'Les autres coûts par unité sont requis',
            'allocations.*.other_costs_per_unit.numeric' => 'Les autres coûts doivent être un nombre',
            'allocations.*.other_costs_per_unit.min' => 'Les autres coûts ne peuvent pas être négatifs',
        ];
    }
}
