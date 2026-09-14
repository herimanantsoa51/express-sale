<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour une RECOMMANDATION de coût par produit
 */
class ProductCostRecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this['product_id'],
            'product_name' => $this['product_name'],

            // Coût fournisseur (fixe)
            'supplier_unit_cost' => (float) $this['supplier_unit_cost'],

            // RECOMMANDATIONS calculées
            'recommended_freight_cost_per_unit' => (float) $this['freight_cost_per_unit'],
            'recommended_other_costs_per_unit' => (float) $this['other_costs_per_unit'],
            'recommended_total_unit_cost' => (float) $this['total_unit_cost'],

            // Quantités
            'total_quantity' => (int) $this['total_quantity'],
            'variants_count' => (int) $this['variants_count'],

            // Totaux recommandés
            'total_freight_cost' => (float) ($this['freight_cost_per_unit'] * $this['total_quantity']),
            'total_other_costs' => (float) ($this['other_costs_per_unit'] * $this['total_quantity']),

            // Variants concernés
            'variants' => $this['batches']->map(function ($batch) {
                return [
                    'variant_id' => $batch['variant_id'],
                    'variant_attributes' => $batch['variant_attributes'],
                    'quantity' => (int) $batch['quantity'],
                ];
            }),
        ];
    }
}
