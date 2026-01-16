<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StockReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'supplier' => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'logo_url' => $this->supplier->logo_url,
                'reliability_score' => $this->supplier->reliability_score
            ],
            'freight_forwarder' => $this->freight_forwarder_id ? [
                'id' => $this->freightForwarder->id,
                'name' => $this->freightForwarder->name,
                'logo_url' => $this->freightForwarder->logo_url,
                'service_score' => $this->freightForwarder->service_score
            ] : null,
            'expected_delivery_date' => $this->expected_delivery_date ? $this->expected_delivery_date->format('Y-m-d') : null,
            'actual_delivery_date' => $this->actual_delivery_date ? $this->actual_delivery_date->format('Y-m-d') : null,
            'validated_at' => $this->validated_at ? $this->validated_at->format('Y-m-d') : null,
            'total_cost_ariary' => (float) $this->total_cost_ariary,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'notes' => $this->notes,
            'items' => StockReceiptItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items->count(),
            'total_quantity_ordered' => $this->items->sum('quantity_ordered'),
            'total_quantity_received' => $this->items->sum('quantity_received'),
            'fulfillment_rate' => $this->getFulfillmentRate(),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'total_paid' => $this->whenLoaded('transactions', function() {
                return $this->transactions->sum('amount');
            }),
            'created_by' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }

    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'validated' => 'Validée',
            'cancelled' => 'Annulée',
            default => $this->status
        };
    }

    protected function getFulfillmentRate(): float
    {
        $ordered = $this->items->sum('quantity_ordered');
        if ($ordered == 0) return 0;
        
        $received = $this->items->sum('quantity_received');
        return round(($received / $ordered) * 100, 2);
    }
}




