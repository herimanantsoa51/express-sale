<?php

namespace App\Mcp\Tools;

use App\Models\Account;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les comptes de trésorerie avec leur solde actuel et leur type (caisse, mobile money, banque). Utiliser ce tool pour découvrir les account_id à fournir pour encaisser un paiement, un acompte ou une échéance de crédit.')]
class ListAccountsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'active_only' => 'nullable|boolean',
            'type' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Account::query()
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.account_number',
                'accounts.current_balance',
                'accounts.initial_balance',
                'accounts.is_active',
                'account_types.code AS type_code',
                'account_types.display_name AS type_name',
            ])
            ->orderBy('account_types.code')
            ->orderBy('accounts.name');

        if ($input['active_only'] ?? true) {
            $query->where('accounts.is_active', true);
        }

        if (! empty($input['type'])) {
            $query->where('account_types.code', 'ILIKE', "%{$input['type']}%");
        }

        if (! empty($input['search'])) {
            $search = $input['search'];
            $query->where(function ($q) use ($search) {
                $q->where('accounts.name', 'ILIKE', "%{$search}%")
                    ->orWhere('accounts.account_number', 'ILIKE', "%{$search}%");
            });
        }

        $accounts = $query->get();

        return Response::json([
            'total' => $accounts->count(),
            'total_balance' => (float) $accounts->sum('current_balance'),
            'accounts' => $accounts->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'account_number' => $account->account_number,
                'type' => strtolower((string) $account->type_code),
                'type_name' => $account->type_name,
                'current_balance' => (float) $account->current_balance,
                'initial_balance' => (float) $account->initial_balance,
                'is_active' => $account->is_active,
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
            'active_only' => $schema->boolean()
                ->description('Ne retourner que les comptes actifs (true par défaut).'),
            'type' => $schema->string()
                ->description("Filtrer par code de type de compte (correspondance partielle, ex. 'CASH', 'MOBILE_MONEY', 'BANK')."),
            'search' => $schema->string()
                ->description('Recherche textuelle sur le nom ou le numéro du compte.'),
        ];
    }
}
