<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Requests\PayCreditInstallmentRequest;
use App\Http\Requests\StoreCreditSaleRequest;
use App\Http\Resources\CreditInstallmentResource;
use App\Http\Resources\CreditListResource;
use App\Http\Resources\CreditResource;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\InstallmentTransaction;
use App\Services\CreditSaleService;
use App\Services\PosPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    protected CreditSaleService $creditSaleService;

    protected PosPrintService $posPrintService;

    public function __construct(CreditSaleService $creditSaleService, PosPrintService $posPrintService)
    {
        $this->creditSaleService = $creditSaleService;
        $this->posPrintService = $posPrintService;
    }

    /**
     * GET /api/credits
     * Liste des crédits avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Credit::with([
            'customer:id,customer_number,name',
            'sale:id,sale_number,user_id',
        ]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtre par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par vendeur
        if ($request->has('created_by')) {
            $query->whereHas('sale', function ($q) use ($request) {
                $q->where('user_id', $request->created_by);
            });
        }

        // Filtrer les crédits actifs
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filtrer les crédits en retard
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        // Filtrer les crédits avec échéance proche
        if ($request->has('due_soon_days')) {
            $query->dueSoon((int) $request->due_soon_days);
        }

        $allowedSortColumns = ['credit_date', 'due_date', 'total_amount', 'amount_due'];
        $sortBy = in_array($request->get('sort_by'), $allowedSortColumns)
            ? $request->get('sort_by')
            : 'credit_date';

        $sortOrder = in_array(strtolower($request->get('sort_order')), ['asc', 'desc'])
            ? $request->get('sort_order')
            : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->get('per_page', 15), 100);
        $credits = $query->paginate($perPage);

        return response()->json([
            'data' => CreditListResource::collection($credits),
            'meta' => [
                'current_page' => $credits->currentPage(),
                'last_page' => $credits->lastPage(),
                'per_page' => $credits->perPage(),
                'total' => $credits->total(),
            ],
        ]);
    }

    /**
     * GET /api/credits/{id}
     * Détail d'un crédit
     */
    public function show(int $id): JsonResponse
    {
        $credit = Credit::with([
            'sale:id,sale_number,subtotal,discount_amount,total_amount,discount_reason',
            'customer:id,name,phone',
            'sale.items.variant.product:id,name,image_url',
            'sale.items.variant.attributeValues.attributeValue.attributeType:id,name,display_name',
            'installments:id,credit_id,installment_number,due_date,amount_due,amount_paid,status',
            'installments.installmentTransactions:id,installment_id,transaction_id,amount,payment_date',
            'installments.installmentTransactions.transaction' => function ($q) {
                $q->select(
                    'id',
                    'account_id',
                    'transaction_type_id',
                    'reference_number',
                    'amount',
                    'notes',
                    'balance_before',
                    'balance_after',
                    'transaction_date',
                    'created_by',
                    'reversed_transaction_id'
                )->with([
                    'account:id,name,account_type_id',
                    'account.accountType:id,display_name',
                    'transactionType:id,name,category',
                    'creator:id,name',
                ]);
            },
        ])->findOrFail($id);

        return response()->json([
            'data' => new CreditResource($credit),
        ]);
    }

    /**
     * POST /api/sales/credit
     * Créer une vente à crédit
     */
    public function store(StoreCreditSaleRequest $request): JsonResponse
    {
        try {
            $credit = $this->creditSaleService->createCreditSale($request->validated());

            $printResult = $this->posPrintService->autoPrintIfEnabled(
                'credit',
                fn () => $this->posPrintService->printCredit($credit)
            );
            ActivityLogger::success(
                ActivityAction::CREDIT_CREATED,
                " a créé une vente à crédit d'une valeur de {$credit->total_amount}",
                [
                    'model_type' => Credit::class,
                    'model_id' => $credit->id,
                    'metadata' => $credit->toArray(),
                ],
                "ventes/credits/{$credit->id}"
            );

            return response()->json([
                'message' => 'Vente à crédit créée avec succès',
                'data' => new CreditResource($credit),
                'print_info' => $printResult,
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::CREDIT_CREATED,
                " la création d'une vente à crédit a échoué",
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );
            Log::error('Erreur création vente crédit', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création de la vente à crédit',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/credits/{creditId}/installments/{installmentId}/pay
     * Payer une échéance de crédit
     */
    public function payInstallment(PayCreditInstallmentRequest $request, int $creditId, int $installmentId): JsonResponse
    {
        try {
            $installment = CreditInstallment::where('credit_id', $creditId)
                ->where('id', $installmentId)
                ->firstOrFail();

            $installment = $this->creditSaleService->payCreditInstallment($installmentId, $request->validated());

            $installmentTransaction = $installment->installmentTransactions()
                ->with([
                    'transaction.account.accountType',
                    'transaction.creator',
                ])
                ->latest()
                ->first();

            $printResult = null;
            if ($installmentTransaction) {
                $printResult = $this->posPrintService->autoPrintIfEnabled(
                    'credit_payment',
                    fn () => $this->posPrintService->printCreditPayment($installmentTransaction)
                );
            }
            ActivityLogger::success(
                ActivityAction::INSTALLMENT_PAID,
                " a reçu le paiement d'un crédit de montant {$installmentTransaction->amount}",
                [
                    'model_type' => InstallmentTransaction::class,
                    'model_id' => $installmentTransaction->id,
                    'metadata' => $installmentTransaction->toArray(),
                ],
                "/transactions/{$installmentTransaction->transaction->id}"
            );

            return response()->json([
                'message' => 'Paiement enregistré avec succès',
                'data' => new CreditInstallmentResource($installment),
                'print_info' => $printResult,
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::INSTALLMENT_PAID,
                "le paiement d'un crédit a échoué",
                $e,
                [
                    'metadata' => $request->validated(),
                ],
            );
            Log::error('Erreur paiement échéance', [
                'credit_id' => $creditId,
                'installment_id' => $installmentId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du paiement',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/credits/overdue
     * Liste des crédits en retard
     */
    public function overdue(): JsonResponse
    {
        $credits = Credit::with(['customer', 'sale', 'installments'])
            ->overdue()
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'data' => CreditResource::collection($credits),
            'summary' => [
                'count' => $credits->count(),
                'total_overdue' => $credits->sum('amount_due'),
            ],
        ]);
    }

    /**
     * GET /api/credits/due-soon
     * Crédits avec échéance proche
     */
    public function dueSoon(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);

        $credits = Credit::with(['customer', 'sale', 'installments'])
            ->dueSoon($days)
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'data' => CreditResource::collection($credits),
            'summary' => [
                'count' => $credits->count(),
                'total_due' => $credits->sum('amount_due'),
                'days' => $days,
            ],
        ]);
    }

    /**
     * POST /api/credits/cancel/{credit}
     * Annuler un crédit
     */
    public function cancel(Credit $credit): JsonResponse
    {
        try {
            $credit = $this->creditSaleService->cancelCredit($credit);
            ActivityLogger::success(
                ActivityAction::CREDIT_CANCELLED,
                " a annulée la vente à crédit  {$credit->sale->sale_number}",
                [
                    'model_type' => Credit::class,
                    'model_id' => $credit->id,
                    'metadata' => $credit->toArray(),
                ],
                "ventes/credits/{$credit->id}"
            );

            return response()->json([
                'message' => 'Vente à crédit annulée avec succès',
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::CREDIT_CANCELLED,
                " l'annulation de la vente à crédit  {$credit->sale->sale_number} a échouée",
                $e,
                [
                    'model_type' => Credit::class,
                    'model_id' => $credit->id,
                    'metadata' => $credit->toArray(),
                ],
            );

            return response()->json([
                'message' => 'Erreur lors de l\'annulation de la vente à crédit',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
