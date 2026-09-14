<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\StockReceiptController;
use App\Http\Requests\ApplyCostAllocationRequest;
use App\Http\Requests\GetCostRecommendationsRequest;
use App\Models\StockReceipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Répartition des coûts d\'une commande arrivée (transport, douane, autres frais) sur les produits : action=recommend pour obtenir les recommandations par méthode (weight/price/quantity), action=apply pour appliquer les allocations par produit (freight_cost_per_unit, other_costs_per_unit). Étape obligatoire entre l\'arrivée (arrived) et la validation (validated) de la commande.')]
class AllocateStockReceiptCostsTool extends Tool
{
    /**
     * Les méthodes publiques du contrôleur de réceptions sont réutilisées
     * telles quelles (avec leurs FormRequests) : une seule source de vérité.
     */
    public function handle(Request $request, StockReceiptController $receiptController): Response
    {
        $input = $request->validate([
            'stock_receipt_id' => 'required|integer|min:1',
            'action' => 'required|string|in:recommend,apply',
            'method' => 'nullable|string|in:weight,price,quantity',
            'allocations' => 'nullable|array|min:1',
            'allocations.*.product_id' => 'required|integer|min:1',
            'allocations.*.freight_cost_per_unit' => 'required|numeric|min:0',
            'allocations.*.other_costs_per_unit' => 'required|numeric|min:0',
        ], [
            'stock_receipt_id.required' => "L'identifiant de la commande est requis (stock_receipt_id) — voir list-stock-receipts-tool.",
            'action.required' => "L'action est requise (action) : recommend ou apply.",
            'action.in' => "L'action doit être : recommend ou apply.",
            'method.in' => 'La méthode de répartition doit être : weight, price ou quantity.',
            'allocations.required' => "Les allocations par produit sont requises pour appliquer (obtenir les montants via action=recommend).",
        ]);

        $receipt = StockReceipt::find($input['stock_receipt_id']);
        if (! $receipt) {
            return Response::error("Aucune commande ne correspond à l'identifiant {$input['stock_receipt_id']}.");
        }

        try {
            $response = match ($input['action']) {
                'recommend' => $receiptController->getCostRecommendations(
                    $this->formRequest(GetCostRecommendationsRequest::class, [
                        'method' => $input['method'] ?? 'weight',
                    ]),
                    $receipt
                ),
                'apply' => $receiptController->applyCosts(
                    $this->formRequest(ApplyCostAllocationRequest::class, [
                        'allocations' => $input['allocations'] ?? [],
                    ]),
                    $receipt
                ),
                default => Response::error('Action inconnue.'),
            };
        } catch (ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            return Response::error('Échec de la répartition des coûts : '.$e->getMessage());
        }

        $data = $response->getData(true);

        if (($data['status'] ?? '') === 'error') {
            return Response::error(($data['message'] ?? 'Erreur de répartition').' : '.($data['error'] ?? ''));
        }

        return Response::json($data);
    }

    /**
     * Instancie et valide une FormRequest du contrôleur comme le ferait le routeur.
     */
    private function formRequest(string $class, array $params): object
    {
        $formRequest = $class::create('/', 'POST', $params);
        $formRequest->setContainer(app());
        $formRequest->validateResolved();

        return $formRequest;
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'stock_receipt_id' => $schema->integer()
                ->description('Identifiant de la commande (voir list-stock-receipts-tool).')
                ->required()
                ->min(1),
            'action' => $schema->string()
                ->description("'recommend' pour obtenir les montants suggérés par produit, 'apply' pour les appliquer (la commande passe alors en cost_allocated).")
                ->required()
                ->enum(['recommend', 'apply']),
            'method' => $schema->string()
                ->description("Méthode de répartition pour action=recommend : 'weight' (par défaut), 'price' ou 'quantity'.")
                ->enum(['weight', 'price', 'quantity']),
            'allocations' => $schema->array()
                ->description("Allocations par produit, requises pour action=apply (product_id + freight_cost_per_unit + other_costs_per_unit, montants obtenus via action=recommend).")
                ->min(1)
                ->items(
                    $schema->object([
                        'product_id' => $schema->integer()
                            ->description('Identifiant du produit.')
                            ->required()
                            ->min(1),
                        'freight_cost_per_unit' => $schema->number()
                            ->description('Coût de transport par unité en Ariary.')
                            ->required()
                            ->min(0),
                        'other_costs_per_unit' => $schema->number()
                            ->description('Autres coûts (douane, manutention...) par unité en Ariary.')
                            ->required()
                            ->min(0),
                    ])
                ),
        ];
    }
}
