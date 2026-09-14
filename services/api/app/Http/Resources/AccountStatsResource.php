<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'total_money' => (float) $this->total_money,
            'active_accounts' => $this->active_accounts,
            'inactive_accounts' => $this->inactive_accounts,
            'total_accounts' => $this->total_accounts,
            'average_balance' => (float) $this->average_balance,
            'by_type' => AccountTypeStatsResource::collection($this->by_type),
        ];
    }
}
