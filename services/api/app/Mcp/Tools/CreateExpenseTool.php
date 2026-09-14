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

#[Description('Enregistre une dépense opérationnelle : débite le montant d\'un compte de trésorerie (le solde est vérifié), la catégorise et trace la transaction comptable. Utiliser list-expense-categories-tool pour trouver l\'identifiant de catégorie.')]
class CreateExpenseTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'account_id' => 'required|integer|min:1|exists:accounts,id',
            'expense_category_id' => 'required|integer|min:1|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'nullable|date',
            'recipient_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
            'planned_expense_id' => 'nullable|integer|min:1|exists:planned_expenses,id',
        ], [
            'account_id.required' => 'Le compte à débiter est requis (account_id).',
            'expense_category_id.required' => "La catégorie de dépense est requise (expense_category_id) — utiliser list-expense-categories-tool.",
            'amount.required' => 'Le montant de la dépense est requis (amount).',
            'amount.min' => 'Le montant doit être supérieur à zéro.',
        ]);

        try {
            $transaction = AccountTransaction::recordOperatingExpense(
                (int) $input['account_id'],
                (int) $input['expense_category_id'],
                (float) $input['amount'],
                $input['recipient_name'] ?? null,
                $input['notes'] ?? null,
                $input['transaction_date'] ?? null,
                $input['reference_number'] ?? null,
                isset($input['planned_expense_id']) ? (int) $input['planned_expense_id'] : null,
            );
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::ACCOUNT_TRANSACTION_CREATED,
                " l'enregistrement d'une dépense opérationnelle via MCP a échoué",
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error("Échec de l'enregistrement de la dépense : ".$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::ACCOUNT_TRANSACTION_CREATED,
            "a enregistré une dépense opérationnelle de {$transaction->amount} via MCP ({$transaction->reference_number})",
            [
                'model_type' => AccountTransaction::class,
                'model_id' => $transaction->id,
                'metadata' => $transaction->toArray(),
            ],
            "transactions/{$transaction->id}"
        );

        return Response::json([
            'message' => 'Dépense enregistrée avec succès.',
            'transaction' => [
                'id' => $transaction->id,
                'reference_number' => $transaction->reference_number,
                'account_id' => (int) $transaction->account_id,
                'amount' => (float) $transaction->amount,
                'balance_after' => (float) $transaction->balance_after,
                'transaction_date' => $transaction->transaction_date?->toDateString(),
                'recipient_name' => $transaction->recipient_name,
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
                ->description('Identifiant du compte de trésorerie à débiter.')
                ->required()
                ->min(1),
            'expense_category_id' => $schema->integer()
                ->description('Identifiant de la catégorie de dépense (voir list-expense-categories-tool).')
                ->required()
                ->min(1),
            'amount' => $schema->number()
                ->description('Montant de la dépense en Ariary (le solde du compte est vérifié).')
                ->required(),
            'transaction_date' => $schema->string()
                ->description('Date de la dépense (format Y-m-d, aujourd\'hui par défaut).'),
            'recipient_name' => $schema->string()
                ->description('Nom du bénéficiaire ou du fournisseur payé.')
                ->max(255),
            'notes' => $schema->string()
                ->description('Description ou notes sur la dépense.')
                ->max(1000),
            'reference_number' => $schema->string()
                ->description('Numéro de référence (pièce justificative, facture...).')
                ->max(255),
            'planned_expense_id' => $schema->integer()
                ->description('Identifiant de la charge planifiée correspondante (optionnel).')
                ->min(1),
        ];
    }
}
