<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\CurrencyRateController;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description("Taux de change : consulter les taux actifs (euro, yuan, dollar, dirham, baht → Ariary), l'historique, ou convertir un montant. Utile avant de négocier une commande fournisseur en devise étrangère.")]
class CurrencyTool extends Tool
{
    public function handle(Request $request, CurrencyRateController $controller): Response
    {
        $input = $request->validate([
            'action' => 'required|string|in:current,history,convert',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:euro,yuan,dollar,dirham,baht',
        ], [
            'action.required' => "L'action est requise (action) : current, history ou convert.",
            'amount.required' => "Le montant est requis pour action=convert (amount, currency).",
        ]);

        try {
            $response = match ($input['action']) {
                'current' => $controller->current(),
                'history' => $controller->index(new HttpRequest),
                'convert' => $controller->convert(HttpRequest::create('/', 'POST', array_filter([
                    'amount' => $input['amount'] ?? null,
                    'currency' => $input['currency'] ?? null,
                ]))),
                default => Response::error('Action inconnue.'),
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
            'action' => $schema->string()
                ->description("'current' (taux actifs), 'history' (historique des taux) ou 'convert' (conversion en Ariary).")
                ->required()
                ->enum(['current', 'history', 'convert']),
            'amount' => $schema->number()
                ->description("Montant à convertir (requis pour action=convert)."),
            'currency' => $schema->string()
                ->description("Devise source (requis pour convert) : euro, yuan, dollar, dirham ou baht.")
                ->enum(['euro', 'yuan', 'dollar', 'dirham', 'baht']),
        ];
    }
}
