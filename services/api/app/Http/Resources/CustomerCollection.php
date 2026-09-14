<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    public $collects = CustomerResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'total_active' => $this->collection->where('is_active', true)->count(),
                'total_at_risk' => $this->collection->filter(fn ($c) => $c->reliability_score <= 4.0)->count(),
            ],
        ];
    }
}
