<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Met à jour un client existant (nom, téléphone, adresse, fiabilité, points de fidélité, limite de crédit, statut). Au moins un champ à modifier doit être fourni.')]
class UpdateCustomerTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'reliability_score' => 'nullable|numeric|min:0|max:10',
            'loyalty_points' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_extra_customer' => 'nullable|boolean',
        ], [
            'id.required' => "Vous devez préciser l'identifiant du client (id).",
            'reliability_score.min' => 'Le score de fiabilité doit être entre 0 et 10.',
            'reliability_score.max' => 'Le score de fiabilité doit être entre 0 et 10.',
        ]);

        $customer = Customer::find($input['id']);
        if (! $customer) {
            return Response::error("Aucun client ne correspond à l'identifiant fourni.");
        }

        $phone = $input['phone'] ?? null;
        if ($phone !== null && $phone !== '' && Customer::where('phone', $phone)->where('id', '!=', $customer->id)->exists()) {
            return Response::error("Le numéro de téléphone {$phone} est déjà utilisé par un autre client.");
        }

        $data = [];
        foreach (['name', 'address', 'reliability_score', 'loyalty_points', 'credit_limit', 'notes', 'is_active', 'is_extra_customer'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }
        if ($phone !== null) {
            $data['phone'] = $phone !== '' ? $phone : null;
        }

        if ($data === []) {
            return Response::error('Aucun champ à mettre à jour. Fournissez au moins un champ (name, phone, address, reliability_score, loyalty_points, credit_limit, notes, is_active).');
        }

        $customer->update($data);

        ActivityLogger::success(
            ActivityAction::CUSTOMER_UPDATED,
            " a modifié le client {$customer->customer_number} via MCP",
            [
                'model_type' => Customer::class,
                'model_id' => $customer->id,
                'metadata' => $customer->toArray(),
            ],
            "clients/{$customer->id}"
        );

        return Response::json([
            'message' => 'Client mis à jour avec succès.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'customer_number' => $customer->customer_number,
                'reliability_score' => (float) $customer->reliability_score,
                'loyalty_points' => (int) $customer->loyalty_points,
                'credit_limit' => (float) $customer->credit_limit,
                'is_active' => (bool) $customer->is_active,
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
            'id' => $schema->integer()
                ->description('Identifiant du client à modifier.')
                ->required()
                ->min(1),
            'name' => $schema->string()
                ->description('Nouveau nom du client.')
                ->max(255),
            'phone' => $schema->string()
                ->description('Nouveau numéro de téléphone (unique).')
                ->max(50),
            'address' => $schema->string()
                ->description('Nouvelle adresse du client.')
                ->max(500),
            'reliability_score' => $schema->number()
                ->description('Nouveau score de fiabilité entre 0 et 10.')
                ->min(0)
                ->max(10),
            'loyalty_points' => $schema->integer()
                ->description('Nouveaux points de fidélité.')
                ->min(0),
            'credit_limit' => $schema->number()
                ->description('Nouvelle limite de crédit en Ariary.')
                ->min(0),
            'notes' => $schema->string()
                ->description('Nouvelles notes internes.'),
            'is_active' => $schema->boolean()
                ->description('Activer ou désactiver le client.'),
            'is_extra_customer' => $schema->boolean()
                ->description('Marquer comme client extra.'),
        ];
    }
}
