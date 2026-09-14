<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Controllers\CashCountController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Comptage de caisse : lister les comptages (avec écart calculé) ou en enregistrer un nouveau par coupures de billets/pièces (denominations : [{denomination, quantity}]). Un comptage par date. Le total et l'écart avec la théorie sont calculés automatiquement.")]
class ManageCashCountTool extends Tool
{
    public function handle(Request $request, CashCountController $controller): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:list,create',
            'count_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'denominations' => 'nullable|array|min:1',
            'denominations.*.denomination' => 'required|integer|min:1',
            'denominations.*.quantity' => 'required|integer|min:0',
        ], [
            'action.required' => "L'action est requise (action) : list ou create.",
            'count_date.required' => 'La date du comptage est requise pour create (count_date, format Y-m-d).',
            'denominations.required' => "Les coupures sont requises pour create (denominations : [{denomination, quantity}], ex. [{denomination: 10000, quantity: 12}]).",
        ]);

        $httpRequest = HttpRequest::create('/', 'POST', array_filter([
            'count_date' => $input['count_date'] ?? null,
            'notes' => $input['notes'] ?? null,
            'denominations' => $input['denominations'] ?? null,
        ], fn ($v) => $v !== null));

        try {
            $response = match ($input['action']) {
                'list' => $controller->index($httpRequest),
                'create' => $controller->store($httpRequest),
                default => Response::error('Action inconnue.'),
            };

            if ($response instanceof Response) {
                return $response;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::CASH_COUNT_CREATED,
                ' la gestion du comptage de caisse via MCP a échoué',
                ['error' => $e->getMessage(), 'input' => $input]
            );

            return Response::error('Échec : '.$e->getMessage());
        }

        return Response::json($response->getData(true));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description("'list' (historique avec écarts) ou 'create' (nouveau comptage).")
                ->required()
                ->enum(['list', 'create']),
            'count_date' => $schema->string()
                ->description("Date du comptage (requis pour create, format Y-m-d, unique par date)."),
            'notes' => $schema->string()
                ->description('Notes sur le comptage.')
                ->max(2000),
            'denominations' => $schema->array()
                ->description("Coupures comptées (requis pour create). Ex. [{denomination: 10000, quantity: 12}, {denomination: 5000, quantity: 30}].")
                ->min(1)
                ->items(
                    $schema->object([
                        'denomination' => $schema->integer()
                            ->description('Valeur de la coupure en Ariary.')
                            ->required()
                            ->min(1),
                        'quantity' => $schema->integer()
                            ->description('Nombre de billets/pièces de cette coupure.')
                            ->required()
                            ->min(0),
                    ])
                ),
        ];
    }
}
