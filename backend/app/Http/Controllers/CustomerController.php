<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\CustomerSaleImmediateResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CustomerCreditResource;
use App\Models\Credit;
use App\Http\Resources\CustomerReservationResource;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
    /**
     * GET /api/customers
     * Liste des clients avec filtres, recherche, tri et pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Compteurs de relations
        $query->withCount(['sales', 'credits', 'reservations']);
        $query->where('is_extra_customer', true);

        // Filtrer par statut : active / inactive / all
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filtrer par niveau de fiabilité
        if ($request->has('reliability')) {
            switch ($request->reliability) {
                case 'excellent':
                    $query->where('reliability_score', '>=', 9.0);
                    break;
                case 'good':
                    $query->whereBetween('reliability_score', [7.0, 8.99]);
                    break;
                case 'average':
                    $query->whereBetween('reliability_score', [5.0, 6.99]);
                    break;
                case 'at_risk':
                    $query->where('reliability_score', '<', 5.0);
                    break;
            }
        }
        // Filtrer les clients VIP
        if ($request->boolean('vip_only')) {
            $query->vip();
        }

        // Filtrer les clients avec crédits actifs
        if ($request->boolean('with_active_credits')) {
            $query->withActiveCredits();
        }

        // Filtrer les clients avec réservations actives
        if ($request->boolean('with_active_reservations')) {
            $query->withActiveReservations();
        }

        // Filtrer les clients à risque
        if ($request->boolean('at_risk_only')) {
            $query->atRisk();
        }

        // Recherche globale (nom ou téléphone)
        if ($request->has('search') && $request->search !== '') {
            $query->search($request->search);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'reliability':
                $query->orderBy('reliability_score', $sortOrder);
                break;
            case 'loyalty_points':
                $query->orderBy('loyalty_points', $sortOrder);
                break;
            case 'credit_limit':
                $query->orderBy('credit_limit', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }

        // Pagination (15 par défaut, max 100)
        $perPage = min($request->get('per_page', 15), 100);
        
        $customers = $query->paginate($perPage);

        return response()->json([
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
            'links' => [
                'first' => $customers->url(1),
                'last' => $customers->url($customers->lastPage()),
                'prev' => $customers->previousPageUrl(),
                'next' => $customers->nextPageUrl(),
            ],
        ]);
    }

    /**
     * POST /api/customers
     * Création d'un client
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Valeurs par défaut
        $data['reliability_score'] = $data['reliability_score'] ?? 5.00;
        $data['loyalty_points'] = $data['loyalty_points'] ?? 0;
        $data['credit_limit'] = $data['credit_limit'] ?? 100000;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_extra_customer'] = $data['is_extra_customer'] ?? false;

        $customer = Customer::create($data);

        return response()->json([
            'message' => 'Client créé avec succès',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * GET /api/customers/{id}
     * Détails d'un client avec statistiques
     */
    public function show($id): JsonResponse
    {
        $customer = Customer::withCount(['sales'=>function($query){
            $query->where('sale_type', 'immediate');
        }, 'credits', 'reservations'])
            ->findOrFail($id);

        return response()->json([
            'data' => new CustomerResource($customer),
            'statistics' => $customer->getProfileSummary(),
        ]);
    }

    /**
     * PUT /api/customers/{id}
     * Mise à jour d'un client
     */
    public function update(UpdateCustomerRequest $request, $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        
        $customer->update($request->validated());

        return response()->json([
            'message' => 'Client mis à jour avec succès',
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * GET /api/customers/{id}/statistics
     * Statistiques détaillées d'un client
     */
    public function statistics($id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        return response()->json([
            'profile' => $customer->getProfileSummary(),
            'payments' => [
                'on_time_count' => $customer->getOnTimePaymentsCount(),
                'late_count' => $customer->getLatePaymentsCount(),
                'on_time_rate' => round($customer->getOnTimePaymentRate(), 2),
            ],
            'purchases' => [
                'total_count' => $customer->getTotalPurchases(),
                'total_spent' => $customer->getTotalSpent(),
                'average_order' => round($customer->getAverageOrderValue(), 2),
                'first_purchase' => $customer->getFirstPurchaseDate()?->toISOString(),
                'last_purchase' => $customer->getLastPurchaseDate()?->toISOString(),
                'days_since_last' => $customer->getDaysSinceLastPurchase(),
                'lifetime_days' => $customer->getCustomerLifetimeDays(),
            ],
            'credit' => [
                'limit' => $customer->credit_limit,
                'used' => $customer->getCurrentCreditUsage(),
                'available' => $customer->getAvailableCredit(),
                'has_overdue' => $customer->hasOverdueCredits(),
            ],
            'reservations' => [
                'cancelled_count' => $customer->getCancelledReservationsCount(),
                'has_active' => $customer->hasActiveReservations(),
            ],
        ]);
    }

    /**
     * POST /api/customers/{id}/adjust-score
     * Ajustement manuel du score de fiabilité (admin)
     */
    public function adjustScore(Request $request, $id): JsonResponse
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:10',
            'reason' => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($id);
        $oldScore = $customer->reliability_score;
        
        $customer->adjustScore($request->score, $request->reason);

        return response()->json([
            'message' => 'Score ajusté avec succès',
            'data' => [
                'old_score' => $oldScore,
                'new_score' => $customer->reliability_score,
                'reliability_level' => $customer->getReliabilityLevel(),
            ],
        ]);
    }

    /**
     * POST /api/customers/{id}/recalculate-score
     * Recalcul automatique du score de fiabilité
     */
    public function recalculateScore($id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $oldScore = $customer->reliability_score;
        
        $customer->recalculateReliabilityScore();

        return response()->json([
            'message' => 'Score recalculé avec succès',
            'data' => [
                'old_score' => $oldScore,
                'new_score' => $customer->reliability_score,
                'reliability_level' => $customer->getReliabilityLevel(),
            ],
        ]);
    }

    /**
     * POST /api/customers/{id}/add-loyalty-points
     * Ajout manuel de points de fidélité
     */
    public function addLoyaltyPoints(Request $request, $id): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $customer = Customer::findOrFail($id);
        $oldPoints = $customer->loyalty_points;
        
        $customer->addLoyaltyPoints($request->points, $request->reason);

        return response()->json([
            'message' => 'Points de fidélité ajoutés',
            'data' => [
                'old_points' => $oldPoints,
                'added_points' => $request->points,
                'new_points' => $customer->loyalty_points,
            ],
        ]);
    }

    /**
     * POST /api/customers/{id}/use-loyalty-points
     * Utilisation de points de fidélité
     */
    public function useLoyaltyPoints(Request $request, $id): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $customer = Customer::findOrFail($id);
        
        if (!$customer->useLoyaltyPoints($request->points)) {
            return response()->json([
                'message' => 'Points insuffisants',
                'data' => [
                    'available_points' => $customer->loyalty_points,
                    'requested_points' => $request->points,
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Points de fidélité utilisés',
            'data' => [
                'used_points' => $request->points,
                'remaining_points' => $customer->loyalty_points,
            ],
        ]);
    }

    /**
     * POST /api/customers/{id}/adjust-credit-limit
     * Ajustement automatique de la limite de crédit selon le score
     */
    public function adjustCreditLimit($id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $oldLimit = $customer->credit_limit;
        
        $customer->adjustCreditLimit();

        return response()->json([
            'message' => 'Limite de crédit ajustée',
            'data' => [
                'old_limit' => $oldLimit,
                'new_limit' => $customer->credit_limit,
                'reliability_score' => $customer->reliability_score,
            ],
        ]);
    }

    /**
     * GET /api/customers/{id}/can-get-credit
     * Vérifie si le client peut obtenir un crédit
     */
    public function canGetCredit(Request $request, $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $customer = Customer::findOrFail($id);
        $canGet = $customer->canGetCredit($request->amount);

        $reasons = [];
        if (!$customer->is_active) {
            $reasons[] = 'Client inactif';
        }
        if ($customer->reliability_score < 4.0) {
            $reasons[] = 'Score de fiabilité insuffisant (min: 4.0)';
        }
        if ($customer->getAvailableCredit() < $request->amount) {
            $reasons[] = 'Limite de crédit insuffisante';
        }

        return response()->json([
            'can_get_credit' => $canGet,
            'requested_amount' => $request->amount,
            'available_credit' => $customer->getAvailableCredit(),
            'reasons' => $canGet ? [] : $reasons,
        ]);
    }
    /**
     * GET /api/customers/{id}/sales/immediate
     * Liste des ventes au comptant d'un client avec pagination
     */
    public function getCustomerSaleImmediate(Request $request, $id): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);
    
        $sales = Sale::where('customer_id', $id)
            ->where('sale_type', 'immediate')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    
        return CustomerSaleImmediateResource::collection($sales)
            ->additional([
                'meta' => [
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                    'per_page' => $sales->perPage(),
                    'total' => $sales->total(),
                ],
            ])
            ->response();
    }



    public function getCustomerCredits(Request $request, $id): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);

        $credits = Credit::where('customer_id', $id)
            ->with('sale.user')
            ->orderBy('credit_date', 'desc')
            ->paginate($perPage);

        return CustomerCreditResource::collection($credits)
            ->additional([
                'meta' => [
                    'current_page' => $credits->currentPage(),
                    'last_page' => $credits->lastPage(),
                    'per_page' => $credits->perPage(),
                    'total' => $credits->total(),
                ],
            ]
            )->response();
    }


    public function getCustomerReservations(Request $request, $id): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);

        $reservations = Reservation::where('customer_id', $id)
            ->with('sale.user')
            ->orderBy('reservation_date', 'desc')
            ->paginate($perPage);

        return CustomerReservationResource::collection($reservations)
            ->additional([
                'meta' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                ],
            ])->response();
    }

    /**
     * GET /api/customers/search-for-sale
     * Recherche de clients pour l'ajout dans une vente
     */
    public function searchForSale(Request $request)
    {   


        try{
            $request->validate([
                'search' => 'required|string|min:2|max:100',
            ]);
    
            $searchTerm = '%' . $request->search . '%';
    
            // Recherche simple et rapide
            $customers = Customer::query()
                ->select([
                    'id',
                    'name',
                    'phone',
                    'address',
                    'customer_number',
                    'reliability_score',
                    'loyalty_points',
                    DB::raw("(
                        SELECT COUNT(*) 
                        FROM sales 
                        WHERE sales.customer_id = customers.id
                    ) as total_sales_count")
                ])
                ->where(function ($query) use ($searchTerm) {
                    // Recherche sur tous les champs
                    $query->where('name', 'ILIKE', $searchTerm)
                        ->orWhere('phone', 'ILIKE', $searchTerm)
                        ->orWhere('address', 'ILIKE', $searchTerm)
                        ->orWhere('customer_number', 'ILIKE', $searchTerm);
                })
                ->where('is_active', true)
                ->orderByRaw("
                    CASE 
                        WHEN name ILIKE ? THEN 1
                        WHEN customer_number ILIKE ? THEN 2
                        WHEN phone ILIKE ? THEN 3
                        ELSE 4
                    END
                ", [$request->search, $request->search, $request->search])
                ->orderBy('reliability_score', 'desc')
                ->orderBy('name')
                ->limit(15)
                ->get();
    
            return response()->json([
                'data' => $customers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'address' => $customer->address,
                        'customer_number' => $customer->customer_number,
                        'total_sales_count' => (int) $customer->total_sales_count,
                        'reliability_score' => (float) $customer->reliability_score,
                        'loyalty_points' => (int) $customer->loyalty_points,
                        'is_vip' => $customer->total_sales_count > 10, // VIP si plus de 10 ventes
                    ];
                }),
                'meta' => [
                    'search_term' => $request->search,
                    'result_count' => $customers->count(),
                ]
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        }
        
    }


}
