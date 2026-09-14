<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ActivityLogController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Journaux d'activité : liste filtrable (utilisateur, action, modèle, statut, période), statistiques globales, échecs, par modèle ou par catégorie. Lecture seule — utile pour auditer qui a fait quoi.")]
class ListActivityLogsTool extends Tool
{
    private const REPORT_METHODS = [
        'logs' => 'index',
        'statistics' => 'statistics',
        'failures' => 'failures',
        'by-model' => 'byModel',
        'by-category' => 'actionsByCategory',
    ];

    public function handle(Request $request, ActivityLogController $controller): Response
    {
        $input = $request->validate([
            'report' => 'nullable|string|in:logs,statistics,failures,by-model,by-category',
            'user_id' => 'nullable|integer|min:1',
            'action' => 'nullable|string|max:100',
            'model_type' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:success,failed,error',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $method = self::REPORT_METHODS[$input['report'] ?? 'logs'];

        $httpRequest = HttpRequest::create('/', 'GET', array_filter([
            'user_id' => $input['user_id'] ?? null,
            'action' => $input['action'] ?? null,
            'model_type' => $input['model_type'] ?? null,
            'status' => $input['status'] ?? null,
            'date_from' => $input['date_from'] ?? null,
            'date_to' => $input['date_to'] ?? null,
            'per_page' => $input['limit'] ?? null,
            'page' => $input['page'] ?? null,
        ]));

        try {
            $response = $controller->{$method}($httpRequest);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        return Response::json($response->getData(true));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'report' => $schema->string()
                ->description("'logs' (liste filtrable, défaut), 'statistics', 'failures', 'by-model' ou 'by-category'.")
                ->enum(['logs', 'statistics', 'failures', 'by-model', 'by-category']),
            'user_id' => $schema->integer()
                ->description('Filtrer par utilisateur.'),
            'action' => $schema->string()
                ->description("Filtrer par action (ex. 'sale_created', 'stock_receipt_validated')."),
            'model_type' => $schema->string()
                ->description("Filtrer par type de modèle concerné."),
            'status' => $schema->string()
                ->description("Filtrer par statut : 'success', 'failed' ou 'error'.")
                ->enum(['success', 'failed', 'error']),
            'date_from' => $schema->string()
                ->description('Date de début (format Y-m-d).'),
            'date_to' => $schema->string()
                ->description('Date de fin (format Y-m-d).'),
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
