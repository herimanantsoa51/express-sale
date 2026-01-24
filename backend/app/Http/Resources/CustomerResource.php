<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'reliability_score' => (float) $this->reliability_score,
            'reliability_level' => $this->getReliabilityLevel(),
            'score_badge_color' => $this->getScoreBadgeColor(),
            'loyalty_points' => $this->loyalty_points,
            'customer_number' => $this->customer_number,
            'credit_limit' => (float) $this->credit_limit,
            'available_credit' => $this->when(
                $request->routeIs('customers.show') || $request->get('with_credit_info'),
                fn() => $this->getAvailableCredit()
            ),
            'current_credit_usage' => $this->when(
                $request->routeIs('customers.show') || $request->get('with_credit_info'),
                fn() => $this->getCurrentCreditUsage()
            ),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'is_vip' => $this->when(
                $request->routeIs('customers.show'),
                fn() => $this->isVip()
            ),
            'is_at_risk' => $this->isAtRisk(),
            'has_overdue_credits' => $this->when(
                $request->routeIs('customers.show') || $request->get('with_credit_info'),
                fn() => $this->hasOverdueCredits()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            

            // Relations (chargées conditionnellement)
            'sales_count' => $this->when(isset($this->sales_count), $this->sales_count),
            'credits_count' => $this->when(isset($this->credits_count), $this->credits_count),
            'reservations_count' => $this->when(isset($this->reservations_count), $this->reservations_count),
            'is_extra_customer' => $this->is_extra_customer,    
        
        ];
    }
}
