<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\StoreOperationalExpenseRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Account;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\AccountTransactionListResource;
use App\Http\Resources\ExpenseTransactionListResource;
use App\Models\ExpenseCategory;
use App\Helpers\ActivityLogger;
use App\Enums\ActivityAction;

/**
 * Controller pour la gestion des transactions
 */
class AccountTransactionController extends Controller
{
    /**
     * Liste toutes les transactions avec filtres
     * GET /api/transactions
     * 
     * Query params:
     * - account_id: Filtrer par compte
     * - type_id: Filtrer par type de transaction
     * - category: Filtrer par catégorie (income/expense/transfer)
     * - start_date: Date de début
     * - end_date: Date de fin
     * - supplier_id: Filtrer par fournisseur
     * - freight_forwarder_id: Filtrer par transitaire
     * - stock_receipt_id: Filtrer par réception de stock
     * - expense_category_id: Filtrer par catégorie de dépense
     * - search: Recherche dans description/notes
     * - sort_by: Champ de tri (default: transaction_date)
     * - sort_order: Ordre de tri (asc/desc, default: desc)
     * - per_page: Nombre d'éléments par page (default: 20)
     */
    // public function index(Request $request): TransactionCollection
    // {
    //     $query = AccountTransaction::with([
    //         'account.accountType',
    //         'transactionType',
    //         'relatedAccount',
    //         'supplier',
    //         'freightForwarder',
    //         'stockReceipt',
    //         'expenseCategory',
    //         'sale',
    //         'creator'
    //     ]);

    //     // Filtre par compte
    //     if ($request->has('account_id')) {
    //         $query->where('account_id', $request->account_id);
    //     }

    //     // Filtre par type de transaction
    //     if ($request->has('type_id')) {
    //         $query->where('transaction_type_id', $request->type_id);
    //     }

    //     // Filtre par catégorie
    //     if ($request->has('category')) {
    //         $query->byCategory($request->category);
    //     }

    //     // Filtre par période
    //     if ($request->has('start_date') && $request->has('end_date')) {
    //         $query->betweenDates($request->start_date, $request->end_date);
    //     }

    //     // Filtre par fournisseur
    //     if ($request->has('supplier_id')) {
    //         $query->where('supplier_id', $request->supplier_id);
    //     }

    //     // Filtre par transitaire
    //     if ($request->has('freight_forwarder_id')) {
    //         $query->where('freight_forwarder_id', $request->freight_forwarder_id);
    //     }

    //     // Filtre par réception de stock
    //     if ($request->has('stock_receipt_id')) {
    //         $query->where('stock_receipt_id', $request->stock_receipt_id);
    //     }

    //     // Filtre par catégorie de dépense
    //     if ($request->has('expense_category_id')) {
    //         $query->where('expense_category_id', $request->expense_category_id);
    //     }

    //     // Recherche
    //     if ($request->has('search')) {
    //         $search = $request->search;
    //         $query->where(function($q) use ($search) {
    //             $q->where('description', 'like', "%$search%")
    //               ->orWhere('notes', 'like', "%$search%")
    //               ->orWhere('reference_number', 'like', "%$search%")
    //               ->orWhere('recipient_name', 'like', "%$search%");
    //         });
    //     }

    //     // Tri
    //     $sortBy = $request->get('sort_by', 'transaction_date');
    //     $sortOrder = $request->get('sort_order', 'desc');
        
    //     if ($sortBy === 'transaction_date') {
    //         $query->orderBy('transaction_date', $sortOrder)
    //               ->orderBy('created_at', $sortOrder);
    //     } else {
    //         $query->orderBy($sortBy, $sortOrder);
    //     }

    //     $perPage = $request->get('per_page', 20);
    //     $transactions = $query->paginate($perPage);

    //     return new TransactionCollection($transactions);
    // }
    /**
     * Liste des opérations de dépenses avec statistiques par catégorie
     * GET /api/transactions/operational-expenses
     * 
     * Query params:
     * - account_id: Filtrer par compte
     * - expense_category_id: Filtrer par catégorie de dépense
     * - start_date: Date de début
     * - end_date: Date de fin
     * - per_page: Nombre d'éléments par page (default: 15)
     */
    public function indexExpenseOperation(Request $request): JsonResponse
    {
        $perPage   = (int) $request->get('per_page', 15);
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $accountId = $request->get('account_id');
        $categoryId = $request->get('expense_category_id');

        /* ===================== QUERY BASE ===================== */
        $baseQuery = AccountTransaction::query()
            ->with([
                'account:id,name,account_number',
                'plannedExpense:id,name'
            ])
            ->whereHas('transactionType', fn ($q) =>
                $q->where('category', 'expense')
                ->where('code', '!=', 'REVERSAL') // ✅ Exclure les annulations
            )
            ->whereNotIn('id', function ($query) {
                // ✅ Exclure les transactions qui ont été annulées
                $query->select('reversed_transaction_id')
                    ->from('account_transactions')
                    ->whereNotNull('reversed_transaction_id');
            });

        if ($accountId) {
            $baseQuery->where('account_id', $accountId);
        }
        if ($categoryId) {
            $baseQuery->where('expense_category_id', $categoryId);
        }

        if ($startDate && $endDate) {
            $baseQuery->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        /* ===================== PAGINATION ===================== */
        $transactions = (clone $baseQuery)
            ->latest('transaction_date')
            ->paginate($perPage);

        /* ===================== STATS PAR CATÉGORIES ===================== */
        $rawStats = (clone $baseQuery)
            ->selectRaw('
                COALESCE(expense_category_id, 0) as category_id,
                SUM(ABS(amount)) as total_amount,
                COUNT(*) as transactions_count
            ')
            ->groupBy('category_id')
            ->get();

        $categories = ExpenseCategory::whereIn(
            'id',
            $rawStats->pluck('category_id')->filter(fn ($id) => $id > 0)
        )->get()->keyBy('id');

        $categoryStats = $rawStats->map(function ($row) use ($categories) {
            $category = $categories->get($row->category_id);

            return [
                'category' => [
                    'id'   => $category?->id ?? 0,
                    'name' => $category?->name ?? 'Non catégorisé',
                ],
                'total_amount'       => (float) $row->total_amount,
                'transactions_count' => (int) $row->transactions_count,
            ];
        });

        /* ===================== TOTAL GLOBAL ===================== */
        $totalExpense = (clone $baseQuery)
            ->sum(DB::raw('ABS(amount)'));

        return response()->json([
            'filters' => [
                'account_id' => $accountId,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'expense_category_id' => $categoryId,
            ],

            'summary' => [
                'total_expense'    => (float) $totalExpense,
                'categories_count'=> $categoryStats->count(),
            ],

            'stats_by_category' => $categoryStats,

            'data' => ExpenseTransactionListResource::collection($transactions),

            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }
        public function index(Request $request, int $accountId): JsonResponse
        {
            $account = Account::findOrFail($accountId);

            $perPage   = (int) $request->get('per_page', 15);
            $startDate = $request->get('start_date');
            $endDate   = $request->get('end_date');

            $query = AccountTransaction::query()
            ->forAccount($account->id)
            ->with([
                'transactionType:id,name,category',
                'relatedAccount:id,name,account_number',
                'expenseCategory:id,name', // ✅ AJOUT
            ])
            ->latest();


            if ($startDate && $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            }

            $paginator = $query->paginate($perPage);

            /* ===================== TOTAUX ===================== */
            $totalsQuery = clone $query;

            $totals = [
                'income' => (float) $totalsQuery
                    ->clone()
                    ->income()
                    ->sum('amount'),

                'expense' => (float) abs(
                    $totalsQuery->clone()->expense()->sum('amount')
                ),

                'transfer' => (float) abs(
                    $totalsQuery->clone()->transfer()->sum('amount')
                ),
            ];

            return response()->json([
                'account' => [
                    'id'   => $account->id,
                    'name' => $account->name,
                ],
                'filters' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
                'totals' => $totals,
                'data' => AccountTransactionListResource::collection($paginator),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        }

    /**
     * Créer une nouvelle transaction
     * POST /api/transactions
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = AccountTransaction::createTransaction($request->validated());
            $transaction->load([
                'account.accountType',
                'transactionType',
                'relatedAccount',
                'supplier',
                'freightForwarder',
                'stockReceipt',
                'expenseCategory',
                'sale',
                'creator'
            ]);
                ActivityLogger::success(
                    ActivityAction::ACCOUNT_TRANSACTION_CREATED,
                    "Transaction créée : ID {$transaction->id}, Montant {$transaction->amount}",
                    [
                        'model_type' => AccountTransaction::class,
                        'model_id' => $transaction->id,
                        'metadata' => $request->validated(),
                    ],
                    "transactions/$transaction->id"
                );
            return response()->json([
                'status' => 'success',
                'message' => 'Transaction créée avec succès',
                'data' => new TransactionResource($transaction)
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::ACCOUNT_TRANSACTION_CREATED,
                "erreur de transaction",
                $e,
                [
                    'model_type' => AccountTransaction::class,
                    'metadata' => $request->validated(),
                ]

            );
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un transfert entre deux comptes
     * POST /api/transactions/transfer
     */
    public function transfer(StoreTransferRequest $request): JsonResponse
    {
        try {
            $result = AccountTransaction::createTransfer(
                $request->from_account_id,
                $request->to_account_id,
                $request->amount,
                $request->notes,
                $request->reference_number,
                $request->transaction_date
            );

            $result['outgoing']->load([
                'account.accountType',
                'transactionType',
                'relatedAccount',
                'creator'
            ]);

            $result['incoming']->load([
                'account.accountType',
                'transactionType',
                'relatedAccount',
                'creator'
            ]);
            ActivityLogger::success(
                ActivityAction::ACCOUNT_TRANSACTION_TRANSFER,
                "a transféré {$request->amount} de ".$result['outgoing']->account->name,
                [
                    'model_type' => AccountTransaction::class,
                    'model_id' => $result['outgoing']->id,
                    'metadata' => $result
                ],
                "transactions/".$result['outgoing']->id
            );
            return response()->json([
                
                'status' => 'success',
                'message' => 'Transfert effectué avec succès',
                'data' => [
                    'outgoing_transaction' => new TransactionResource($result['outgoing']),
                    'incoming_transaction' => new TransactionResource($result['incoming']),
                ]
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::ACCOUNT_TRANSACTION_TRANSFER,
                "erreur de transfert",
                $e,
                [
                    'model_type' => AccountTransaction::class,
                    'metadata' => $request->validated(),
                ]
            );
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Créer une dépense opérationnelle
     * POST /api/transactions/operational-expense
     */
    public function storeOperationalExpense(StoreOperationalExpenseRequest $request): JsonResponse
    {
        try {
            // Utiliser la nouvelle méthode du modèle
            $transaction = AccountTransaction::recordOperatingExpense(
                $request->account_id,
                $request->expense_category_id,
                $request->amount,
                $request->recipient_name,
                $request->notes, // Maintenant c'est notes qui devient description
                $request->transaction_date,
                $request->reference_number,
                $request->planned_expense_id
            );

            $transaction->load([
                'account.accountType',
                'transactionType',
                'expenseCategory',
                'creator'
            ]);
            ActivityLogger::success(
                    ActivityAction::ACCOUNT_TRANSACTION_CREATED,
                    "a enregistré une Dépense opérationnelle : ID {$transaction->reference_number}, Montant {$transaction->amount}",
                    [
                        'model_type' => AccountTransaction::class,
                        'model_id' => $transaction->id,
                        'metadata' => $transaction->toArray(),
                    ],
                    "transactions/$transaction->id"
                );
            return response()->json([
                'status' => 'success',
                'message' => 'Dépense enregistrée avec succès',
                'data' => new TransactionResource($transaction)
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::class::error(
                ActivityAction::ACCOUNT_TRANSACTION_CREATED,
                "erreur lors de l'enregistrement de la dépense",
                $e,
                [
                    'model_type' => AccountTransaction::class,
                    'metadata' => $request->validated(),
                ]

            );
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement de la dépense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une transaction spécifique
     * GET /api/transactions/{id}
     */
    public function show(AccountTransaction $transaction): JsonResponse
    {
        $transaction->load([
            /* ===================== COMPTE ===================== */
            'account:id,name,account_type_id',
            'account.accountType:id,display_name',

            /* ===================== TYPE ===================== */
            'transactionType:id,code,name,display_name,category',

            /* ===================== RELATIONS OPTIONNELLES ===================== */
            'relatedAccount:id,name',
            'supplier:id,name',
            'freightForwarder:id,name',
            'stockReceipt:id,receipt_number,total_cost_ariary',
            'expenseCategory:id,name',

            /* ===================== VENTE ===================== */
            'sale:id,sale_type,sale_number',
            'sale.credit:id,sale_id',
            'sale.reservation:id,sale_id',

            /* ===================== ANNULATION ===================== */
            'reversingTransaction:id,reference_number,transaction_date,created_at',
            'reversedTransaction:id,reference_number',

            /* ===================== META ===================== */
            'creator:id,name',
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => new TransactionResource($transaction),
        ]);
    }

    /**
     * Annuler une transaction
     * POST /api/transactions/{id}/cancel
     * 
     * Seul le créateur de la transaction peut l'annuler
     */
    public function cancel(AccountTransaction $transaction): JsonResponse
    {
        // Vérifier que l'utilisateur est le créateur
        if (!$transaction->canBeCancelledBy(Auth::id())) {
            
                ActivityLogger::error(
                    ActivityAction::ACCOUNT_TRANSACTION_CANCELLED,
                    "tentative non autorisée d'annuler la transaction ID {$transaction->id} {$transaction->reference_number} par l'utilisateur ID ".Auth::id(),
                    new \Exception('Unauthorized cancellation attempt'),
                    [
                        'model_type' => AccountTransaction::class,
                        'model_id' => $transaction->id,
                        'metadata' => [
                            'user_id' => Auth::id(),
                        ],
                    ]
                );
            
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'êtes pas autorisé à annuler cette transaction'
            ], 403);
        }

        try {
            $transaction->cancel();
                ActivityLogger::success(
                    ActivityAction::ACCOUNT_TRANSACTION_CANCELLED,
                    "a annulé la transaction ID {$transaction->id} {$transaction->reference_number} montant {$transaction->amount}",
                    [
                        'model_type' => AccountTransaction::class,
                        'model_id' => $transaction->id,
                        'metadata' => $transaction->toArray(),
                    ],
                    "transactions/{$transaction->id}"
                );
            return response()->json([
                'status' => 'success',
                'message' => 'Transaction annulée avec succès'
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::ACCOUNT_TRANSACTION_CANCELLED,
                "erreur lors de l'annulation de la transaction ID {$transaction->id} {$transaction->reference_number}",
                $e,
                [
                    'model_type' => AccountTransaction::class,
                    'model_id' => $transaction->id,
                    'metadata' => $transaction->toArray(),
                ]
            );
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'annulation de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}