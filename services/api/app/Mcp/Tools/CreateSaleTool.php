<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Services\ImmediateSaleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée une vente immédiate (payée comptant) pour une liste de variantes de produits prises dans le stock. Le prix, le total et la transaction comptable sont calculés automatiquement.')]
class CreateSaleTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ImmediateSaleService $immediateSaleService): Response
    {
        $input = $request->validate([
            'account_id' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|min:1',
            'items.*.location_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|integer|min:1',
            'payment_method' => 'nullable|string|in:cash,mobile_money,bank_transfer,mixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'account_id.required' => 'Le compte de destination est requis (account_id).',
            'items.required' => 'Au moins un article est requis (items).',
            'items.min' => 'Au moins un article est requis.',
            'items.*.variant_id.required' => 'Chaque article doit préciser la variante de produit (variant_id).',
            'items.*.location_id.required' => 'Chaque article doit préciser son emplacement de stock (location_id).',
            'items.*.quantity.required' => 'Chaque article doit préciser une quantité (quantity).',
            'items.*.quantity.min' => 'La quantité doit être au moins 1.',
            'payment_method.in' => 'La méthode de paiement doit être : cash, mobile_money, bank_transfer ou mixed.',
        ]);

        try {
            $sale = $immediateSaleService->createImmediateSale([
                'customer_id' => $input['customer_id'] ?? null,
                'account_id' => (int) $input['account_id'],
                'payment_method' => $input['payment_method'] ?? 'cash',
                'discount_amount' => $input['discount_amount'] ?? null,
                'discount_reason' => $input['discount_reason'] ?? null,
                'notes' => $input['notes'] ?? null,
                'items' => array_map(fn (array $item) => [
                    'variant_id' => (int) $item['variant_id'],
                    'location_id' => (int) $item['location_id'],
                    'quantity' => (int) $item['quantity'],
                ], $input['items']),
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::SALE_CREATED,
                ' la création de la vente rapide via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création de la vente : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::SALE_CREATED,
            " a créé une vente rapide d'une valeur de {$sale->total_amount} via MCP",
            [
                'model_type' => \App\Models\Sale::class,
                'model_id' => $sale->id,
                'metadata' => $sale->toArray(),
            ],
            "ventes/immediate/{$sale->id}"
        );

        return Response::json([
            'message' => 'Vente créée avec succès.',
            'sale' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'customer_id' => $sale->customer_id,
                'subtotal' => (float) $sale->subtotal,
                'discount_amount' => (float) $sale->discount_amount,
                'total_amount' => (float) $sale->total_amount,
                'payment_method' => $sale->payment_method->value,
                'payment_status' => $sale->payment_status->value,
                'sale_date' => $sale->sale_date?->toISOString(),
                'items' => collect($sale->items)->map(fn ($item) => [
                    'variant_id' => (int) $item->variant_id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
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
            'account_id' => $schema->integer()
                ->description('Identifiant du compte de trésorerie où encaisser le paiement.')
                ->required()
                ->min(1),
            'items' => $schema->array()
                ->description('Articles à vendre.')
                ->required()
                ->min(1)
                ->items(
                    $schema->object([
                        'variant_id' => $schema->integer()
                            ->description('Identifiant de la variante de produit.')
                            ->required()
                            ->min(1),
                        'location_id' => $schema->integer()
                            ->description("Identifiant de l'emplacement de stock où la variante est disponible.")
                            ->required()
                            ->min(1),
                        'quantity' => $schema->integer()
                            ->description('Quantité à vendre.')
                            ->required()
                            ->min(1),
                    ])
                ),
            'customer_id' => $schema->integer()
                ->description('Identifiant du client (optionnel — vente anonyme si omis).')
                ->min(1),
            'payment_method' => $schema->string()
                ->description('Méthode de paiement : cash, mobile_money, bank_transfer ou mixed (cash par défaut).')
                ->enum(['cash', 'mobile_money', 'bank_transfer', 'mixed']),
            'discount_amount' => $schema->number()
                ->description('Montant de la remise globale en Ariary.')
                ->min(0),
            'discount_reason' => $schema->string()
                ->description('Motif de la remise.')
                ->max(255),
            'notes' => $schema->string()
                ->description('Notes sur la vente.')
                ->max(1000),
        ];
    }
}
