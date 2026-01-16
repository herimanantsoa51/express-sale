<?php

namespace App\Http\Controllers;

use App\Models\StockReceipt;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\ExpenseCategory;
use App\Models\TransactionType;
use App\Http\Requests\PaySupplierRequest;
use App\Http\Requests\PayFreightRequest;
use App\Http\Requests\PayCompleteStockRequest;
use App\Http\Resources\StockPaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Controller pour la gestion des paiements de réapprovisionnement
 */
class StockPaymentController extends Controller
{
    /**
     * Enregistre un paiement au fournisseur pour une réception de stock
     * POST /api/stock-payments/supplier
     * 
     * Le montant est automatiquement le total_cost_ariary de la réception
     * Le numéro de référence est généré automatiquement (RE-YYYYMMDD-NNNNNN)
     * 
     * Body:
     * - stock_receipt_id: ID de la réception
     * - account_id: Compte à débiter
     * - transaction_date: Date de la transaction (optionnel)
     * - notes: Notes additionnelles (optionnel)
     */
    public function paySupplier(PaySupplierRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                // Récupérer la réception de stock avec le fournisseur
                $stockReceipt = StockReceipt::with('supplier')
                    ->findOrFail($request->stock_receipt_id);

                // Vérifier que la réception a un fournisseur
                if (!$stockReceipt->supplier_id) {
                    throw new \Exception('Cette réception n\'a pas de fournisseur associé');
                }

                // Le montant est le total_cost_ariary de la réception
                $amount = $stockReceipt->total_cost_ariary;

                if ($amount <= 0) {
                    throw new \Exception('Le coût total de la réception doit être supérieur à 0');
                }

                // Récupérer le compte
                $account = Account::findOrFail($request->account_id);

                // Vérifier que le compte est actif
                if (!$account->is_active) {
                    throw new \Exception('Le compte sélectionné n\'est pas actif');
                }

                // Vérifier le solde disponible
                if ($account->current_balance < $amount) {
                    throw new \Exception(sprintf(
                        'Solde insuffisant (disponible: %s Ar, requis: %s Ar)',
                        number_format($account->current_balance, 2, ',', ' '),
                        number_format($amount, 2, ',', ' ')
                    ));
                }

                // Récupérer la catégorie de dépense "Approvisionnement"
                $expenseCategory = ExpenseCategory::where('name', 'Approvisionnement')
                    ->where('is_active', true)
                    ->first();

                if (!$expenseCategory) {
                    throw new \Exception('Catégorie de dépense "Approvisionnement" non trouvée ou inactive');
                }

                // Récupérer le type de transaction EXPENSE
                $expenseType = TransactionType::where('code', 'EXPENSE')->first();
                if (!$expenseType) {
                    throw new \Exception('Type de transaction EXPENSE non configuré');
                }

                // Calculer les soldes
                $balanceBefore = $account->current_balance;
                $balanceAfter = $balanceBefore - $amount;

                // Créer la description
                $description = sprintf(
                    'Paiement fournisseur %s - Réception #%s',
                    $stockReceipt->supplier->name,
                    $stockReceipt->receipt_number
                );

                // Créer la transaction (le numéro de référence sera généré automatiquement)
                $transaction = AccountTransaction::create([
                    'account_id' => $account->id,
                    'transaction_type_id' => $expenseType->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'transaction_date' => $request->transaction_date ?? now(),
                    'supplier_id' => $stockReceipt->supplier_id,
                    'stock_receipt_id' => $stockReceipt->id,
                    'expense_category_id' => $expenseCategory->id,
                    'recipient_name' => $stockReceipt->supplier->name,
                    'description' => $description,
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ]);

                // Mettre à jour le solde du compte
                $account->current_balance = $balanceAfter;
                $account->save();

                // Recharger avec les relations
                $transaction->load([
                    'account.accountType',
                    'transactionType',
                    'supplier',
                    'stockReceipt',
                    'expenseCategory',
                    'creator'
                ]);

                Log::info('Paiement fournisseur enregistré', [
                    'transaction_id' => $transaction->id,
                    'reference_number' => $transaction->reference_number,
                    'stock_receipt_id' => $stockReceipt->id,
                    'supplier_id' => $stockReceipt->supplier_id,
                    'amount' => $amount,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Paiement fournisseur enregistré avec succès',
                    'data' => new StockPaymentResource($transaction)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Erreur lors du paiement fournisseur', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistre un paiement au transitaire pour une réception de stock
     * POST /api/stock-payments/freight
     * 
     * Le numéro de référence est généré automatiquement (RE-YYYYMMDD-NNNNNN)
     * 
     * Body:
     * - stock_receipt_id: ID de la réception
     * - account_id: Compte à débiter
     * - amount: Montant à payer
     * - transaction_date: Date de la transaction (optionnel)
     * - notes: Notes additionnelles (optionnel)
     */
    public function payFreight(PayFreightRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                // Récupérer la réception de stock avec le transitaire
                $stockReceipt = StockReceipt::with('freightForwarder')
                    ->findOrFail($request->stock_receipt_id);

                // Vérifier que la réception a un transitaire
                if (!$stockReceipt->freight_forwarder_id) {
                    throw new \Exception('Cette réception n\'a pas de transitaire associé');
                }

                // Récupérer le compte
                $account = Account::findOrFail($request->account_id);

                // Vérifier que le compte est actif
                if (!$account->is_active) {
                    throw new \Exception('Le compte sélectionné n\'est pas actif');
                }

                // Vérifier le solde disponible
                if ($account->current_balance < $request->amount) {
                    throw new \Exception(sprintf(
                        'Solde insuffisant (disponible: %s Ar, requis: %s Ar)',
                        number_format($account->current_balance, 2, ',', ' '),
                        number_format($request->amount, 2, ',', ' ')
                    ));
                }

                // Récupérer la catégorie de dépense "Transport & Transit"
                $expenseCategory = ExpenseCategory::where('name', 'Transport & Transit')
                    ->where('is_active', true)
                    ->first();

                if (!$expenseCategory) {
                    throw new \Exception('Catégorie de dépense "Transport & Transit" non trouvée ou inactive');
                }

                // Récupérer le type de transaction EXPENSE
                $expenseType = TransactionType::where('code', 'EXPENSE')->first();
                if (!$expenseType) {
                    throw new \Exception('Type de transaction EXPENSE non configuré');
                }

                // Calculer les soldes
                $balanceBefore = $account->current_balance;
                $balanceAfter = $balanceBefore - $request->amount;

                // Créer la description
                $description = sprintf(
                    'Paiement transitaire %s - Réception #%s',
                    $stockReceipt->freightForwarder->name,
                    $stockReceipt->receipt_number
                );

                // Créer la transaction (le numéro de référence sera généré automatiquement)
                $transaction = AccountTransaction::create([
                    'account_id' => $account->id,
                    'transaction_type_id' => $expenseType->id,
                    'amount' => $request->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'transaction_date' => $request->transaction_date ?? now(),
                    'freight_forwarder_id' => $stockReceipt->freight_forwarder_id,
                    'stock_receipt_id' => $stockReceipt->id,
                    'expense_category_id' => $expenseCategory->id,
                    'recipient_name' => $stockReceipt->freightForwarder->name,
                    'description' => $description,
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ]);

                // Mettre à jour le solde du compte
                $account->current_balance = $balanceAfter;
                $account->save();

                // Recharger avec les relations
                $transaction->load([
                    'account.accountType',
                    'transactionType',
                    'freightForwarder',     
                    'stockReceipt',
                    'expenseCategory',
                    'creator'
                ]);

                Log::info('Paiement transitaire enregistré', [
                    'transaction_id' => $transaction->id,
                    'reference_number' => $transaction->reference_number,
                    'stock_receipt_id' => $stockReceipt->id,
                    'freight_forwarder_id' => $stockReceipt->freight_forwarder_id,
                    'amount' => $request->amount,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Paiement transitaire enregistré avec succès',
                    'data' => new StockPaymentResource($transaction)
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Erreur lors du paiement transitaire', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistre un paiement complet (fournisseur + transitaire) pour une réception de stock
     * POST /api/stock-payments/complete
     * 
     * Le montant fournisseur est automatiquement le total_cost_ariary
     * Les numéros de référence sont générés automatiquement (RE-YYYYMMDD-NNNNNN)
     * 
     * Body:
     * - stock_receipt_id: ID de la réception
     * - account_id: Compte à débiter
     * - freight_amount: Montant à payer au transitaire
     * - transaction_date: Date de la transaction (optionnel)
     * - notes: Notes additionnelles (optionnel)
     */
    public function payComplete(PayCompleteStockRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                $stockReceipt = StockReceipt::with(['supplier', 'freightForwarder'])
                    ->findOrFail($request->stock_receipt_id);

                // Le montant fournisseur est automatiquement le total_cost_ariary
                $supplierAmount = $stockReceipt->total_cost_ariary;

                $account = Account::lockForUpdate()->findOrFail($request->account_id);

                // Vérifier que le compte est actif
                if (!$account->is_active) {
                    throw new \Exception('Le compte sélectionné n\'est pas actif');
                }

                $totalAmount = $supplierAmount + $request->freight_amount;

                // Vérifier le solde disponible
                if ($account->current_balance < $totalAmount) {
                    throw new \Exception(sprintf(
                        'Solde insuffisant (disponible: %s Ar, requis: %s Ar)',
                        number_format($account->current_balance, 2, ',', ' '),
                        number_format($totalAmount, 2, ',', ' ')
                    ));
                }

                $transactions = [];

                // Paiement fournisseur (toujours effectué si supplier_id existe)
                if ($stockReceipt->supplier_id && $supplierAmount > 0) {
                    $supplierCategory = ExpenseCategory::where('name', 'Approvisionnement')
                        ->where('is_active', true)
                        ->firstOrFail();

                    $expenseType = TransactionType::where('code', 'EXPENSE')->firstOrFail();

                    $balanceBefore = $account->current_balance;
                    $balanceAfter = $balanceBefore - $supplierAmount;

                    // Le numéro de référence sera généré automatiquement
                    $supplierTransaction = AccountTransaction::create([
                        'account_id' => $account->id,
                        'transaction_type_id' => $expenseType->id,
                        'amount' => $supplierAmount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'transaction_date' => $request->transaction_date ?? now(),
                        'supplier_id' => $stockReceipt->supplier_id,
                        'stock_receipt_id' => $stockReceipt->id,
                        'expense_category_id' => $supplierCategory->id,
                        'recipient_name' => $stockReceipt->supplier->name,
                        'description' => sprintf(
                            'Paiement fournisseur %s - Réception #%s',
                            $stockReceipt->supplier->name,
                            $stockReceipt->receipt_number
                        ),
                        'notes' => $request->notes,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    $account->current_balance = $balanceAfter;
                    $account->save();

                    $transactions['supplier'] = $supplierTransaction;
                }

                // Paiement transitaire
                if ($request->freight_amount > 0) {
                    if (!$stockReceipt->freight_forwarder_id) {
                        throw new \Exception('Cette réception n\'a pas de transitaire associé');
                    }

                    $freightCategory = ExpenseCategory::where('name', 'Transport & Transit')
                        ->where('is_active', true)
                        ->firstOrFail();

                    $expenseType = TransactionType::where('code', 'EXPENSE')->firstOrFail();

                    $account->refresh(); // Recharger le solde mis à jour
                    $balanceBefore = $account->current_balance;
                    $balanceAfter = $balanceBefore - $request->freight_amount;

                    // Le numéro de référence sera généré automatiquement
                    $freightTransaction = AccountTransaction::create([
                        'account_id' => $account->id,
                        'transaction_type_id' => $expenseType->id,
                        'amount' => $request->freight_amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'transaction_date' => $request->transaction_date ?? now(),
                        'freight_forwarder_id' => $stockReceipt->freight_forwarder_id,
                        'stock_receipt_id' => $stockReceipt->id,
                        'expense_category_id' => $freightCategory->id,
                        'recipient_name' => $stockReceipt->freightForwarder->name,
                        'description' => sprintf(
                            'Paiement transitaire %s - Réception #%s',
                            $stockReceipt->freightForwarder->name,
                            $stockReceipt->receipt_number
                        ),
                        'notes' => $request->notes,
                        'created_by' => Auth::id(),
                        'created_at' => now(),
                    ]);

                    $account->current_balance = $balanceAfter;
                    $account->save();

                    $transactions['freight'] = $freightTransaction;
                }

                // Recharger les transactions avec leurs relations
                foreach ($transactions as $key => $transaction) {
                    $transaction->load([
                        'account.accountType',
                        'transactionType',
                        'supplier',
                        'freightForwarder',
                        'stockReceipt',
                        'expenseCategory',
                        'creator'
                    ]);
                }

                Log::info('Paiement complet enregistré', [
                    'stock_receipt_id' => $stockReceipt->id,
                    'supplier_amount' => $supplierAmount,
                    'supplier_reference' => $transactions['supplier']->reference_number ?? null,
                    'freight_amount' => $request->freight_amount,
                    'freight_reference' => $transactions['freight']->reference_number ?? null,
                    'total_amount' => $totalAmount,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Paiements enregistrés avec succès',
                    'data' => [
                        'supplier_payment' => isset($transactions['supplier']) 
                            ? new StockPaymentResource($transactions['supplier']) 
                            : null,
                        'freight_payment' => isset($transactions['freight']) 
                            ? new StockPaymentResource($transactions['freight']) 
                            : null,
                        'total_amount' => $totalAmount,
                        'supplier_amount' => $supplierAmount,
                        'freight_amount' => $request->freight_amount
                    ]
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Erreur lors du paiement complet', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste toutes les transactions liées à une réception de stock
     * GET /api/stock-payments/receipt/{stock_receipt_id}
     */
    public function getReceiptTransactions(int $stockReceiptId): JsonResponse
    {
        try {
            $stockReceipt = StockReceipt::with(['supplier', 'freightForwarder'])
                ->findOrFail($stockReceiptId);

            $transactions = AccountTransaction::where('stock_receipt_id', $stockReceiptId)
                ->with([
                    'account.accountType',
                    'transactionType',
                    'supplier',
                    'freightForwarder',
                    'expenseCategory',
                    'creator'
                ])
                ->orderBy('transaction_date', 'desc')
                ->get();

            // Calculer les totaux
            $supplierTotal = $transactions
                ->where('supplier_id', '!=', null)
                ->sum('amount');

            $freightTotal = $transactions
                ->where('freight_forwarder_id', '!=', null)
                ->sum('amount');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stock_receipt' => [
                        'id' => $stockReceipt->id,
                        'receipt_number' => $stockReceipt->receipt_number,
                        'total_cost' => $stockReceipt->total_cost_ariary,
                        'supplier' => $stockReceipt->supplier,
                        'freight_forwarder' => $stockReceipt->freightForwarder,
                    ],
                    'transactions' => StockPaymentResource::collection($transactions),
                    'summary' => [
                        'total_paid' => $supplierTotal + $freightTotal,
                        'supplier_paid' => $supplierTotal,
                        'freight_paid' => $freightTotal,
                        'transaction_count' => $transactions->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des transactions',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}