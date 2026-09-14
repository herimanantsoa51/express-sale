<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\AccountTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Annule une transaction comptable (dépense, transfert, encaissement) en créant la transaction inverse : le solde du compte est restauré. Seul le créateur de la transaction peut l\'annuler, et une transaction déjà annulée est refusée.')]
class CancelTransactionTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'transaction_id' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ], [
            'transaction_id.required' => "L'identifiant de la transaction est requis (transaction_id) — voir list-transactions-tool.",
        ]);

        $transaction = AccountTransaction::with('account:id,name,current_balance')->find($input['transaction_id']);
        if (! $transaction) {
            return Response::error("Aucune transaction ne correspond à l'identifiant {$input['transaction_id']}.");
        }

        if (! $transaction->canBeCancelledBy((int) auth()->id())) {
            return Response::error("Vous n'êtes pas autorisé à annuler cette transaction (seul son créateur peut le faire).");
        }

        if ($transaction->isReversed()) {
            return Response::error('Cette transaction a déjà été annulée.');
        }

        $balanceBefore = (float) $transaction->account->current_balance;

        try {
            $transaction->cancel();
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::ACCOUNT_TRANSACTION_CANCELLED,
                " l'annulation de la transaction {$transaction->reference_number} via MCP a échoué",
                ['error' => $e->getMessage(), 'transaction_id' => $transaction->id]
            );

            return Response::error("Échec de l'annulation : ".$e->getMessage());
        }

        $transaction->account->refresh();

        ActivityLogger::success(
            ActivityAction::ACCOUNT_TRANSACTION_CANCELLED,
            "a annulé la transaction {$transaction->reference_number} via MCP".(isset($input['reason']) ? " — motif : {$input['reason']}" : ''),
            [
                'model_type' => AccountTransaction::class,
                'model_id' => $transaction->id,
                'metadata' => $transaction->toArray(),
            ],
            "transactions/{$transaction->id}"
        );

        return Response::json([
            'message' => 'Transaction annulée avec succès. Le solde du compte a été restauré.',
            'cancelled_transaction' => [
                'id' => $transaction->id,
                'reference_number' => $transaction->reference_number,
                'amount' => (float) $transaction->amount,
            ],
            'account' => [
                'id' => $transaction->account->id,
                'name' => $transaction->account->name,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $transaction->account->current_balance,
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
            'transaction_id' => $schema->integer()
                ->description('Identifiant de la transaction à annuler (voir list-transactions-tool).')
                ->required()
                ->min(1),
            'reason' => $schema->string()
                ->description("Motif de l'annulation (tracé dans le journal d'activité).")
                ->max(500),
        ];
    }
}
