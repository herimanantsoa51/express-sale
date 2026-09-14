<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\InstallmentTransaction;
use App\Services\CreditSaleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Enregistre un paiement sur une échéance d\'une vente à crédit : encaisse le montant sur un compte de trésorerie et met à jour l\'échéance et le crédit. Si installment_id est omis, la première échéance non soldée du crédit est payée automatiquement.')]
class PayCreditInstallmentTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, CreditSaleService $creditSaleService): Response
    {
        $input = $request->validate([
            'credit_id' => 'required|integer|min:1',
            'installment_id' => 'nullable|integer|min:1',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|integer|min:1|exists:accounts,id',
            'notes' => 'nullable|string|max:500',
        ], [
            'credit_id.required' => "L'identifiant du crédit est requis (credit_id).",
            'amount.required' => 'Le montant payé est requis (amount).',
            'account_id.required' => "Le compte de trésorerie est requis (account_id) pour encaisser le paiement.",
            'account_id.exists' => "Le compte sélectionné n'existe pas.",
        ]);

        $credit = Credit::find($input['credit_id']);
        if (! $credit) {
            return Response::error("Aucun crédit ne correspond à l'identifiant {$input['credit_id']}.");
        }
        if (in_array($credit->status, ['cancelled', 'paid'])) {
            return Response::error("Ce crédit est {$credit->status}, aucun paiement n'est possible.");
        }

        // Échéance explicite, sinon la première échéance non soldée
        if (! empty($input['installment_id'])) {
            $installment = CreditInstallment::where('credit_id', $credit->id)
                ->where('id', $input['installment_id'])
                ->first();
            if (! $installment) {
                return Response::error("L'échéance {$input['installment_id']} n'appartient pas au crédit {$credit->credit_number}.");
            }
        } else {
            $installment = CreditInstallment::where('credit_id', $credit->id)
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('installment_number')
                ->first();
            if (! $installment) {
                return Response::error("Toutes les échéances du crédit {$credit->credit_number} sont déjà soldées.");
            }
        }

        try {
            $installment = $creditSaleService->payCreditInstallment($installment->id, [
                'amount' => (float) $input['amount'],
                'account_id' => (int) $input['account_id'],
                'notes' => $input['notes'] ?? '',
            ]);
        } catch (\Throwable $e) {
            ActivityLogger::failed(
                ActivityAction::INSTALLMENT_PAID,
                ' le paiement d\'une échéance via MCP a échoué',
                ['error' => $e->getMessage(), 'credit_id' => $input['credit_id'], 'installment_id' => $installment->id]
            );

            return Response::error('Échec du paiement de l\'échéance : '.$e->getMessage());
        }

        $credit->refresh();
        $lastTransaction = $installment->installmentTransactions->sortByDesc('id')->first();

        ActivityLogger::success(
            ActivityAction::INSTALLMENT_PAID,
            " a encaissé un paiement d'échéance via MCP sur le crédit {$credit->credit_number}",
            [
                'model_type' => InstallmentTransaction::class,
                'model_id' => $lastTransaction?->id,
                'metadata' => $lastTransaction?->toArray(),
            ],
            "/transactions/{$lastTransaction?->transaction_id}"
        );

        return Response::json([
            'message' => 'Paiement enregistré avec succès.',
            'installment' => [
                'id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'amount_due' => (float) $installment->amount_due,
                'amount_paid' => (float) $installment->amount_paid,
                'status' => $installment->status,
                'due_date' => $installment->due_date?->toDateString(),
            ],
            'credit' => [
                'id' => $credit->id,
                'credit_number' => $credit->credit_number,
                'amount_paid' => (float) $credit->amount_paid,
                'amount_due' => (float) $credit->amount_due,
                'status' => $credit->status,
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'credit_id' => $schema->integer()
                ->description('Identifiant du crédit concerné.')
                ->required()
                ->min(1),
            'installment_id' => $schema->integer()
                ->description("Identifiant de l'échéance à payer. Si omis, la première échéance non soldée est payée automatiquement.")
                ->min(1),
            'amount' => $schema->number()
                ->description('Montant encaissé en Ariary (plafonné automatiquement au restant dû de l\'échéance).')
                ->required(),
            'account_id' => $schema->integer()
                ->description('Identifiant du compte de trésorerie où encaisser le paiement.')
                ->required()
                ->min(1),
            'notes' => $schema->string()
                ->description('Notes sur le paiement.')
                ->max(500),
        ];
    }
}
