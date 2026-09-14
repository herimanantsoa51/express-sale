<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Controllers\LocationController;
use App\Models\Location;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Crée ou met à jour un emplacement de stockage (entrepôt, rayon, étagère). action=create : name, code (unique) et warehouse requis. action=update : champs modifiables au choix, identifié par location_id.")]
class ManageLocationTool extends Tool
{
    public function handle(Request $request, LocationController $controller): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:create,update',
            'location_id' => 'nullable|integer|min:1',
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100',
            'warehouse' => 'nullable|string|max:255',
            'aisle' => 'nullable|string|max:100',
            'shelf' => 'nullable|string|max:100',
            'bin' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'capacity' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'action.required' => "L'action est requise (action) : create ou update.",
        ]);

        $httpRequest = HttpRequest::create('/', 'POST', array_filter([
            'name' => $input['name'] ?? null,
            'code' => $input['code'] ?? null,
            'warehouse' => $input['warehouse'] ?? null,
            'aisle' => $input['aisle'] ?? null,
            'shelf' => $input['shelf'] ?? null,
            'bin' => $input['bin'] ?? null,
            'description' => $input['description'] ?? null,
            'capacity' => $input['capacity'] ?? null,
            'is_active' => $input['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        try {
            if ($input['action'] === 'create') {
                if (empty($input['name']) || empty($input['code']) || empty($input['warehouse'])) {
                    return Response::error("Pour action=create : name, code (unique) et warehouse sont requis.");
                }
                $location = Location::create($httpRequest->all());

                return Response::json([
                    'message' => 'Emplacement créé avec succès.',
                    'location' => ['id' => $location->id, 'name' => $location->name, 'code' => $location->code, 'warehouse' => $location->warehouse],
                ]);
            }

            $location = Location::find($input['location_id'] ?? 0);
            if (! $location) {
                return Response::error("Aucun emplacement ne correspond à l'identifiant fourni — voir list-locations-tool.");
            }

            $controller->update($httpRequest, $location->id);
            $location->refresh();

            return Response::json([
                'message' => 'Emplacement mis à jour avec succès.',
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'warehouse' => $location->warehouse,
                    'is_active' => $location->is_active,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::PRODUCT_UPDATED,
                ' la gestion de l\'emplacement via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec : '.$e->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description("'create' ou 'update'.")
                ->required()
                ->enum(['create', 'update']),
            'location_id' => $schema->integer()
                ->description("Identifiant de l'emplacement (requis pour update, voir list-locations-tool)."),
            'name' => $schema->string()
                ->description("Nom de l'emplacement (ex. 'Étagère A1').")
                ->max(255),
            'code' => $schema->string()
                ->description("Code unique de l'emplacement (ex. 'PRINC-A1').")
                ->max(100),
            'warehouse' => $schema->string()
                ->description("Entrepôt (ex. 'Antanimena').")
                ->max(255),
            'aisle' => $schema->string()
                ->description('Allée.')
                ->max(100),
            'shelf' => $schema->string()
                ->description('Étagère.')
                ->max(100),
            'bin' => $schema->string()
                ->description('Casier.')
                ->max(100),
            'capacity' => $schema->integer()
                ->description('Capacité maximale.')
                ->min(0),
            'description' => $schema->string()
                ->description('Description.')
                ->max(2000),
            'is_active' => $schema->boolean()
                ->description('Activer ou désactiver l\'emplacement.'),
        ];
    }
}
