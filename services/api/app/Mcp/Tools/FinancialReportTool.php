<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\SalesStatisticsController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Rapports financiers de la boutique : dashboard global (revenus, coûts d'approvisionnement, dépenses opérationnelles, pertes, profits, marges), détail des profits (par produit, par catégorie), détail des dépenses (réelles vs planifiées) ou détail des pertes de stock (par type, par produit). Période paramétrable.")]
class FinancialReportTool extends Tool
{
    /**
     * Les méthodes publiques du contrôleur de statistiques sont réutilisées
     * telles quelles : une seule source de vérité pour les calculs financiers.
     */
    private const REPORT_METHODS = [
        'dashboard' => 'financialDashboard',
        'profits' => 'profitsOverview',
        'expenses' => 'expensesOverview',
        'losses' => 'lossesOverview',
    ];

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, SalesStatisticsController $statisticsController): Response
    {
        $input = $request->validate([
            'report' => 'nullable|string|in:dashboard,profits,expenses,losses',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|string|in:week,month,year',
        ], [
            'report.in' => 'Le rapport doit être : dashboard, profits, expenses ou losses.',
            'end_date.after_or_equal' => "La date de fin doit être postérieure ou égale à la date de début.",
        ]);

        $method = self::REPORT_METHODS[$input['report'] ?? 'dashboard'];

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

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'report' => $schema->string()
                ->description("Type de rapport : 'dashboard' (vue globale par défaut), 'profits' (bénéfices par produit/catégorie), 'expenses' (dépenses réelles vs planifiées) ou 'losses' (pertes de stock).")
                ->enum(['dashboard', 'profits', 'expenses', 'losses']),
            'start_date' => $schema->string()
                ->description('Date de début (format Y-m-d).'),
            'end_date' => $schema->string()
                ->description('Date de fin (format Y-m-d, incluse).'),
            'period' => $schema->string()
                ->description("Période prédéfinie si pas de dates explicites : 'week' (semaine en cours), 'month' (mois en cours) ou 'year' (année en cours)."),
        ];
    }
}
