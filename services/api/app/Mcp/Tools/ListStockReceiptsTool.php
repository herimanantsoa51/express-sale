<?php

namespace App\Mcp\Tools;

use App\Models\AccountTransaction;
use App\Models\StockReceipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les commandes de réapprovisionnement (réceptions de stock) avec leur statut : pending (en attente), sent (envoyée), in_transit (en transit), arrived (arrivée), cost_allocated (coûts répartis), validated (validée), cancelled (annulée). Inclut les montants payés au fournisseur et au transitaire. Filtres : statut, fournisseur, transitaire, recherche, période, pagination.')]
class ListStockReceiptsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'status' => 'nullable|string|max:30',
            'supplier_id' => 'nullable|integer|min:1',
            'freight_forwarder_id' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = min($input['limit'] ?? 20, 100);
        $page = $input['page'] ?? 1;

        $query = StockReceipt::query()
            ->select(['id', 'receipt_number', 'supplier_id', 'freight_forwarder_id', 'expected_delivery_date', 'actual_delivery_date', 'total_cost_ariary', 'status', 'notes', 'created_at'])
            ->with([
                'supplier:id,name',
                'freightForwarder:id,name,type',
            ])
            ->withCount('items')
            ->orderBy('created_at', 'desc');

        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }
        if (! empty($input['supplier_id'])) {
            $query->where('supplier_id', (int) $input['supplier_id']);
        }
        if (! empty($input['freight_forwarder_id'])) {
            $query->where('freight_forwarder_id', (int) $input['freight_forwarder_id']);
        }
        if (! empty($input['search'])) {
            $query->where('receipt_number', 'ILIKE', "%{$input['search']}%");
        }
        if (! empty($input['date_from'])) {
            $query->where('created_at', '>=', $input['date_from']);
        }
        if (! empty($input['date_to'])) {
            $query->where('created_at', '<=', $input['date_to'].' 23:59:59');
        }

        $total = (clone $query)->count();
        $receipts = $query->forPage($page, $perPage)->get();

        // Montants payés par commande (transactions non annulées), en une requête groupée
        $paymentsByReceipt = AccountTransaction::where('stock_receipt_id', $receipts->isEmpty() ? 0 : $receipts->pluck('id'))
            ->whereDoesntHave('reversingTransaction')
            ->get(['stock_receipt_id', 'supplier_id', 'freight_forwarder_id', 'amount'])
            ->groupBy('stock_receipt_id')
            ->map(fn ($group) => [
                'supplier' => (float) $group->whereNotNull('supplier_id')->sum('amount'),
                'freight' => (float) $group->whereNotNull('freight_forwarder_id')->sum('amount'),
            ]);

        return Response::json([
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
            'receipts' => $receipts->map(fn (StockReceipt $receipt) => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'supplier' => $receipt->supplier?->name,
                'freight_forwarder' => $receipt->freightForwarder ? [
                    'name' => $receipt->freightForwarder->name,
                    'type' => $receipt->freightForwarder->type,
                ] : null,
                'item_count' => (int) $receipt->items_count,
                'total_cost_ariary' => (float) $receipt->total_cost_ariary,
                'supplier_paid' => $paymentsByReceipt[$receipt->id]['supplier'] ?? 0.0,
                'freight_paid' => $paymentsByReceipt[$receipt->id]['freight'] ?? 0.0,
                'status' => $receipt->status,
                'expected_delivery_date' => $receipt->expected_delivery_date?->toDateString(),
                'actual_delivery_date' => $receipt->actual_delivery_date?->toDateString(),
                'created_at' => $receipt->created_at?->toISOString(),
                'notes' => $receipt->notes,
            ])->toArray(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description("Filtrer par statut : 'pending', 'sent', 'in_transit', 'arrived', 'cost_allocated', 'validated' ou 'cancelled'."),
            'supplier_id' => $schema->integer()
                ->description('Filtrer par identifiant de fournisseur (voir list-suppliers-tool).'),
            'freight_forwarder_id' => $schema->integer()
                ->description('Filtrer par identifiant de transitaire (voir list-freight-forwarders-tool).'),
            'search' => $schema->string()
                ->description("Recherche par numéro de réception (ex. 'RCP-2026...')."),
            'date_from' => $schema->string()
                ->description('Date de début de la période de création (format Y-m-d).'),
            'date_to' => $schema->string()
                ->description('Date de fin de la période de création (format Y-m-d).'),
            'limit' => $schema->integer()
                ->description('Nombre de résultats par page (1 à 100, 20 par défaut).')
                ->min(1)
                ->max(100),
            'page' => $schema->integer()
                ->description('Numéro de page (1 par défaut).')
                ->min(1),
        ];
    }
}
