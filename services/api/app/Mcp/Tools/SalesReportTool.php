<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\SalesStatisticsController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Rapports de ventes détaillés : vue d'ensemble, évolution temporelle, top produits, par catégorie, par vendeur, par moyen de paiement, remises, stats crédits ou stats réservations. Période paramétrable (start_date/end_date ou period).")]
class SalesReportTool extends Tool
{
    private const REPORT_METHODS = [
        'overview' => 'overview',
        'timeline' => 'timeline',
        'top-products' => 'topProducts',
        'by-category' => 'byCategory',
        'by-seller' => 'bySeller',
        'by-payment-method' => 'byPaymentMethod',
        'discounts' => 'discounts',
        'credits' => 'credits',
        'reservations' => 'reservations',
    ];

    public function handle(Request $request, SalesStatisticsController $statisticsController): Response
    {
        $input = $request->validate([
            'report' => 'nullable|string|in:'.implode(',', array_keys(self::REPORT_METHODS)),
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|string|in:today,week,month,year',
        ]);

        $method = self::REPORT_METHODS[$input['report'] ?? 'overview'];

        $httpRequest = HttpRequest::create('/', 'GET', array_filter([
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'period' => $input['period'] ?? null,
        ]));

        try {
            $data = $statisticsController->{$method}($httpRequest)->getData(true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        }

        return Response::json($data);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'report' => $schema->string()
                ->description("Type de rapport : 'overview' (par défaut), 'timeline', 'top-products', 'by-category', 'by-seller', 'by-payment-method', 'discounts', 'credits' ou 'reservations'.")
                ->enum(array_keys(self::REPORT_METHODS)),
            'start_date' => $schema->string()
                ->description('Date de début (format Y-m-d).'),
            'end_date' => $schema->string()
                ->description('Date de fin (format Y-m-d, incluse).'),
            'period' => $schema->string()
                ->description("Période prédéfinie si pas de dates explicites : 'today', 'yesterday', 'week', 'month' ou 'year'.")
                ->enum(['today', 'yesterday', 'week', 'month', 'year']),
        ];
    }
}
