<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\SaleType;
use App\Http\Requests\CompleteReservationRequest;
use App\Http\Requests\PayCreditInstallmentRequest;
use App\Http\Requests\StoreCreditSaleRequest;
use App\Http\Requests\StoreImmediateSaleRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\CreditInstallmentResource;
use App\Http\Resources\CreditListResource;
use App\Http\Resources\CreditResource;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\SaleResource;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\Reservation;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ImmediateSaleListResource;
use App\Http\Resources\ImmediateSaleDetailResource;
use Carbon\Carbon;
use App\Http\Resources\ReservationListResource;


class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * GET /api/sales
     * Liste des ventes avec filtres, recherche et pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['customer', 'user', 'items.variant.product']);

        // Filtre par type de vente
        if ($request->has('sale_type') && in_array($request->sale_type, ['immediate', 'credit', 'reservation'])) {
            $query->where('sale_type', $request->sale_type);
        }

        // Filtre par statut de paiement
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filtre par client
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filtre par vendeur
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre par période
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('sale_date', [$request->start_date, $request->end_date]);
        } elseif ($request->has('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        } elseif ($request->has('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        // Filtre pour aujourd'hui
        if ($request->boolean('today')) {
            $query->whereDate('sale_date', today());
        }

        // Recherche par numéro de vente
        if ($request->has('search') && $request->search !== '') {
            $query->where('sale_number', 'ILIKE', "%{$request->search}%");
        }

        // Tri
        $sortBy = $request->get('sort_by', 'sale_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $sales = $query->paginate($perPage);

        return response()->json([
            'data' => SaleResource::collection($sales),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * GET /api/sales/{id}
     * Détails d'une vente
     */
    public function show(int $id): JsonResponse
    {
        $sale = Sale::with([
            'customer',
            'user',
            'items.variant.product',
            'items.variant.attributeValues.attributeType',
            'credit.installments',
            'reservation',
            'transactions.account',
        ])->findOrFail($id);

        return response()->json([
            'data' => new SaleResource($sale),
        ]);
    }

    /**
     * POST /api/sales/immediate
     * Créer une vente immédiate (payée)
     */
    public function storeImmediate(StoreImmediateSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->saleService->createImmediateSale($request->validated());

            return response()->json([
                'message' => 'Vente créée avec succès',
                'data' => new SaleResource($sale),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur création vente immédiate', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la création de la vente',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 422);
        }
    }

    /**
     * POST /api/sales/credit
     * Créer une vente à crédit
     */
    public function storeCredit(StoreCreditSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->saleService->createCreditSale($request->validated());

            return response()->json([
                'message' => 'Vente à crédit créée avec succès',
                'data' => new SaleResource($sale),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la vente à crédit',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/sales/reservation
     * Créer une réservation
     */
    public function storeReservation(StoreReservationRequest $request): JsonResponse
    {
        try {
            $sale = $this->saleService->createReservation($request->validated());

            return response()->json([
                'message' => 'Réservation créée avec succès',
                'data' => new SaleResource($sale),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la réservation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/sales/statistics
     * Statistiques globales des ventes
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        // Ventes par type
        $salesByType = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('sale_type, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('sale_type')
            ->get()
            ->keyBy('sale_type');

        // Ventes par statut de paiement
        $salesByStatus = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('payment_status, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        // Totaux
        $totals = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total, SUM(discount_amount) as discounts')
            ->first();

        // Ventes d'aujourd'hui
        $today = Sale::whereDate('sale_date', today())
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();

        // Crédits en cours
        $activeCredits = Credit::whereIn('status', ['active', 'partial_paid', 'overdue'])
            ->selectRaw('COUNT(*) as count, SUM(amount_due) as total_due')
            ->first();

        // Réservations actives
        $activeReservations = Reservation::whereIn('status', ['pending', 'confirmed', 'partial_paid'])
            ->where('expiry_date', '>', now())
            ->selectRaw('COUNT(*) as count, SUM(remaining_amount) as total_remaining')
            ->first();

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'count' => (int) $totals->count,
                'total_amount' => (float) $totals->total,
                'total_discounts' => (float) $totals->discounts,
            ],
            'today' => [
                'count' => (int) $today->count,
                'total_amount' => (float) $today->total,
            ],
            'by_type' => [
                'immediate' => [
                    'count' => (int) ($salesByType['immediate']->count ?? 0),
                    'total' => (float) ($salesByType['immediate']->total ?? 0),
                ],
                'credit' => [
                    'count' => (int) ($salesByType['credit']->count ?? 0),
                    'total' => (float) ($salesByType['credit']->total ?? 0),
                ],
                'reservation' => [
                    'count' => (int) ($salesByType['reservation']->count ?? 0),
                    'total' => (float) ($salesByType['reservation']->total ?? 0),
                ],
            ],
            'by_status' => [
                'paid' => [
                    'count' => (int) ($salesByStatus['paid']->count ?? 0),
                    'total' => (float) ($salesByStatus['paid']->total ?? 0),
                ],
                'pending' => [
                    'count' => (int) ($salesByStatus['pending']->count ?? 0),
                    'total' => (float) ($salesByStatus['pending']->total ?? 0),
                ],
                'partial' => [
                    'count' => (int) ($salesByStatus['partial']->count ?? 0),
                    'total' => (float) ($salesByStatus['partial']->total ?? 0),
                ],
            ],
            'active_credits' => [
                'count' => (int) $activeCredits->count,
                'total_due' => (float) $activeCredits->total_due,
            ],
            'active_reservations' => [
                'count' => (int) $activeReservations->count,
                'total_remaining' => (float) $activeReservations->total_remaining,
            ],
        ]);
    }

    // ==================== CRÉDITS ====================

    /**
     * GET /api/credits
     * Liste des crédits avec filtres
     */
    public function indexCredits(Request $request): JsonResponse
    {   

        
        $query = Credit::with(['customer:id,customer_number,name', 
                                'sale:id,sale_number,user_id']);
        
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        // Filtre par statut
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par vendeur
        if ($request->has('created_by')) {
            $query->whereHas('sale',function($q) use ($request){
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
        // Tri
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
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

    public function showCredit(int $id): JsonResponse
{
    $credit = Credit::with([
        // ===== CREDIT =====
        'sale:id,sale_number,subtotal,discount_amount,total_amount,discount_reason',
        'customer:id,name,phone',

        // ===== SALE ITEMS =====
        // CORRECTION ICI : image_url au lieu de image_path
        'sale.items.variant.product:id,name,image_url',
        'sale.items.variant.attributeValues.attributeValue.attributeType:id,name,display_name',

        // ===== INSTALLMENTS =====
        'installments:id,credit_id,installment_number,due_date,amount_due,amount_paid,status',

        // ===== INSTALLMENT TRANSACTIONS =====
        'installments.installmentTransactions:id,installment_id,transaction_id,amount,payment_date',

        // ===== ACCOUNT TRANSACTIONS =====
        'installments.installmentTransactions.transaction' => function ($q) {
            $q->select(
                'id',
                'account_id',
                'transaction_type_id',
                'amount',
                'notes',
                'balance_before',
                'balance_after',
                'transaction_date',
                'created_by'
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
     * POST /api/credits/{creditId}/installments/{installmentId}/pay
     * Payer une échéance de crédit
     */
    public function payInstallment(PayCreditInstallmentRequest $request, int $creditId, int $installmentId): JsonResponse
    {
        try {
            // Vérifier que l'échéance appartient bien au crédit
            $installment = CreditInstallment::where('credit_id', $creditId)
                ->where('id', $installmentId)
                ->firstOrFail();

            $installment = $this->saleService->payCreditInstallment($installmentId, $request->validated());

            return response()->json([
                'message' => 'Paiement enregistré avec succès',
                'data' => new CreditInstallmentResource($installment),
            ]);
        } catch (\Exception $e) {
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
    public function overdueCredits(): JsonResponse
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
    public function dueSoonCredits(Request $request): JsonResponse
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

    // ==================== RÉSERVATIONS ====================

    /**
     * GET /api/reservations
     * Liste des réservations avec filtres
     */
    public function indexReservations(Request $request): JsonResponse
    {
        // Chargement minimal des relations nécessaires
        $query = Reservation::with([
            'sale:id,sale_number,customer_id,user_id',
            'sale.customer:id,name,customer_number',
        ]);

        // Filtre par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par vendeur (user_id du sale) - REMPLACÉ customer_id
        if ($request->has('user_id')) {
            $query->whereHas('sale', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        // Dans le contrôleur, remplacez la partie recherche par :
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtre par date/heure de réservation (FROM)
        if ($request->filled('reservation_date_from')) {
            $query->where('reservation_date', '>=', $request->reservation_date_from);
        }

        // Filtre par date/heure de réservation (TO)
        if ($request->filled('reservation_date_to')) {
            $query->where('reservation_date', '<=', $request->reservation_date_to);
        }

        // Filtre par date/heure d'expiration (FROM)
        if ($request->filled('expiry_date_from')) {
            $query->where('expiry_date', '>=', $request->expiry_date_from);
        }

        // Filtre par date/heure d'expiration (TO)
        if ($request->filled('expiry_date_to')) {
            $query->where('expiry_date', '<=', $request->expiry_date_to);
        }

        // Filtrer les réservations actives
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filtrer les réservations expirées
        if ($request->boolean('expired_only')) {
            $query->expired();
        }

        

        // Filtrer les réservations expirant bientôt
        if ($request->has('expiring_soon_days')) {
            $query->expiringSoon((int) $request->expiring_soon_days);
        }

        // Tri avec colonnes autorisées
        $allowedSortColumns = [
            'reservation_date', 
            'expiry_date', 
            'total_amount', 
            'remaining_amount',
            'status',
            'sale_id',
            'created_at',
            'updated_at'
        ];
        
        $sortBy = in_array($request->get('sort_by'), $allowedSortColumns) 
            ? $request->get('sort_by') 
            : 'reservation_date';
            
        $sortOrder = in_array(strtolower($request->get('sort_order')), ['asc', 'desc']) 
            ? $request->get('sort_order') 
            : 'desc';
        
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $reservations = $query->paginate($perPage);

        return response()->json([
            'data' => ReservationListResource::collection($reservations),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }
    /**
     * GET /api/reservations/{id}
     * Détails d'une réservation
     */
    public function showReservation(int $id): JsonResponse
    {
        $reservation = Reservation::with([
            // Client
            'customer:id,name,phone,customer_number',
            
            // Vente avec infos essentielles
            'sale:id,sale_number,user_id,subtotal,discount_amount,total_amount,payment_status,sale_date',
            
            // Items de vente avec leurs relations
            'sale.items.variant.product:id,name,image_url',
            'sale.items.variant.attributeValues.attributeValue.attributeType:id,name,display_name',
            
            // Transactions bancaires
            'sale.transactions' => function ($q) {
                $q->select(
                    'id',
                    'account_id',
                    'sale_id',
                    'transaction_type_id',
                    'amount',
                    'notes',
                    'balance_before',
                    'balance_after',
                    'transaction_date',
                    'created_by'
                )->with([
                    'account:id,name',
                    'transactionType:id,name',
                    'creator:id,name',
                ]);
            },
            
            // Vendeur
            'sale.user:id,name',
        ])->findOrFail($id);

        return response()->json([
            'data' => new ReservationResource($reservation),
        ]);
    }
    /**
     * POST /api/reservations/{id}/complete
     * Compléter une réservation (paiement final)
     */
    public function completeReservation(CompleteReservationRequest $request, int $id): JsonResponse
    {
        try {
            $reservation = $this->saleService->completeReservation($id, $request->validated());

            return response()->json([
                'message' => 'Réservation complétée avec succès',
                'data' => new ReservationResource($reservation),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la complétion de la réservation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/reservations/{id}/cancel
     * Annuler une réservation
     */
    public function cancelReservation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $reservation = $this->saleService->cancelReservation($id, $request->reason);

            return response()->json([
                'message' => 'Réservation annulée avec succès',
                'data' => new ReservationResource($reservation),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'annulation de la réservation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/reservations/{id}/add-deposit
     * Ajouter un acompte supplémentaire à une réservation
     */
    public function addDeposit(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'payment_method' => 'required|string',
        ]);

        try {
            $reservation = Reservation::with('sale')->findOrFail($id);

            if (!$reservation->isActive()) {
                throw new \Exception("Cette réservation n'est plus active");
            }

            $amount = min($request->amount, $reservation->remaining_amount);

            // Créer la transaction via la méthode publique du service
            $this->saleService->createSaleTransactionPublic(
                $reservation->sale,
                $request->account_id,
                $amount,
                "Acompte supplémentaire réservation #{$reservation->sale->sale_number}"
            );

            // Mettre à jour la réservation
            $reservation->deposit_amount += $amount;
            $reservation->remaining_amount -= $amount;

            if ($reservation->remaining_amount <= 0) {
                $reservation->status = 'completed';
                $reservation->completed_at = now();
                $reservation->sale->update(['payment_status' => PaymentStatus::PAID]);
            } else {
                $reservation->status = 'partial_paid';
                $reservation->sale->update(['payment_status' => PaymentStatus::PARTIAL]);
            }

            $reservation->save();

            return response()->json([
                'message' => 'Acompte ajouté avec succès',
                'data' => new ReservationResource($reservation->load(['sale.transactions', 'customer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'ajout de l\'acompte',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/reservations/expiring-soon
     * Réservations expirant bientôt
     */
    public function expiringSoonReservations(Request $request): JsonResponse
    {
        $days = $request->get('days', 3);

        $reservations = Reservation::with(['customer', 'sale'])
            ->expiringSoon($days)
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'data' => ReservationResource::collection($reservations),
            'summary' => [
                'count' => $reservations->count(),
                'total_remaining' => $reservations->sum('remaining_amount'),
                'days' => $days,
            ],
        ]);
    }

    /**
     * GET /api/reservations/expired
     * Réservations expirées
     */
    public function expiredReservations(): JsonResponse
    {
        $reservations = Reservation::with(['customer', 'sale'])
            ->expired()
            ->orderBy('expiry_date', 'desc')
            ->get();

        return response()->json([
            'data' => ReservationResource::collection($reservations),
            'summary' => [
                'count' => $reservations->count(),
                'total_deposits' => $reservations->sum('deposit_amount'),
            ],
        ]);
    }


    /**
     * GET /api/sales/immediate
     * Liste des ventes immédiates avec filtres, recherche et pagination
     */
    public function immediateSaleIndex(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
    
        $query = Sale::query()
            ->where('sale_type', SaleType::IMMEDIATE)
            ->with([
                'customer:id,name,customer_number,loyalty_points',
                'user:id,name',
                'accountTransaction:id,sale_id,reference_number'
            ]);
    
        // filtre date
        if ($request->filled(['from_date', 'to_date'])) {

            $from = Carbon::parse($request->from_date)->utc();
            $to   = Carbon::parse($request->to_date)->utc();
        
            Log::info("Filtering immediate sales from {$from} to {$to}");
        
            $query->whereBetween('sale_date', [$from, $to]);
        }
    
        // filtre vendeur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    
        // recherche
        if ($request->filled('search')) {
            $search = $request->search;
    
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'ILIKE', "%{$search}%")
                  ->orWhereHas('customer', function ($c) use ($search) {
                      $c->where('customer_number', 'ILIKE', "%{$search}%");
                  })
                  ->orWhereHas('accountTransaction', function ($t) use ($search) {
                      $t->where('reference_number', 'ILIKE', "%{$search}%");
                  });
            });
        }
    
        $query->orderBy('created_at', 'desc');
    
        return ImmediateSaleListResource::collection(
            $query->paginate($perPage)
        );
    }
   

    public function immediateSaleShow(int $id)
    {
        $sale = Sale::where('sale_type', SaleType::IMMEDIATE)
            ->with([
                'customer:id,name,customer_number,loyalty_points',
                'user:id,name',
                'items.variant.product:id,name',
                'items.variant.attributeValues.attributeValue.attributeType',
                'accountTransaction.account',
                'accountTransaction.transactionType',
            ])
            ->findOrFail($id);

        return new ImmediateSaleDetailResource($sale);
    }


}
