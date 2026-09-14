<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\StockPaymentController;
use App\Http\Requests\PayCompleteStockRequest;
use App\Http\Requests\PayFreightRequest;
use App\Http\Requests\PaySupplierRequest;
use App\Models\AccountTransaction;
use App\Models\StockReceipt;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Paye une commande de réapprovisionnement depuis un compte de trésorerie : payee=supplier (montant = coût total de la commande, refusé si déjà payé), payee=freight (montant libre au transitaire), payee=complete (fournisseur + transitaire en une fois). Utiliser list-accounts-tool pour choisir le compte. Les paiements sont catégorisés automatiquement (Approvisionnement / Transport & Transit).')]
class PayStockReceiptTool extends Tool
{
    /**
     * Les méthodes publiques du contrôleur de paiements sont réutilisées
     * telles quelles (avec leurs FormRequests) : une seule source de
     * vérité pour la comptabilité des paiements d'achats.
     */
    public function handle(Request $request, StockPaymentController $paymentController): Response
    {
        $input = $request->validate([
            'stock_receipt_id' => 'required|integer|min:1',
            'payee' => 'required|string|in:supplier,freight,complete',
            'account_id' => 'required|integer|min:1|exists:accounts,id',
            'amount' => 'nullable|numeric|min:0.01',
            'freight_amount' => 'nullable|numeric|min:0',
            'supplier_amount' => 'nullable|numeric|min:0',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'stock_receipt_id.required' => "L'identifiant de la commande est requis (stock_receipt_id) — voir list-stock-receipts-tool.",
            'payee.required' => "Le bénéficiaire est requis (payee) : supplier, freight ou complete.",
            'payee.in' => "Le bénéficiaire doit être : supplier, freight ou complete.",
            'account_id.required' => 'Le compte à débiter est requis (account_id) — voir list-accounts-tool.',
        ]);

        $receipt = StockReceipt::with(['supplier:id,name', 'freightForwarder:id,name'])->find($input['stock_receipt_id']);
        if (! $receipt) {
            return Response::error("Aucune commande ne correspond à l'identifiant {$input['stock_receipt_id']}.");
        }

        // Montants déjà payés (transactions non annulées liées à la commande)
        $payments = AccountTransaction::where('stock_receipt_id', $receipt->id)
            ->whereDoesntHave('reversingTransaction')
            ->get(['supplier_id', 'freight_forwarder_id', 'amount']);
        $supplierPaid = (float) $payments->whereNotNull('supplier_id')->sum('amount');
        $freightPaid = (float) $payments->whereNotNull('freight_forwarder_id')->sum('amount');
        $summary = [
            'stock_receipt_id' => $receipt->id,
            'receipt_number' => $receipt->receipt_number,
            'supplier' => $receipt->supplier?->name,
            'freight_forwarder' => $receipt->freightForwarder?->name,
            'total_cost_ariary' => (float) $receipt->total_cost_ariary,
            'supplier_paid' => $supplierPaid,
            'supplier_remaining' => max(0, (float) $receipt->total_cost_ariary - $supplierPaid),
            'freight_paid' => $freightPaid,
        ];

        try {
            $response = match ($input['payee']) {
                'supplier' => $this->paySupplierWithGuard($paymentController, $input, $summary),
                'freight' => $paymentController->payFreight($this->formRequest(PayFreightRequest::class, $input)),
                'complete' => $paymentController->payComplete($this->formRequest(PayCompleteStockRequest::class, $input)),
                default => Response::error('Bénéficiaire inconnu.'),
            };
        } catch (ValidationException $e) {
            return Response::error('Paramètres invalides : '.implode(' ', $e->validator->errors()->all()));
        } catch (\Throwable $e) {
            return Response::error('Échec du paiement : '.$e->getMessage());
        }

        $data = $response->getData(true);

        if (($data['status'] ?? '') === 'error') {
            return Response::error(($data['message'] ?? 'Erreur de paiement').' : '.($data['error'] ?? ''));
        }

        return Response::json([
            'message' => $data['message'] ?? 'Paiement enregistré avec succès.',
            'transaction' => [
                'id' => data_get($data, 'data.id') ?? data_get($data, 'data.data.id'),
                'reference_number' => data_get($data, 'data.reference_number') ?? data_get($data, 'data.data.reference_number'),
                'amount' => (float) (data_get($data, 'data.amount') ?? data_get($data, 'data.data.amount') ?? 0),
            ],
            'payment_summary' => $summary,
        ]);
    }

    /**
     * Paiement fournisseur : montant unique (coût total de la commande) —
     * refusé si le fournisseur a déjà été intégralement payé.
     */
    private function paySupplierWithGuard(StockPaymentController $paymentController, array $input, array $summary): mixed
    {
        if ($summary['supplier_remaining'] <= 0 && $summary['total_cost_ariary'] > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Le fournisseur a déjà été payé pour cette commande ({$summary['supplier_paid']} Ar payés).",
            ]);
        }

        return $paymentController->paySupplier($this->formRequest(PaySupplierRequest::class, $input));
    }

    /**
     * Instancie et valide une FormRequest du contrôleur comme le ferait le routeur.
     */
    private function formRequest(string $class, array $params): object
    {
        $formRequest = $class::create('/', 'POST', array_filter($params, fn ($v) => $v !== null));
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
                ->description('Identifiant de la commande (stock_receipts.id, voir list-stock-receipts-tool).')
                ->required()
                ->min(1),
            'payee' => $schema->string()
                ->description("Bénéficiaire : 'supplier' (montant = coût total de la commande), 'freight' (montant libre, param amount) ou 'complete' (fournisseur + transport en une fois).")
                ->required()
                ->enum(['supplier', 'freight', 'complete']),
            'account_id' => $schema->integer()
                ->description('Identifiant du compte de trésorerie à débiter (voir list-accounts-tool).')
                ->required()
                ->min(1),
            'amount' => $schema->number()
                ->description("Montant à payer au transitaire (requis uniquement pour payee='freight')."),
            'freight_amount' => $schema->number()
                ->description("Montant du transport (requis uniquement pour payee='complete')."),
            'transaction_date' => $schema->string()
                ->description('Date du paiement (format Y-m-d, aujourd\'hui par défaut).'),
            'notes' => $schema->string()
                ->description('Notes sur le paiement.')
                ->max(1000),
        ];
    }
}
