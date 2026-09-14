<?php

namespace App\Mcp\Tools;

use App\Models\Location;
use App\Models\ProductVariantLocation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les emplacements de stockage disponibles avec leur stock agrégé (nombre de variantes, quantités disponibles et réservées). Utiliser ce tool pour découvrir les location_id à fournir lors de la création d\'une vente ou d\'une réservation.')]
class ListLocationsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'active_only' => 'nullable|boolean',
            'warehouse' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Location::query()
            ->select(['id', 'name', 'code', 'warehouse', 'description', 'is_active'])
            ->orderBy('name');

        if ($input['active_only'] ?? true) {
            $query->where('is_active', true);
        }

        if (! empty($input['warehouse'])) {
            $query->where('warehouse', 'ILIKE', "%{$input['warehouse']}%");
        }

        if (! empty($input['search'])) {
            $search = $input['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('warehouse', 'ILIKE', "%{$search}%");
            });
        }

        $locations = $query->get();

        // Agrégation du stock en une seule requête groupée (pas de N+1)
        $stockByLocation = ProductVariantLocation::query()
            ->select('location_id')
            ->selectRaw('COUNT(DISTINCT variant_id) AS variant_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) AS available_quantity')
            ->selectRaw('COALESCE(SUM(reserved_quantity), 0) AS reserved_quantity')
            ->whereIn('location_id', $locations->isEmpty() ? [0] : $locations->pluck('id'))
            ->groupBy('location_id')
            ->get()
            ->keyBy('location_id')
            ->all();

        return Response::json([
            'total' => $locations->count(),
            'locations' => $locations->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
                'warehouse' => $location->warehouse,
                'description' => $location->description,
                'is_active' => $location->is_active,
                'variant_count' => isset($stockByLocation[$location->id]) ? (int) $stockByLocation[$location->id]->variant_count : 0,
                'available_quantity' => isset($stockByLocation[$location->id]) ? (int) $stockByLocation[$location->id]->available_quantity : 0,
                'reserved_quantity' => isset($stockByLocation[$location->id]) ? (int) $stockByLocation[$location->id]->reserved_quantity : 0,
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
                ->description('Ne retourner que les emplacements actifs (true par défaut).'),
            'warehouse' => $schema->string()
                ->description("Filtrer par entrepôt (correspondance partielle, ex. 'Antanimena')."),
            'search' => $schema->string()
                ->description('Recherche textuelle sur le nom, le code ou l\'entrepôt de l\'emplacement.'),
        ];
    }
}
