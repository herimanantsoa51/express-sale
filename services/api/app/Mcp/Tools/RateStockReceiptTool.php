<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\StockReceiptController;
use App\Http\Requests\RateStockReceiptRequest;
use App\Models\StockReceipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Évalue la qualité des articles reçus d\'une commande (note 0-10 par article, notes de conformité) : met à jour le score de fiabilité du fournisseur, marque la commande comme rated et crée les batches de stock. Étape obligatoire entre arrived et la répartition des coûts.')]
class RateStockReceiptTool extends Tool
{
    /**
     * La méthode du contrôleur est réutilisée telle quelle (avec sa
     * FormRequest) : une seule source de vérité pour l'évaluation qualité.
     */
    public function handle(Request $request, StockReceiptController $controller): Response
    {
        $input = $request->validate([
            'stock_receipt_id' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|min:1',
            'items.*.quality_rating' => 'required|numeric|min:0|max:10',
            'items.*.quality_notes' => 'nullable|string|max:1000',
        ], [
            'stock_receipt_id.required' => "L'identifiant de la commande est requis (stock_receipt_id) — voir list-stock-receipts-tool.",
            'items.required' => "Les évaluations sont requises (items : item_id + quality_rating 0-10).",
        ]);

        $receipt = StockReceipt::find($input['stock_receipt_id']);
        if (! $receipt) {
            return Response::error("Aucune commande ne correspond à l'identifiant {$input['stock_receipt_id']}.");
        }
        if (! in_array($receipt->status, ['arrived', 'rated'])) {
            return Response::error("Seules les commandes arrivées peuvent être évaluées — statut actuel : {$receipt->status}.");
        }
        if ($receipt->hasBatches()) {
            return Response::error('Les batches de cette commande existent déjà (évaluation déjà faite).');
        }

        try {
            $response = $controller->rateReceipt(
                $this->formRequest(RateStockReceiptRequest::class, [
                    'items' => array_map(fn (array $item) => array_filter([
                        'item_id' => (int) $item['item_id'],
                        'quality_rating' => (float) $item['quality_rating'],
                        'quality_notes' => $item['quality_notes'] ?? null,
                    ], fn ($v) => $v !== null), $input['items']),
                ]),
                $receipt
            );
        } catch (ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            return Response::error('Échec de l\'évaluation : '.$e->getMessage());
        }

        $data = $response->getData(true);

        if (($data['status'] ?? '') === 'error') {
            return Response::error(($data['message'] ?? 'Erreur d\'évaluation').' : '.($data['error'] ?? ''));
        }

        $receipt->refresh();

        return Response::json([
            'message' => 'Commande évaluée avec succès. Batches de stock créés — passer à la répartition des coûts (allocate-stock-receipt-costs-tool).',
            'receipt' => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'status' => $receipt->status,
            ],
        ]);
    }

    private function formRequest(string $class, array $params): object
    {
        $formRequest = $class::create('/', 'POST', $params);
        $formRequest->setContainer(app());
        $formRequest->validateResolved();

        return $formRequest;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'stock_receipt_id' => $schema->integer()
                ->description('Identifiant de la commande (voir list-stock-receipts-tool).')
                ->required()
                ->min(1),
            'items' => $schema->array()
                ->description('Évaluations par article (item_id + quality_rating).')
                ->required()
                ->min(1)
                ->items(
                    $schema->object([
                        'item_id' => $schema->integer()
                            ->description("Identifiant de l'article de réception.")
                            ->required()
                            ->min(1),
                        'quality_rating' => $schema->number()
                            ->description('Note qualité de 0 à 10.')
                            ->required()
                            ->min(0),
                        'quality_notes' => $schema->string()
                            ->description('Notes qualité (défauts, conformité...).')
                            ->max(1000),
                    ])
                ),
        ];
    }
}
