<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountTypeStatsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type_id' => $this->type_id,
            'type_name' => $this->type_name,
            'type_code' => $this->type_code,
            'total_balance' => (float) $this->total_balance,
            'account_count' => (int) $this->account_count,
            'percentage' => $this->percentage ? (float) $this->percentage : null,
        ];
    }
}
