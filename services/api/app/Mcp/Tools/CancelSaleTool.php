<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Sale;
use App\Services\ImmediateSaleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Annule une vente immédiate (comptant) confirmée : marque la vente comme annulée. Politique métier : AUCUN remboursement automatique ni remise en stock — encaissements et stock sont régularisés manuellement par l\'opérateur (cancel-transaction-tool, stock-adjustment-tool).')]
class CancelSaleTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ImmediateSaleService $immediateSaleService): Response
    {
        $input = $request->validate([
            'sale_id' => 'required|integer|min:1',
        ], [
            'sale_id.required' => "L'identifiant de la vente est requis (sale_id).",
        ]);

        $sale = Sale::find($input['sale_id']);
        if (! $sale) {
            return Response::error("Aucune vente ne correspond à l'identifiant {$input['sale_id']}.");
        }

        try {
            $sale = $immediateSaleService->cancelImmediateSale($sale);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::SALE_CANCELLED,
                " l'annulation de la vente {$sale->sale_number} via MCP a échoué",
                ['error' => $e->getMessage(), 'sale_id' => $sale->id]
            );

            return Response::error('Échec de l\'annulation de la vente : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::SALE_CANCELLED,
            " a annulé la vente {$sale->sale_number} via MCP",
            [
                'model_type' => Sale::class,
                'model_id' => $sale->id,
                'metadata' => $sale->toArray(),
            ],
            "ventes/immediate/{$sale->id}"
        );

        return Response::json([
            'message' => 'Vente annulée. Régularisations manuelles requises : remboursement de l\'encaissement (cancel-transaction-tool) et remise en stock (stock-adjustment-tool) à faire par l\'opérateur.',
            'sale' => [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'status' => $sale->status->value,
                'total_amount' => (float) $sale->total_amount,
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
            'sale_id' => $schema->integer()
                ->description('Identifiant de la vente immédiate à annuler.')
                ->required()
                ->min(1),
        ];
    }
}
