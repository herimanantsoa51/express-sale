<?php

namespace App\Mcp\Tools;

use App\Models\AccountTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les transactions comptables (dépenses, transferts, encaissements) avec filtres : compte, catégorie de transaction (expense/transfer/income), période, et pagination. Retourne les montants et les soldes après opération.')]
class ListTransactionsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'account_id' => 'nullable|integer|min:1',
            'category' => 'nullable|string|in:expense,transfer,income',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ], [
            'category.in' => 'La catégorie doit être : expense, transfer ou income.',
        ]);

        $perPage = min($input['limit'] ?? 20, 100);
        $page = $input['page'] ?? 1;

        $query = AccountTransaction::query()
            ->select(['id', 'account_id', 'transaction_type_id', 'amount', 'balance_before', 'balance_after', 'transaction_date', 'reference_number', 'recipient_name', 'expense_category_id', 'related_account_id', 'notes'])
            ->with([
                'account:id,name',
                'transactionType:id,name,category',
            ])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if (! empty($input['account_id'])) {
            $query->where('account_id', (int) $input['account_id']);
        }
        if (! empty($input['category'])) {
            $query->whereHas('transactionType', fn ($q) => $q->where('category', $input['category']));
        }
        if (! empty($input['date_from'])) {
            $query->where('transaction_date', '>=', $input['date_from']);
        }
        if (! empty($input['date_to'])) {
            $query->where('transaction_date', '<=', $input['date_to'].' 23:59:59');
        }

        $total = (clone $query)->count();
        $transactions = $query->forPage($page, $perPage)->get();

        return Response::json([
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
            'transactions' => $transactions->map(fn (AccountTransaction $transaction) => [
                'id' => $transaction->id,
                'account' => $transaction->account?->name,
                'type' => $transaction->transactionType?->name,
                'category' => $transaction->transactionType?->category,
                'amount' => (float) $transaction->amount,
                'balance_after' => (float) $transaction->balance_after,
                'transaction_date' => $transaction->transaction_date?->toISOString(),
                'reference_number' => $transaction->reference_number,
                'recipient_name' => $transaction->recipient_name,
                'notes' => $transaction->notes,
            ])->toArray(),
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
                ->description('Filtrer par identifiant de compte (voir list-accounts-tool).'),
            'category' => $schema->string()
                ->description("Filtrer par catégorie de transaction : 'expense', 'transfer' ou 'income'.")
                ->enum(['expense', 'transfer', 'income']),
            'date_from' => $schema->string()
                ->description('Date de début de la période (format Y-m-d).'),
            'date_to' => $schema->string()
                ->description('Date de fin de la période (format Y-m-d).'),
            'limit' => $schema->integer()
                ->description('Nombre de résultats par page (1 à 100, 20 par défaut).')
                ->min(1)
                ->max(100),
            'page' => $schema->integer()
                ->description('Numéro de page (1 par défaut).')
                ->min(1),
        ];
    }
}
