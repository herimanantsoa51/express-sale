<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Controllers\AccountController;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée ou met à jour un compte de trésorerie. action=create : name, account_type_id (voir list-account-types-tool), initial_balance requis. action=update : name, account_number, notes et is_active modifiables (le solde ne se modifie pas, il évolue via les transactions).')]
class ManageAccountTool extends Tool
{
    /**
     * Les méthodes du contrôleur sont réutilisées telles quelles (avec leurs
     * FormRequests) : une seule source de vérité pour la comptabilité.
     */
    public function handle(Request $request, AccountController $controller): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:create,update',
            'account_id' => 'nullable|integer|min:1',
            'account_type_id' => 'nullable|integer|min:1|exists:account_types,id',
            'name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'initial_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ], [
            'action.required' => "L'action est requise (action) : create ou update.",
            'account_id.required' => "L'identifiant du compte est requis pour action=update (voir list-accounts-tool).",
        ]);

        try {
            if ($input['action'] === 'create') {
                if (empty($input['name']) || empty($input['account_type_id']) || ! isset($input['initial_balance'])) {
                    return Response::error("Pour action=create : name, account_type_id et initial_balance sont requis.");
                }

                $response = $controller->store($this->formRequest(StoreAccountRequest::class, $input));
                $data = $response->getData(true);

                return Response::json([
                    'message' => 'Compte créé avec succès.',
                    'account' => data_get($data, 'data.id') !== null ? [
                        'id' => data_get($data, 'data.id'),
                        'name' => data_get($data, 'data.name'),
                        'current_balance' => (float) data_get($data, 'data.current_balance'),
                    ] : $data,
                ]);
            }

            $account = Account::find($input['account_id'] ?? 0);
            if (! $account) {
                return Response::error("Aucun compte ne correspond à l'identifiant fourni — voir list-accounts-tool.");
            }

            $response = $controller->update($this->formRequest(UpdateAccountRequest::class, $input), $account);
            $data = $response->getData(true);
            $account->refresh();

            return Response::json([
                'message' => 'Compte mis à jour avec succès.',
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'account_number' => $account->account_number,
                    'current_balance' => (float) $account->current_balance,
                    'is_active' => $account->is_active,
                ],
            ]);
        } catch (ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::ACCOUNT_TRANSACTION_CREATED,
                ' la gestion du compte via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec : '.$e->getMessage());
        }
    }

    private function formRequest(string $class, array $params): object
    {
        $formRequest = $class::create('/', 'POST', array_filter($params, fn ($v) => $v !== null));
        $formRequest->setContainer(app());
        $formRequest->validateResolved();

        return $formRequest;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description("'create' ou 'update'.")
                ->required()
                ->enum(['create', 'update']),
            'account_id' => $schema->integer()
                ->description("Identifiant du compte (requis pour action=update)."),
            'account_type_id' => $schema->integer()
                ->description("Type de compte (requis pour create, voir list-account-types-tool)."),
            'name' => $schema->string()
                ->description('Nom du compte (ex. "Caisse principale", "MVola boutique").')
                ->max(255),
            'account_number' => $schema->string()
                ->description('Numéro de compte / téléphone (optionnel).')
                ->max(100),
            'initial_balance' => $schema->number()
                ->description("Solde initial en Ariary (requis pour create)."),
            'notes' => $schema->string()
                ->description('Notes sur le compte.')
                ->max(2000),
            'is_active' => $schema->boolean()
                ->description('Activer ou désactiver le compte.'),
        ];
    }
}
