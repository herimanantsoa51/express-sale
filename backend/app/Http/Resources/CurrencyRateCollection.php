<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CurrencyRateCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'has_more' => $this->hasMorePages(),
            ],
        ];
    }

    public function with(Request $request): array
    {
        return [
            'status' => 'success',
            'message' => 'Liste des taux de change récupérée avec succès',
        ];
    }
}