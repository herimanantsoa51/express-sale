<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\ProductVariantLocation;
use App\Models\StockMovement;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Ajuste le stock d\'une variante dans un emplacement : quantité positive pour ajouter (régularisation à la hausse), négative pour retirer (régularisation à la baisse). Le stock total de la variante est recalculé et le mouvement est tracé.')]
class StockAdjustmentTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'variant_id' => 'required|integer|min:1|exists:product_variants,id',
            'location_id' => 'required|integer|min:1|exists:locations,id',
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ], [
            'variant_id.required' => 'La variante de produit est requise (variant_id).',
            'location_id.required' => "L'emplacement est requis (location_id).",
            'quantity.required' => 'La quantité est requise (quantity, positive ou négative).',
            'quantity.not_in' => 'La quantité ne peut pas être zéro.',
            'reason.required' => 'Le motif de l\'ajustement est requis (reason).',
        ]);

        $isIncrease = $input['quantity'] > 0;
        $quantity = abs((int) $input['quantity']);

        try {
            $movement = DB::transaction(function () use ($input, $isIncrease, $quantity) {
                $variantLocation = ProductVariantLocation::firstOrCreate(
                    [
                        'variant_id' => $input['variant_id'],
                        'location_id' => $input['location_id'],
                    ],
                    ['quantity' => 0]
                );

                if (! $isIncrease && $variantLocation->quantity < $quantity) {
                    throw new \Exception("Quantité insuffisante pour l'ajustement négatif (disponible : {$variantLocation->quantity}).");
                }

                if ($isIncrease) {
                    $variantLocation->increment('quantity', $quantity);
                } else {
                    $variantLocation->decrement('quantity', $quantity);
                }

                $variantLocation->variant->recalculateTotalStock();

                return StockMovement::createAdjustment(
                    (int) $input['variant_id'],
                    (int) $input['location_id'],
                    $quantity,
                    auth()->id(),
                    $input['reason'],
                    $isIncrease,
                    $input['notes'] ?? null
                );
            });
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::STOCK_ADJUSTED,
                ' l\'ajustement de stock via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de l\'ajustement : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::STOCK_ADJUSTED,
            'a ajusté le stock via MCP (mouvement #'.$movement->id.')',
            [
                'model_type' => StockMovement::class,
                'model_id' => $movement->id,
                'metadata' => $movement->toArray(),
            ],
            'movements-stock'
        );

        return Response::json([
            'message' => $isIncrease
                ? "Ajustement à la hausse de {$quantity} unité(s) effectué."
                : "Ajustement à la baisse de {$quantity} unité(s) effectué.",
            'movement' => [
                'id' => $movement->id,
                'variant_id' => (int) $movement->variant_id,
                'location_id' => (int) $input['location_id'],
                'quantity' => $quantity,
                'direction' => $isIncrease ? 'increase' : 'decrease',
                'reason' => $movement->reason,
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
            'variant_id' => $schema->integer()
                ->description('Identifiant de la variante de produit.')
                ->required()
                ->min(1),
            'location_id' => $schema->integer()
                ->description("Identifiant de l'emplacement concerné.")
                ->required()
                ->min(1),
            'quantity' => $schema->integer()
                ->description('Quantité ajustée : positive pour une hausse, négative pour une baisse (jamais zéro).')
                ->required(),
            'reason' => $schema->string()
                ->description("Motif de l'ajustement (obligatoire).")
                ->required()
                ->max(500),
            'notes' => $schema->string()
                ->description('Notes sur l\'ajustement.')
                ->max(1000),
        ];
    }
}
