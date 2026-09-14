<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditItem;
use App\Models\InstallmentTransaction;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditSaleService
{
    public function __construct(
        private SaleService $saleService,
        private StockService $stockService,
    ) {}

    /**
     * Crée une vente à crédit (indépendante de Sale)
     */
    public function createCreditSale(array $data): Credit
    {
        return DB::transaction(function () use ($data) {
            // Contrôles client : existence, statut actif, retard de paiement, plafond de crédit
            $customer = \App\Models\Customer::lockForUpdate()->find($data['customer_id']);
            if (! $customer) {
                throw new \Exception('Client introuvable');
            }
            if (! $customer->is_active) {
                throw new \Exception("Le client « {$customer->name} » est inactif : vente à crédit impossible");
            }
            if ($customer->hasOverdueCredits()) {
                throw new \Exception("Le client « {$customer->name} » a des crédits en retard : vente à crédit impossible");
            }
            if ($customer->credit_limit !== null) {
                $data['_customer_open_due'] = (float) Credit::where('customer_id', $customer->id)
                    ->whereIn('status', ['active', 'partial_paid', 'overdue'])
                    ->sum('amount_due');
            }

            $data['items'] = $this->saleService->enrichItemsWithPrices($data['items']);

            $stockValidation = $this->saleService->validateStockAvailability($data['items']);
            if (! $stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            $totals = $this->saleService->calculateTotals($data['items'], $data['discount_amount'] ?? 0);

            if ($customer->credit_limit !== null) {
                $openDue = (float) $data['_customer_open_due'];
                if ($openDue + $totals['total'] > (float) $customer->credit_limit) {
                    throw new \Exception(sprintf(
                        'Limite de crédit dépassée : plafond %s Ar, déjà dû %s Ar, demande %s Ar',
                        number_format((float) $customer->credit_limit, 2, ',', ' '),
                        number_format($openDue, 2, ',', ' '),
                        number_format($totals['total'], 2, ',', ' ')
                    ));
                }
            }

            // Distribuer la remise si applicable
            if (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
                $data['items'] = $this->saleService->distributeDiscount($data['items'], $data['discount_amount']);
            }

            // Créer le crédit directement (SANS Sale)
            // Note : installment_count/installment_frequency ne sont pas des colonnes
            // de `credits` — ils servent uniquement à générer les échéances ci-dessous.
            $credit = Credit::create([
                'credit_number' => Credit::generateNumber(),
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'credit_date' => now(),
                'due_date' => Carbon::parse($data['due_date']),
                'total_amount' => $totals['total'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'amount_paid' => 0,
                'amount_due' => $totals['total'],
                'status' => 'active',
            ]);

            // Créer les items propres au crédit
            foreach ($data['items'] as $item) {
                $creditItem = CreditItem::create([
                    'credit_id' => $credit->id,
                    'variant_id' => $item['variant_id'],
                    'location_id' => $item['location_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // FIFO avec la nouvelle méthode dédiée
                $this->stockService->consumeFifoForCredit(
                    creditItemId: $creditItem->id,
                    variantId: $item['variant_id'],
                    locationId: $item['location_id'],
                    quantityToSell: $item['quantity'],
                    unitPriceAtSale: $item['unit_price'],
                    discount: $item['discount_amount'] ?? 0,
                );
            }

            // Créer les échéances
            $this->createInstallments($credit, $data);

            // Mettre à jour les points fidélité du client
            $credit->customer->recordPurchase($credit->total_amount);

            Log::info('Crédit créé (indépendant)', [
                'credit_id' => $credit->id,
                'credit_number' => $credit->credit_number,
            ]);

            return $credit->load(['items.variant.product', 'customer', 'user', 'installments']);
        });
    }

    /**
     * Payer une échéance de crédit
     */
    public function payCreditInstallment(int $installmentId, array $data): CreditInstallment
    {
        return DB::transaction(function () use ($installmentId, $data) {
            $installment = CreditInstallment::with(['credit.customer'])->findOrFail($installmentId);
            $credit = $installment->credit;
            $amount = min($data['amount'], $installment->amount_due - $installment->amount_paid);

            if ($amount <= 0) {
                throw new \Exception('Cette échéance est déjà payée');
            }

            $transaction = $this->saleService->createCreditTransaction(
                $credit,
                $data['account_id'],
                $amount,
                "Paiement crédit #{$credit->credit_number} - Échéance #{$installment->installment_number}",
                $data['notes'] ?? '',
            );

            InstallmentTransaction::create([
                'installment_id' => $installment->id,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'payment_date' => now(),
            ]);

            $installment->amount_paid += $amount;
            if ($installment->amount_paid >= $installment->amount_due) {
                $installment->status = 'paid';
            } else {
                $installment->status = 'partial';
            }
            $installment->save();

            // Mettre à jour le crédit
            $credit->amount_paid += $amount;
            $credit->amount_due -= $amount;
            if ($credit->amount_due <= 0) {
                $credit->status = 'paid';
            } else {
                $credit->status = 'partial_paid';
            }
            $credit->save();

            return $installment->load(['installmentTransactions.transaction']);
        });
    }

    /**
     * Annuler un crédit : marque uniquement le crédit comme annulé.
     *
     * Politique métier : AUCUN automatisme au-delà du statut — ni remboursement
     * des échéances déjà payées, ni libération du stock. Ces opérations sont
     * faites manuellement par l'opérateur (transaction inverse, ajustement de stock).
     */
    public function cancelCredit(Credit $credit): Credit
    {
        return DB::transaction(function () use ($credit) {
            $credit->status = 'cancelled';
            $credit->save();

            Log::info('Crédit annulé', [
                'credit_id' => $credit->id,
                'amount_paid' => $credit->amount_paid,
            ]);

            return $credit;
        });
    }

    /**
     * Créer les échéances de paiement
     */
    private function createInstallments(Credit $credit, array $data): void
    {
        $count = $data['installment_count'] ?? 1;
        $frequency = $data['installment_frequency'] ?? 'monthly';
        $amountPerInstallment = round($credit->total_amount / $count, 2);
        $remainder = round($credit->total_amount - ($amountPerInstallment * $count), 2);

        for ($i = 1; $i <= $count; $i++) {
            $dueDate = match ($frequency) {
                'weekly' => Carbon::parse($credit->credit_date)->addWeeks($i),
                'biweekly' => Carbon::parse($credit->credit_date)->addWeeks($i * 2),
                'monthly' => Carbon::parse($credit->credit_date)->addMonths($i),
                default => Carbon::parse($credit->credit_date)->addMonths($i),
            };

            $amount = $amountPerInstallment;
            if ($i === $count) {
                $amount += $remainder;
            }

            CreditInstallment::create([
                'credit_id' => $credit->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'amount_due' => $amount,
                'amount_paid' => 0,
                'status' => 'pending',
            ]);
        }
    }
}
