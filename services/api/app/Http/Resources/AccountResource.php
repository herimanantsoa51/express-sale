<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour un compte individuel
 */
class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_type' => [
                'id' => $this->accountType->id,
                'code' => $this->accountType->code,
                'name' => $this->accountType->name,
                'display_name' => $this->accountType->display_name,
            ],
            'name' => $this->name,
            'account_number' => $this->account_number,
            'initial_balance' => (float) $this->initial_balance,
            'current_balance' => (float) $this->current_balance,
            'formatted_balance' => number_format($this->current_balance, 2, ',', ' ').' Ar',
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Statistiques optionnelles
            'stats' => $this->when($request->query('include_stats'), function () {
                return $this->getStats();
            }),
        ];
    }
}
