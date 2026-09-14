<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\SalesStatisticsController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Évolution financière dans le temps (revenus, profits) sur une période, groupée par jour, semaine ou mois. Idéal pour analyser les tendances. Les deux dates sont obligatoires.')]
class FinancialTimelineTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, SalesStatisticsController $statisticsController): Response
    {
        $input = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'grouping' => 'required|string|in:day,week,month',
        ], [
            'start_date.required' => 'La date de début est requise (start_date, format Y-m-d).',
            'end_date.required' => 'La date de fin est requise (end_date, format Y-m-d).',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'grouping.required' => "Le regroupement est requis (grouping) : day, week ou month.",
            'grouping.in' => 'Le regroupement doit être : day, week ou month.',
        ]);

        $httpRequest = HttpRequest::create('/', 'GET', $input);

        try {
            $data = $statisticsController->financialTimeline($httpRequest)->getData(true);
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
            'start_date' => $schema->string()
                ->description('Date de début (format Y-m-d).')
                ->required(),
            'end_date' => $schema->string()
                ->description('Date de fin (format Y-m-d, incluse).')
                ->required(),
            'grouping' => $schema->string()
                ->description("Regroupement temporel : 'day', 'week' ou 'month'.")
                ->required()
                ->enum(['day', 'week', 'month']),
        ];
    }
}
