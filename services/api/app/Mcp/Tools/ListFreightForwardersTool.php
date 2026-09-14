<?php

namespace App\Mcp\Tools;

use App\Models\FreightForwarder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Liste les transitaires avec leur type de transport (aérien ou maritime), leur score de service et leur nombre de réapprovisionnements. À utiliser pour trouver le freight_forwarder_id avant de créer une commande. Filtres : type, recherche, actifs uniquement.')]
class ListFreightForwardersTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $input = $request->validate([
            'active_only' => 'nullable|boolean',
            'type' => 'nullable|string|in:aerien,maritime',
            'search' => 'nullable|string|max:255',
        ], [
            'type.in' => "Le type doit être : 'aerien' ou 'maritime'.",
        ]);

        $query = FreightForwarder::query()
            ->select(['id', 'name', 'type', 'contact', 'notes', 'service_score', 'is_active'])
            ->withCount('stockReceipts')
            ->orderBy('name');

        if ($input['active_only'] ?? true) {
            $query->where('is_active', true);
        }

        if (! empty($input['type'])) {
            $query->where('type', $input['type']);
        }

        if (! empty($input['search'])) {
            $query->where(function ($q) use ($input) {
                $q->where('name', 'ILIKE', "%{$input['search']}%")
                    ->orWhere('contact', 'ILIKE', "%{$input['search']}%");
            });
        }

        $forwarders = $query->get();

        return Response::json([
            'total' => $forwarders->count(),
            'freight_forwarders' => $forwarders->map(fn (FreightForwarder $forwarder) => [
                'id' => $forwarder->id,
                'name' => $forwarder->name,
                'type' => $forwarder->type,
                'contact' => $forwarder->contact,
                'notes' => $forwarder->notes,
                'service_score' => $forwarder->service_score !== null ? (float) $forwarder->service_score : null,
                'is_active' => $forwarder->is_active,
                'receipt_count' => (int) $forwarder->stock_receipts_count,
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
                ->description('Ne retourner que les transitaires actifs (true par défaut).'),
            'type' => $schema->string()
                ->description("Filtrer par type de transport : 'aerien' ou 'maritime'.")
                ->enum(['aerien', 'maritime']),
            'search' => $schema->string()
                ->description('Recherche textuelle sur le nom ou le contact.'),
        ];
    }
}
