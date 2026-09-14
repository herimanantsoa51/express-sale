<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\FreightForwarder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Crée un nouveau transitaire (nom unique, type de transport aérien ou maritime obligatoire, contact, notes). Le transitaire pourra ensuite être associé aux commandes via create-stock-receipt-tool.')]
class CreateFreightForwarderTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'name' => 'required|string|max:255|unique:freight_forwarders,name',
            'type' => 'required|string|in:aerien,maritime',
            'contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [
            'name.required' => 'Le nom du transitaire est requis (name).',
            'name.unique' => "Un transitaire portant ce nom existe déjà — utiliser list-freight-forwarders-tool pour le retrouver.",
            'type.required' => "Le type de transport est requis (type) : 'aerien' ou 'maritime'.",
            'type.in' => "Le type de transport doit être : 'aerien' ou 'maritime'.",
        ]);

        try {
            $forwarder = FreightForwarder::create([
                'name' => $input['name'],
                'type' => $input['type'],
                'contact' => $input['contact'] ?? null,
                'notes' => $input['notes'] ?? null,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::FREIGHT_FORWARDER_CREATED,
                ' la création du transitaire via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec de la création du transitaire : '.$e->getMessage());
        }

        ActivityLogger::success(
            ActivityAction::FREIGHT_FORWARDER_CREATED,
            "a créé le transitaire {$forwarder->name} via MCP",
            [
                'model_type' => FreightForwarder::class,
                'model_id' => $forwarder->id,
                'metadata' => $forwarder->toArray(),
            ],
            "/transitaires/{$forwarder->id}"
        );

        return Response::json([
            'message' => 'Transitaire créé avec succès.',
            'freight_forwarder' => [
                'id' => $forwarder->id,
                'name' => $forwarder->name,
                'type' => $forwarder->type,
                'contact' => $forwarder->contact,
                'is_active' => $forwarder->is_active,
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
                ->description('Nom du transitaire (unique).')
                ->required()
                ->max(255),
            'type' => $schema->string()
                ->description("Type de transport : 'aerien' ou 'maritime'.")
                ->required()
                ->enum(['aerien', 'maritime']),
            'contact' => $schema->string()
                ->description('Contact (téléphone, email...).')
                ->max(255),
            'notes' => $schema->string()
                ->description('Notes sur le transitaire (routes, délais, tarifs...).')
                ->max(2000),
        ];
    }
}
