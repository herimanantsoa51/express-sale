<?php

namespace App\Http\Resources;

use App\Services\AccountStatsService;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'account_number' => $account->account_number,
                    'current_balance' => (float) $account->current_balance,
                    // Optionnel: ajouter le type pour affichage
                    'account_type' => $account->accountType->display_name ?? null,
                ];
            }),

            // Métadonnées de pagination minimales
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],

            // Liens de pagination
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }

    /**
     * Ajouter les statistiques à la réponse
     */
    public function with($request)
    {
        $statsService = new AccountStatsService;
        $stats = $statsService->getStats($request);

        return [
            'stats' => new AccountStatsResource((object) $stats),
        ];
    }
}
