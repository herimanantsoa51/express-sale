<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StockReceiptCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),        // SIMPLE VALEUR
                'last_page' => $this->lastPage(),              // SIMPLE VALEUR
                'per_page' => $this->perPage(),                // SIMPLE VALEUR
                'total' => $this->total(),                     // SIMPLE VALEUR
                'from' => $this->firstItem(),                  // SIMPLE VALEUR
                'to' => $this->lastItem()                      // SIMPLE VALEUR
            ],
            'summary' => [
                'total_receipts' => $this->total(),
                'total_cost' => $this->collection->sum('total_cost_ariary'),
                'pending_count' => $this->collection->where('status', 'pending')->count(),
                'validated_count' => $this->collection->where('status', 'validated')->count()
            ],
            'links' => [  // Optionnel : pour la pagination Laravel standard
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ]
        ];
    }
}