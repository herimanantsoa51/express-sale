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

#[Description('Transfère une quantité d\'une variante de produit d\'un emplacement de stock vers un autre : la quantité est retirée de l\'emplacement source (si disponible) et ajoutée à l\'emplacement destination, avec un mouvement de stock tracé.')]
class StockTransferTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'variant_id' => 'required|integer|min:1|exists:product_variants,id',
            'from_location_id' => 'required|integer|min:1|exists:locations,id',
            'to_location_id' => 'required|integer|min:1|exists:locations,id|different:from_location_id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ], [
            'variant_id.required' => 'La variante de produit est requise (variant_id).',
            'from_location_id.required' => "L'emplacement source est requis (from_location_id).",
            'to_location_id.required' => "L'emplacement destination est requis (to_location_id).",
            'to_location_id.different' => "L'emplacement destination doit être différent de l'emplacement source.",
            'quantity.required' => 'La quantité à transférer est requise (quantity).',
        ]);

        try {
            $movement = DB::transaction(function () use ($input) {
                $fromVariantLocation = ProductVariantLocation::where('variant_id', $input['variant_id'])
                    ->where('location_id', $input['from_location_id'])
                    ->first();

                if (! $fromVariantLocation || $fromVariantLocation->quantity < $input['quantity']) {
                    throw new \Exception('Quantité insuffisante dans la location source (disponible : '.($fromVariantLocation?->quantity ?? 0).').');
                }

                $fromVariantLocation->decrement('quantity', $input['quantity']);

                $toVariantLocation = ProductVariantLocation::firstOrCreate(
                    [
                        'variant_id' => $input['variant_id'],
                        'location_id' => $input['to_location_id'],
                    ],
                    ['quantity' => 0]
                );
                $toVariantLocation->increment('quantity', $input['quantity']);

                return StockMovement::createTransfer(
                    (int) $input['variant_id'],
                    (int) $input['from_location_id'],
                    (int) $input['to_location_id'],
                    (int) $input['quantity'],
                    auth()->id(),
                    $input['reason'] ?? null,
                    $input['notes'] ?? null
                );
            });
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::STOCK_TRANSFERRED,
                ' le transfert de stock via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec du transfert : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::STOCK_TRANSFERRED,
            "a transféré {$input['quantity']} unité(s) via MCP (mouvement #{$movement->id})",
            [
                'model_type' => StockMovement::class,
                'model_id' => $movement->id,
                'metadata' => $movement->toArray(),
            ],
            'movements-stock'
        );

        return Response::json([
            'message' => 'Transfert effectué avec succès.',
            'movement' => [
                'id' => $movement->id,
                'variant_id' => (int) $movement->variant_id,
                'from_location_id' => (int) $movement->from_location_id,
                'to_location_id' => (int) $movement->to_location_id,
                'quantity' => (int) $movement->quantity,
                'movement_type' => $movement->movement_type,
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
                ->description('Identifiant de la variante de produit à transférer.')
                ->required()
                ->min(1),
            'from_location_id' => $schema->integer()
                ->description("Identifiant de l'emplacement source.")
                ->required()
                ->min(1),
            'to_location_id' => $schema->integer()
                ->description("Identifiant de l'emplacement destination.")
                ->required()
                ->min(1),
            'quantity' => $schema->integer()
                ->description('Quantité à transférer (doit être disponible dans la location source).')
                ->required()
                ->min(1),
            'reason' => $schema->string()
                ->description('Motif du transfert.')
                ->max(500),
            'notes' => $schema->string()
                ->description('Notes sur le transfert.')
                ->max(1000),
        ];
    }
}
