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

#[Description('Crée une réception de stock (réapprovisionnement) auprès d\'un fournisseur, avec un transitaire optionnel : articles commandés, quantités et coûts unitaires en Ariary. La réception est créée en statut pending ; les étapes suivantes (envoyé, en transit, arrivé, validé) se font via update-stock-receipt-status-tool.')]
class CreateStockReceiptTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'supplier_id' => 'required|integer|min:1|exists:suppliers,id',
            'freight_forwarder_id' => 'nullable|integer|min:1|exists:freight_forwarders,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|min:1|exists:product_variants,id|distinct',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost_ariary' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ], [
            'supplier_id.required' => 'Le fournisseur est requis (supplier_id). Utiliser un outil de recherche de fournisseurs ou demander le nom.',
            'items.required' => 'Au moins un article est requis (items).',
            'items.*.variant_id.required' => 'La variante de produit est requise pour chaque article (variant_id).',
            'items.*.quantity_ordered.required' => 'La quantité commandée est requise (quantity_ordered).',
            'items.*.unit_cost_ariary.required' => "Le coût unitaire en Ariary est requis (unit_cost_ariary).",
            'items.*.variant_id.distinct' => 'Une même variante ne peut pas être ajoutée plusieurs fois.',
        ]);

        try {
            $receipt = StockReceipt::createWithItems([
                'supplier_id' => (int) $input['supplier_id'],
                'freight_forwarder_id' => isset($input['freight_forwarder_id']) ? (int) $input['freight_forwarder_id'] : null,
                'expected_delivery_date' => $input['expected_delivery_date'] ?? null,
                'notes' => $input['notes'] ?? null,
                'items' => array_map(fn (array $item) => [
                    'variant_id' => (int) $item['variant_id'],
                    'quantity_ordered' => (int) $item['quantity_ordered'],
                    'unit_cost_ariary' => (float) $item['unit_cost_ariary'],
                    'notes' => $item['notes'] ?? null,
                ], $input['items']),
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::STOCK_RECEIPT_CREATED,
                ' la création du réapprovisionnement via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création de la réception : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::STOCK_RECEIPT_CREATED,
            "a créé le réapprovisionnement {$receipt->receipt_number} via MCP",
            [
                'model_type' => StockReceipt::class,
                'model_id' => $receipt->id,
                'metadata' => $receipt->toArray(),
            ],
            "reapprovisionnements/{$receipt->id}"
        );

        return Response::json([
            'message' => 'Réception de stock créée avec succès.',
            'receipt' => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'supplier_id' => $receipt->supplier_id,
                'freight_forwarder_id' => $receipt->freight_forwarder_id,
                'status' => $receipt->status,
                'expected_delivery_date' => $receipt->expected_delivery_date?->toDateString(),
                'total_cost_ariary' => (float) $receipt->total_cost_ariary,
                'items' => collect($receipt->items)->map(fn ($item) => [
                    'item_id' => $item->id,
                    'variant_id' => (int) $item->variant_id,
                    'quantity_ordered' => (int) $item->quantity_ordered,
                    'unit_cost_ariary' => (float) $item->unit_cost_ariary,
                ])->toArray(),
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
            'supplier_id' => $schema->integer()
                ->description('Identifiant du fournisseur.')
                ->required()
                ->min(1),
            'freight_forwarder_id' => $schema->integer()
                ->description('Identifiant du transitaire (optionnel).')
                ->min(1),
            'expected_delivery_date' => $schema->string()
                ->description('Date de livraison attendue (format Y-m-d, optionnel).'),
            'notes' => $schema->string()
                ->description('Notes sur la réception.')
                ->max(1000),
            'items' => $schema->array()
                ->description('Articles commandés.')
                ->required()
                ->min(1)
                ->items(
                    $schema->object([
                        'variant_id' => $schema->integer()
                            ->description('Identifiant de la variante de produit.')
                            ->required()
                            ->min(1),
                        'quantity_ordered' => $schema->integer()
                            ->description('Quantité commandée.')
                            ->required()
                            ->min(1),
                        'unit_cost_ariary' => $schema->number()
                            ->description("Coût unitaire d'achat en Ariary.")
                            ->required()
                            ->min(0),
                        'notes' => $schema->string()
                            ->description('Notes sur cet article.')
                            ->max(500),
                    ])
                ),
        ];
    }
}
