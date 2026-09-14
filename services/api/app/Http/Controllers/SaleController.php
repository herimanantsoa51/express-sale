<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleResource;
use App\Models\Credit;
use App\Models\Reservation;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SaleController — Méthodes génériques transversales aux 3 types de vente.
 *
 * Les opérations spécifiques sont dans :
 *  - ImmediateSaleController  (ventes immédiates)
 *  - CreditController         (ventes à crédit)
 *  - ReservationController    (réservations)
 */
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
     * Détails d'une vente (tous types)
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
}
