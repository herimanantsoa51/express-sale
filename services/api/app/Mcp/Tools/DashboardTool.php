<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\DashboardController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Tableau de bord général de la boutique : synthèse du jour et de la période (ventes, encaissements, crédits en cours, réservations actives, alertes de stock...). Mêmes données que la page d'accueil du frontend.")]
class DashboardTool extends Tool
{
    public function handle(Request $request, DashboardController $dashboardController): Response
    {
        $input = $request->validate([
            'period' => 'nullable|string|in:today,week,month,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $httpRequest = HttpRequest::create('/', 'GET', array_filter([
            'period' => $input['period'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
        ]));

        try {
            $data = $dashboardController->index($httpRequest)->getData(true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        return Response::json($data);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->description("Période : 'today', 'week', 'month' ou 'year'.")
                ->enum(['today', 'week', 'month', 'year']),
            'start_date' => $schema->string()
                ->description('Date de début personnalisée (format Y-m-d).'),
            'end_date' => $schema->string()
                ->description('Date de fin personnalisée (format Y-m-d).'),
        ];
    }
}
