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

#[Description('Transfère des fonds entre deux comptes de trésorerie : débit du compte source, crédit du compte destination, avec vérification du solde et des deux transactions comptables liées.')]
class TransferFundsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'from_account_id' => 'required|integer|min:1|exists:accounts,id',
            'to_account_id' => 'required|integer|min:1|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
            'transaction_date' => 'nullable|date',
        ], [
            'from_account_id.required' => 'Le compte source est requis (from_account_id).',
            'to_account_id.required' => 'Le compte destination est requis (to_account_id).',
            'to_account_id.different' => 'Le compte destination doit être différent du compte source.',
            'amount.required' => 'Le montant du transfert est requis (amount).',
            'amount.min' => 'Le montant doit être supérieur à zéro.',
        ]);

        try {
            $result = AccountTransaction::createTransfer(
                (int) $input['from_account_id'],
                (int) $input['to_account_id'],
                (float) $input['amount'],
                $input['notes'] ?? null,
                $input['reference_number'] ?? null,
                $input['transaction_date'] ?? null,
            );
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::ACCOUNT_TRANSACTION_TRANSFER,
                ' le transfert de fonds via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec du transfert : '.$e->getMessage());
        }

        $outgoing = $result['outgoing'];
        $incoming = $result['incoming'];

        ActivityLogger::success(
            ActivityAction::ACCOUNT_TRANSACTION_TRANSFER,
            "a transféré {$input['amount']} via MCP (transaction #{$outgoing->id})",
            [
                'model_type' => AccountTransaction::class,
                'model_id' => $outgoing->id,
                'metadata' => ['outgoing' => $outgoing->toArray(), 'incoming' => $incoming->toArray()],
            ],
            "transactions/{$outgoing->id}"
        );

        return Response::json([
            'message' => 'Transfert effectué avec succès.',
            'outgoing_transaction' => [
                'id' => $outgoing->id,
                'account_id' => (int) $outgoing->account_id,
                'amount' => (float) $outgoing->amount,
                'balance_after' => (float) $outgoing->balance_after,
            ],
            'incoming_transaction' => [
                'id' => $incoming->id,
                'account_id' => (int) $incoming->account_id,
                'amount' => (float) $incoming->amount,
                'balance_after' => (float) $incoming->balance_after,
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
            'from_account_id' => $schema->integer()
                ->description('Identifiant du compte source à débiter.')
                ->required()
                ->min(1),
            'to_account_id' => $schema->integer()
                ->description('Identifiant du compte destination à créditer.')
                ->required()
                ->min(1),
            'amount' => $schema->number()
                ->description('Montant du transfert en Ariary (le solde du compte source est vérifié).')
                ->required(),
            'notes' => $schema->string()
                ->description('Notes sur le transfert.')
                ->max(1000),
            'reference_number' => $schema->string()
                ->description('Numéro de référence du transfert.')
                ->max(255),
            'transaction_date' => $schema->string()
                ->description('Date du transfert (format Y-m-d, aujourd\'hui par défaut).'),
        ];
    }
}
