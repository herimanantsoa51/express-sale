<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\StockMovementController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Traçabilité des mouvements de stock : liste filtrable (variante, emplacement, type, période), mouvements groupés, statistiques ou détail d'un batch. Répond aux questions « d'où vient ce stock ? » et « où est passée cette marchandise ? ».")]
class StockMovementsReportTool extends Tool
{
    public function handle(Request $request, StockMovementController $controller): Response
    {
        $input = $request->validate([
            'report' => 'nullable|string|in:list,grouped,statistics,batch',
            'variant_id' => 'nullable|integer|min:1',
            'location_id' => 'nullable|integer|min:1',
            'movement_type' => 'nullable|string|max:30',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'batch_id' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $report = $input['report'] ?? 'list';
        $params = array_filter([
            'variant_id' => $input['variant_id'] ?? null,
            'location_id' => $input['location_id'] ?? null,
            'movement_type' => $input['movement_type'] ?? null,
            'date_from' => $input['date_from'] ?? null,
            'date_to' => $input['date_to'] ?? null,
            'batch_id' => $input['batch_id'] ?? null,
            'per_page' => $input['limit'] ?? null,
            'page' => $input['page'] ?? null,
        ]);

        $httpRequest = HttpRequest::create('/', 'GET', $params);

        try {
            $response = match ($report) {
                'grouped' => $controller->grouped($httpRequest),
                'statistics' => $controller->statistics($httpRequest),
                'batch' => isset($input['batch_id'])
                    ? $controller->batchDetails($input['batch_id'])
                    : Response::error("Le paramètre batch_id est requis pour report='batch'."),
                default => $controller->index($httpRequest),
            };

            if ($response instanceof Response) {
                return $response;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        return Response::json($response->getData(true));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'report' => $schema->string()
                ->description("'list' (défaut), 'grouped' (par batch), 'statistics' ou 'batch' (détail d'un batch, batch_id requis).")
                ->enum(['list', 'grouped', 'statistics', 'batch']),
            'variant_id' => $schema->integer()
                ->description('Filtrer par variante.'),
            'location_id' => $schema->integer()
                ->description("Filtrer par emplacement (source ou destination)."),
            'movement_type' => $schema->string()
                ->description("Type de mouvement : sale, reservation, credit, receipt, transfer, adjustment, loss, return..."),
            'date_from' => $schema->string()
                ->description('Date de début (format Y-m-d).'),
            'date_to' => $schema->string()
                ->description('Date de fin (format Y-m-d).'),
            'batch_id' => $schema->string()
                ->description("Identifiant de batch (requis pour report='batch')."),
            'limit' => $schema->integer()
                ->description('Nombre de résultats par page (1 à 100, 20 par défaut).')
                ->min(1)
                ->max(100),
            'page' => $schema->integer()
                ->description('Numéro de page.')
                ->min(1),
        ];
    }
}
