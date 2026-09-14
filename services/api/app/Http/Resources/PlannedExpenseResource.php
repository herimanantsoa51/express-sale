<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlannedExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'estimated_amount' => (float) $this->estimated_amount,
            'frequency' => $this->frequency,
            'day_of_week' => $this->day_of_week,
            'day_of_month' => $this->day_of_month,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_due_date' => $this->next_due_date?->toDateString(),
            'recipient_name' => $this->recipient_name,
            'is_active' => $this->is_active,
            'expense_category' => [
                'id' => $this->expenseCategory->id,
                'name' => $this->expenseCategory->name,
                'icon' => $this->expenseCategory->icon,
            ],
            'days_until_due' => $this->daysUntilDue(),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
