<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Controllers\PlannedExpenseController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Dépenses planifiées (loyer, salaires, abonnements...) : liste avec statistiques, création (montant estimé, fréquence, date de début), mise à jour ou passage à l'échéance suivante après paiement. Les paiements réels se font via create-expense-tool avec planned_expense_id.")]
class ManagePlannedExpenseTool extends Tool
{
    public function handle(Request $request, PlannedExpenseController $controller): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:list,create,update,mark-paid',
            'planned_expense_id' => 'nullable|integer|min:1',
            'expense_category_id' => 'nullable|integer|min:1',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'estimated_amount' => 'nullable|numeric|min:0',
            'frequency' => 'nullable|string|in:daily,weekly,monthly,yearly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'start_date' => 'nullable|date',
            'recipient_name' => 'nullable|string|max:255',
        ], [
            'action.required' => "L'action est requise (action) : list, create, update ou mark-paid.",
        ]);

        $httpRequest = HttpRequest::create('/', 'POST', array_filter([
            'expense_category_id' => $input['expense_category_id'] ?? null,
            'name' => $input['name'] ?? null,
            'description' => $input['description'] ?? null,
            'estimated_amount' => $input['estimated_amount'] ?? null,
            'frequency' => $input['frequency'] ?? null,
            'day_of_week' => $input['day_of_week'] ?? null,
            'day_of_month' => $input['day_of_month'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'recipient_name' => $input['recipient_name'] ?? null,
        ], fn ($v) => $v !== null));

        try {
            $response = match ($input['action']) {
                'list' => $controller->index($httpRequest),
                'create' => $controller->store($httpRequest),
                'update' => $controller->update(
                    $httpRequest,
                    $this->resolve($input['planned_expense_id'] ?? 0)
                ),
                'mark-paid' => $controller->markPaid(
                    $this->resolve($input['planned_expense_id'] ?? 0)
                ),
                default => Response::error('Action inconnue.'),
            };

            if ($response instanceof Response) {
                return $response;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::EXPENSE_CATEGORY_CREATED,
                ' la gestion de la dépense planifiée via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec : '.$e->getMessage());
        }

        return Response::json($response->getData(true));
    }

    private function resolve(int $id): \App\Models\PlannedExpense
    {
        return \App\Models\PlannedExpense::findOrFail($id);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description("'list' (avec stats), 'create', 'update' ou 'mark-paid' (recalcule la prochaine échéance après un paiement réel).")
                ->required()
                ->enum(['list', 'create', 'update', 'mark-paid']),
            'planned_expense_id' => $schema->integer()
                ->description("Identifiant de la dépense planifiée (requis pour update et mark-paid)."),
            'expense_category_id' => $schema->integer()
                ->description("Catégorie de dépense (requis pour create, voir list-expense-categories-tool)."),
            'name' => $schema->string()
                ->description('Nom de la charge (ex. "Loyer boutique").')
                ->max(255),
            'estimated_amount' => $schema->number()
                ->description('Montant estimé en Ariary (requis pour create).'),
            'frequency' => $schema->string()
                ->description("Fréquence (requis pour create) : daily, weekly, monthly ou yearly.")
                ->enum(['daily', 'weekly', 'monthly', 'yearly']),
            'day_of_week' => $schema->integer()
                ->description('Jour de la semaine (1-7, pour frequency=weekly).'),
            'day_of_month' => $schema->integer()
                ->description('Jour du mois (1-31, pour frequency=monthly).'),
            'start_date' => $schema->string()
                ->description('Date de début (requis pour create, format Y-m-d).'),
            'recipient_name' => $schema->string()
                ->description('Bénéficiaire (bailleur, employé...).')
                ->max(255),
            'description' => $schema->string()
                ->description('Description de la charge.')
                ->max(2000),
        ];
    }
}
