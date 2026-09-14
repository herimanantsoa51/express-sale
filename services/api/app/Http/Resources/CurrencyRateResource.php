<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'rates_in_ariary' => [
                'euro' => [
                    'value' => (float) $this->euro_rate,
                    'label' => '1 EUR = '.number_format($this->euro_rate, 2, ',', ' ').' Ar',
                ],
                'dollar' => [
                    'value' => (float) $this->dollar_rate,
                    'label' => '1 USD = '.number_format($this->dollar_rate, 2, ',', ' ').' Ar',
                ],
                'yuan' => [
                    'value' => (float) $this->yuan_rate,
                    'label' => '1 CNY = '.number_format($this->yuan_rate, 2, ',', ' ').' Ar',
                ],
                'dirham' => [
                    'value' => (float) $this->dirham_rate,
                    'label' => '1 MAD = '.number_format($this->dirham_rate, 2, ',', ' ').' Ar',
                ],
                'baht' => [
                    'value' => (float) $this->baht_rate,
                    'label' => '1 THB = '.number_format($this->baht_rate, 2, ',', ' ').' Ar',
                ],
            ],
            'is_active' => $this->is_active,
            'effective_date' => $this->effective_date->format('Y-m-d'),
            'is_current' => $this->isCurrent(),
            'is_recent' => $this->isRecent(),

            'created_by' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
