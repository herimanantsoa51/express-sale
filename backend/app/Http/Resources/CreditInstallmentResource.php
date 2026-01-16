<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditInstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // =====================
            // IDENTITÉ
            // =====================
            'id' => $this->id,
            'installment_number' => (int) $this->installment_number,

            // =====================
            // DATES
            // =====================
            'due_date' => $this->due_date?->toISOString(),

            // =====================
            // MONTANTS
            // =====================
            'amount_due' => (float) $this->amount_due,
            'amount_paid' => (float) $this->amount_paid,
            'remaining_amount' => (float) $this->getRemainingAmount(),

            // =====================
            // STATUT
            // =====================
            'status' => $this->status,
            'status_description' => $this->getStatusDescription(),

            // =====================
            // INDICATEURS MÉTIER
            // =====================
            'payment_percentage' => round($this->getPaymentPercentage(), 2),
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->getDaysOverdue(),
            'days_until_due' => $this->getDaysUntilDue(),

            // =====================
            // TRANSACTIONS (PAIEMENTS)
            // =====================
            'transactions' => $this->when(
                $this->relationLoaded('installmentTransactions'),
                fn () => InstallmentTransactionResource::collection(
                    $this->installmentTransactions
                )
            ),
        ];
    }
}
