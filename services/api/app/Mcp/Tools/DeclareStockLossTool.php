<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\ProductVariantLocation;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Déclare une perte de stock (casse, vol, péremption, détérioration, écart d\'inventaire) : consomme les batches FIFO, met à jour l\'emplacement et retourne l\'impact financier de la perte.')]
class DeclareStockLossTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'variant_id' => 'required|integer|min:1|exists:product_variants,id',
            'location_id' => 'required|integer|min:1|exists:locations,id',
            'quantity' => 'required|integer|min:1',
            'loss_type' => 'required|string|in:breakage,theft,expiry,damage,inventory_shortage,other',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ], [
            'variant_id.required' => 'La variante de produit est requise (variant_id).',
            'location_id.required' => "L'emplacement est requis (location_id).",
            'quantity.required' => 'La quantité perdue est requise (quantity).',
            'loss_type.required' => "Le type de perte est requis (loss_type) : breakage, theft, expiry, damage, inventory_shortage ou other.",
            'loss_type.in' => 'Le type de perte doit être : breakage, theft, expiry, damage, inventory_shortage ou other.',
            'reason.required' => 'Le motif de la perte est requis (reason).',
        ]);

        try {
            $result = DB::transaction(function () use ($input) {
                $variantLocation = ProductVariantLocation::where([
                    'variant_id' => $input['variant_id'],
                    'location_id' => $input['location_id'],
                ])->first();

                if (! $variantLocation || $variantLocation->quantity < $input['quantity']) {
                    throw new \Exception('Quantité insuffisante pour la déclaration de perte (disponible : '.($variantLocation?->quantity ?? 0).').');
                }

                // Consommer les batches en FIFO
                $remainingToLose = (int) $input['quantity'];
                $batches = StockBatch::where('variant_id', $input['variant_id'])
                    ->available()
                    ->fifoOrder()
                    ->lockForUpdate()
                    ->get();

                $affectedBatches = [];
                $totalCostImpact = 0;

                foreach ($batches as $batch) {
                    if ($remainingToLose <= 0) {
                        break;
                    }

                    $quantityFromBatch = min($batch->remaining_quantity, $remainingToLose);
                    $batch->decrement('remaining_quantity', $quantityFromBatch);

                    $batchCostImpact = $quantityFromBatch * $batch->total_unit_cost;
                    $totalCostImpact += $batchCostImpact;

                    $affectedBatches[] = [
                        'batch_number' => $batch->batch_number,
                        'quantity_lost' => $quantityFromBatch,
                        'unit_cost' => (float) $batch->total_unit_cost,
                        'cost_impact' => (float) $batchCostImpact,
                    ];

                    $remainingToLose -= $quantityFromBatch;
                }

                if ($remainingToLose > 0) {
                    throw new \Exception('Stock insuffisant dans les batches disponibles');
                }

                $variantLocation->decrement('quantity', $input['quantity']);
                $variantLocation->variant->recalculateTotalStock();

                $movement = StockMovement::create([
                    'variant_id' => $input['variant_id'],
                    'from_location_id' => $input['location_id'],
                    'to_location_id' => null,
                    'quantity' => $input['quantity'],
                    'movement_type' => 'loss',
                    'loss_type' => $input['loss_type'],
                    'performed_by' => auth()->id(),
                    'reason' => $input['reason'],
                    'notes' => $input['notes'] ?? null,
                ]);

                return ['movement' => $movement, 'affectedBatches' => $affectedBatches, 'totalCostImpact' => $totalCostImpact];
            });
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::STOCK_LOSS_DECLARED,
                ' la déclaration de perte via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la déclaration de perte : '.$e->getMessage());
        }

        $movement = $result['movement'];

        ActivityLogger::success(
            ActivityAction::STOCK_LOSS_DECLARED,
            "a déclaré une perte de stock de {$input['quantity']} unité(s) via MCP (mouvement #{$movement->id})",
            [
                'model_type' => StockMovement::class,
                'model_id' => $movement->id,
                'metadata' => $movement->toArray(),
            ],
            'movements-stock'
        );

        return Response::json([
            'message' => 'Perte déclarée avec succès.',
            'movement' => [
                'id' => $movement->id,
                'variant_id' => (int) $movement->variant_id,
                'location_id' => (int) $input['location_id'],
                'quantity' => (int) $movement->quantity,
                'loss_type' => $movement->loss_type,
                'reason' => $movement->reason,
            ],
            'affected_batches' => $result['affectedBatches'],
            'total_cost_impact' => (float) $result['totalCostImpact'],
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
            'variant_id' => $schema->integer()
                ->description('Identifiant de la variante de produit perdue.')
                ->required()
                ->min(1),
            'location_id' => $schema->integer()
                ->description("Identifiant de l'emplacement où la perte est constatée.")
                ->required()
                ->min(1),
            'quantity' => $schema->integer()
                ->description('Quantité perdue.')
                ->required()
                ->min(1),
            'loss_type' => $schema->string()
                ->description("Type de perte : 'breakage' (casse), 'theft' (vol), 'expiry' (péremption), 'damage' (détérioration), 'inventory_shortage' (écart d'inventaire) ou 'other'.")
                ->required()
                ->enum(['breakage', 'theft', 'expiry', 'damage', 'inventory_shortage', 'other']),
            'reason' => $schema->string()
                ->description('Motif détaillé de la perte (obligatoire).')
                ->required()
                ->max(500),
            'notes' => $schema->string()
                ->description('Notes sur la perte.')
                ->max(1000),
        ];
    }
}
