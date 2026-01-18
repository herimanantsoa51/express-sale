<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour le résumé des recommandations
 */
class CostRecommendationsSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'allocation_method' => $this['method'],
            
            // Dépenses totales à répartir
            'total_expenses' => [
                'freight_costs' => (float) $this['total_freight_costs'],
                'other_costs' => (float) $this['total_other_costs'],
                'total_to_allocate' => (float) $this['total_allocated'],
            ],
            
            // Statistiques
            'statistics' => [
                'products_count' => (int) $this['products_count'],
                'batches_count' => (int) $this['batches_count'],
            ],
            
            // RECOMMANDATIONS par produit
            'recommendations' => ProductCostRecommendationResource::collection($this['allocations']),
        ];
    }
}