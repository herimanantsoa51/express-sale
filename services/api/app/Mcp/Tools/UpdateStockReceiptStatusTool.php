<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\StockReceipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fait progresser une réception de stock dans son cycle de vie : shipped (envoyée), in_transit (en transit), arrived (arrivée, avec quantités reçues), rated (évaluée — crée les batches de stock), validated (validée, après répartition des coûts) ou cancelled (annulée, impossible si déjà validée). Chaque transition vérifie le statut courant de la réception.')]
class UpdateStockReceiptStatusTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'receipt_id' => 'required|integer|min:1',
            'action' => 'required|string|in:shipped,in_transit,arrived,rated,validated,cancelled',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array|min:1',
            'items.*.item_id' => 'required|integer|min:1',
            'items.*.quantity_received' => 'nullable|integer|min:0',
            'items.*.location_id' => 'nullable|integer|min:1',
        ], [
            'receipt_id.required' => "L'identifiant de la réception est requis (receipt_id).",
            'action.required' => "L'action est requise (action) : shipped, in_transit, arrived ou validated.",
            'action.in' => "L'action doit être : shipped, in_transit, arrived, rated, validated ou cancelled.",
        ]);

        $receipt = StockReceipt::with('items')->find($input['receipt_id']);
        if (! $receipt) {
            return Response::error("Aucune réception ne correspond à l'identifiant {$input['receipt_id']}.");
        }

        $action = $input['action'];

        try {
            switch ($action) {
                case 'shipped':
                    if ($receipt->status !== 'pending') {
                        return Response::error("Seules les réceptions en attente (pending) peuvent être marquées comme envoyées — statut actuel : {$receipt->status}.");
                    }
                    $receipt->markAsShipped($input['notes'] ?? null);
                    $activityAction = ActivityAction::STOCK_RECEIPT_SHIPPED;
                    $message = 'Réception marquée comme envoyée.';
                    break;

                case 'in_transit':
                    if (! in_array($receipt->status, ['pending', 'sent'])) {
                        return Response::error("Seules les réceptions en attente ou envoyées peuvent passer en transit — statut actuel : {$receipt->status}.");
                    }
                    $receipt->markAsInTransit($input['notes'] ?? null);
                    $activityAction = ActivityAction::STOCK_RECEIPT_IN_TRANSIT;
                    $message = 'Réception marquée comme en transit.';
                    break;

                case 'arrived':
                    if ($receipt->status === 'validated') {
                        return Response::error('Cette réception est déjà validée.');
                    }
                    if (empty($input['items'])) {
                        return Response::error("Les quantités reçues sont requises pour marquer l'arrivée (items : item_id + quantity_received).");
                    }
                    $receipt->markAsArrived(array_map(fn (array $item) => [
                        'item_id' => (int) $item['item_id'],
                        'quantity_received' => (int) ($item['quantity_received'] ?? 0),
                    ], $input['items']));
                    $activityAction = ActivityAction::STOCK_RECEIPT_ARRIVED;
                    $message = 'Réception marquée comme arrivée. Le stock a été mis à jour.';
                    break;

                case 'rated':
                    if (! in_array($receipt->status, ['arrived', 'rated'])) {
                        return Response::error("Seules les réceptions arrivées peuvent être évaluées — statut actuel : {$receipt->status}.");
                    }
                    if ($receipt->hasBatches()) {
                        return Response::error('Les batches de cette réception existent déjà.');
                    }
                    $receipt->update(['status' => 'rated']);
                    $receipt->createBatches();
                    $activityAction = ActivityAction::STOCK_RECEIPT_RATED;
                    $message = 'Réception évaluée. Les batches de stock ont été créés — passer à la répartition des coûts (allocate-stock-receipt-costs-tool).';
                    break;

                case 'cancelled':
                    try {
                        $receipt->cancel();
                    } catch (\Throwable $e) {
                        return Response::error($e->getMessage());
                    }
                    $activityAction = ActivityAction::STOCK_RECEIPT_CANCELLED;
                    $message = 'Réception annulée.';
                    break;

                case 'validated':
                    if (empty($input['items'])) {
                        return Response::error("L'affectation des emplacements est requise pour valider (items : item_id + location_id).");
                    }
                    $receipt->validate(array_map(fn (array $item) => [
                        'item_id' => (int) $item['item_id'],
                        'location_id' => (int) ($item['location_id'] ?? 0),
                        'notes' => $item['notes'] ?? null,
                    ], $input['items']));
                    $activityAction = ActivityAction::STOCK_RECEIPT_VALIDATED;
                    $message = 'Réception validée avec succès. Les scores ont été mis à jour.';
                    break;

                default:
                    return Response::error('Action inconnue.');
            }
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                $activityAction ?? ActivityAction::STOCK_RECEIPT_UPDATED,
                ' la mise à jour du statut de réapprovisionnement via MCP a échoué',
                ['error' => $e->getMessage(), 'receipt_id' => $receipt->id, 'action' => $action]
            );

            return Response::error('Échec de la mise à jour : '.$e->getMessage());
        }

        $receipt->refresh();

        ActivityLogger::success(
            $activityAction,
            "a mis à jour le réapprovisionnement {$receipt->receipt_number} ({$action}) via MCP",
            [
                'model_type' => StockReceipt::class,
                'model_id' => $receipt->id,
                'metadata' => $receipt->toArray(),
            ],
            "reapprovisionnements/{$receipt->id}"
        );

        return Response::json([
            'message' => $message,
            'receipt' => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'status' => $receipt->status,
                'actual_delivery_date' => $receipt->actual_delivery_date?->toDateString(),
            ],
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
            'receipt_id' => $schema->integer()
                ->description('Identifiant de la réception de stock.')
                ->required()
                ->min(1),
            'action' => $schema->string()
                ->description("Transition à appliquer : 'shipped' (envoyée), 'in_transit' (en transit), 'arrived' (arrivée, items avec quantity_received requis), 'rated' (évaluée — crée les batches, obligatoire avant la répartition des coûts), 'validated' (validée, items avec location_id requis — possible uniquement après répartition des coûts) ou 'cancelled' (annulée).")
                ->required()
                ->enum(['shipped', 'in_transit', 'arrived', 'rated', 'validated', 'cancelled']),
            'notes' => $schema->string()
                ->description('Notes optionnelles pour les transitions shipped et in_transit.')
                ->max(1000),
            'items' => $schema->array()
                ->description("Articles concernés — requis pour 'arrived' (item_id + quantity_received) et 'validated' (item_id + location_id). Les item_id correspondent aux articles retournés à la création de la réception.")
                ->min(1)
                ->items(
                    $schema->object([
                        'item_id' => $schema->integer()
                            ->description("Identifiant de l'article de réception (stock_receipt_items.id).")
                            ->required()
                            ->min(1),
                        'quantity_received' => $schema->integer()
                            ->description("Quantité effectivement reçue (action 'arrived').")
                            ->min(0),
                        'location_id' => $schema->integer()
                            ->description("Emplacement de stockage où ranger l'article (action 'validated').")
                            ->min(1),
                        'notes' => $schema->string()
                            ->description('Notes sur cet article (action validated).')
                            ->max(500),
                    ])
                ),
        ];
    }
}
