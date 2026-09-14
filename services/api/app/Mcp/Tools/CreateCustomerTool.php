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

#[Description('Crée un nouveau client (nom, téléphone, adresse, limite de crédit, points de fidélité). Le numéro client est généré automatiquement.')]
class CreateCustomerTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'reliability_score' => 'nullable|numeric|min:0|max:10',
            'loyalty_points' => 'nullable|integer|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_extra_customer' => 'nullable|boolean',
        ], [
            'name.required' => 'Le nom du client est requis.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'phone.max' => 'Le téléphone ne peut pas dépasser 50 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'address.max' => "L'adresse ne peut pas dépasser 500 caractères.",
            'reliability_score.min' => 'Le score de fiabilité doit être entre 0 et 10.',
            'reliability_score.max' => 'Le score de fiabilité doit être entre 0 et 10.',
            'loyalty_points.min' => 'Les points de fidélité ne peuvent pas être négatifs.',
            'credit_limit.min' => 'La limite de crédit ne peut pas être négative.',
        ]);

        $phone = $input['phone'] ?? null;
        if ($phone !== null && $phone !== '' && Customer::where('phone', $phone)->exists()) {
            return Response::error("Le numéro de téléphone {$phone} est déjà utilisé par un autre client.");
        }

        // Valeurs par défaut alignées sur CustomerController@store
        $customer = Customer::create([
            'name' => $input['name'],
            'phone' => $phone !== '' ? $phone : null,
            'address' => $input['address'] ?? null,
            'reliability_score' => $input['reliability_score'] ?? 5.00,
            'loyalty_points' => $input['loyalty_points'] ?? 0,
            'credit_limit' => $input['credit_limit'] ?? 100000,
            'notes' => $input['notes'] ?? null,
            'is_active' => $input['is_active'] ?? true,
            'is_extra_customer' => $input['is_extra_customer'] ?? false,
            // customer_number est généré automatiquement par l'événement "creating" du modèle
        ]);

        ActivityLogger::success(
            ActivityAction::CUSTOMER_CREATED,
            " a créé le client {$customer->customer_number} via MCP",
            [
                'model_type' => Customer::class,
                'model_id' => $customer->id,
                'metadata' => $customer->toArray(),
            ],
            "clients/{$customer->id}"
        );

        return Response::json([
            'message' => 'Client créé avec succès.',
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
            'name' => $schema->string()
                ->description('Nom du client.')
                ->required()
                ->max(255),
            'phone' => $schema->string()
                ->description('Numéro de téléphone du client (unique).')
                ->max(50),
            'address' => $schema->string()
                ->description('Adresse du client.')
                ->max(500),
            'reliability_score' => $schema->number()
                ->description('Score de fiabilité entre 0 et 10 (5 par défaut).')
                ->min(0)
                ->max(10),
            'loyalty_points' => $schema->integer()
                ->description('Points de fidélité initiaux (0 par défaut).')
                ->min(0),
            'credit_limit' => $schema->number()
                ->description('Limite de crédit en Ariary (100000 par défaut).')
                ->min(0),
            'notes' => $schema->string()
                ->description('Notes internes sur le client.'),
            'is_active' => $schema->boolean()
                ->description('Client actif (true par défaut).'),
            'is_extra_customer' => $schema->boolean()
                ->description('Client extra (false par défaut).'),
        ];
    }
}
