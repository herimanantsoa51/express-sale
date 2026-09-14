<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Credit;
use App\Services\CreditSaleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Annule une vente à crédit : marque le crédit comme annulé. Politique métier : AUCUN remboursement automatique des échéances déjà payées ni libération du stock — régularisations manuelles par l\'opérateur (cancel-transaction-tool, stock-adjustment-tool). Un crédit déjà payé ou déjà annulé ne peut pas être annulé.')]
class CancelCreditTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, CreditSaleService $creditSaleService): Response
    {
        $input = $request->validate([
            'credit_id' => 'required|integer|min:1',
        ], [
            'credit_id.required' => "L'identifiant du crédit est requis (credit_id).",
        ]);

        $credit = Credit::find($input['credit_id']);
        if (! $credit) {
            return Response::error("Aucun crédit ne correspond à l'identifiant {$input['credit_id']}.");
        }
        if ($credit->status === 'cancelled') {
            return Response::error('Ce crédit est déjà annulé.');
        }
        if ($credit->status === 'paid') {
            return Response::error('Ce crédit est déjà intégralement payé, il ne peut plus être annulé.');
        }

        try {
            $credit = $creditSaleService->cancelCredit($credit);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::CREDIT_CANCELLED,
                " l'annulation du crédit {$credit->credit_number} via MCP a échoué",
                ['error' => $e->getMessage(), 'credit_id' => $credit->id]
            );

            return Response::error('Échec de l\'annulation du crédit : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::CREDIT_CANCELLED,
            " a annulé la vente à crédit {$credit->credit_number} via MCP",
            [
                'model_type' => Credit::class,
                'model_id' => $credit->id,
                'metadata' => $credit->toArray(),
            ],
            "ventes/credits/{$credit->id}"
        );

        return Response::json([
            'message' => 'Vente à crédit annulée. Régularisations manuelles requises : remboursement des échéances payées (cancel-transaction-tool) et libération du stock (stock-adjustment-tool) à faire par l\'opérateur.',
            'credit' => [
                'id' => $credit->id,
                'credit_number' => $credit->credit_number,
                'status' => $credit->status,
                'total_amount' => (float) $credit->total_amount,
                'amount_paid' => (float) $credit->amount_paid,
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
            'credit_id' => $schema->integer()
                ->description('Identifiant du crédit à annuler.')
                ->required()
                ->min(1),
        ];
    }
}
