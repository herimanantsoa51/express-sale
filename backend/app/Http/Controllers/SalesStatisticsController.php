<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Credit;
use App\Models\Reservation;
use App\Enums\SaleType;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesStatisticsController extends Controller
{
    
    /**
     * Vue d'ensemble des ventes (KPIs principaux)
     * GET /api/statistics/sales/overview
     */
    public function overview(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:today,week,month,year',
        ]);

        $period = $this->getPeriod($request);

        $query = Sale::betweenDates($period['start'], $period['end']);

        // Calcul du total moyen (exclure les valeurs nulles et cancelled)
        $averageSaleValue = Sale::betweenDates($period['start'], $period['end'])
            ->where('payment_status', '!=', PaymentStatus::CANCELLED)
            ->avg('total_amount');

        // Calcul par type de vente
        $byType = [
            'immediate' => [
                'count' => (clone $query)->where('sale_type', 'immediate')->count(),
                'revenue' => (float) (clone $query)->where('sale_type', 'immediate')->sum('total_amount'),
            ],
            'credit' => [
                'count' => (clone $query)->where('sale_type', 'credit')->count(),
                'revenue' => (float) (clone $query)->where('sale_type', 'credit')->sum('total_amount'),
            ],
            'reservation' => [
                'count' => (clone $query)->where('sale_type', 'reservation')->count(),
                'revenue' => (float) (clone $query)->where('sale_type', 'reservation')->sum('total_amount'),
            ],
        ];

        // CORRECTION MAJEURE : Statuts de paiement selon le type de vente
        $byPaymentStatus = $this->calculatePaymentStatusByType($period);

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['label'],
            ],
            'kpis' => [
                'total_sales_count' => $query->count(),
                'total_revenue' => (float) $query->sum('total_amount'),
                'total_paid' => (float) $this->getTotalPaid($period),
                'total_pending' => (float) $this->getTotalPending($period),
                'average_sale_value' => $averageSaleValue ? round((float) $averageSaleValue, 2) : 0,
                'total_discount_given' => (float) $query->sum('discount_amount'),
            ],
            'by_type' => $byType,
            'by_payment_status' => $byPaymentStatus,
        ]);
     }
    
        /**
     * Calcule les statuts de paiement en fonction du type de vente
     * - immediate: utilise sales.payment_status
     * - credit: utilise credits.status
     * - reservation: utilise reservations.status
     */
    private function calculatePaymentStatusByType(array $period): array
    {
        // 1. VENTES IMMÉDIATES - Utilise sales.payment_status
        $immediateStats = Sale::selectRaw("
                payment_status,
                COUNT(*) as count,
                SUM(sales.total_amount) as amount
            ")
            ->where('sale_type', 'immediate')
            ->whereBetween('sale_date', [$period['start'], $period['end']])
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        // 2. CRÉDITS - Utilise credits.status
        $creditStats = Credit::selectRaw("
                credits.status,
                COUNT(*) as count,
                SUM(credits.total_amount) as amount
            ")
            ->join('sales', 'credits.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('credits.status')
            ->get()
            ->keyBy('status');

        // 3. RÉSERVATIONS - Utilise reservations.status
        $reservationStats = Reservation::selectRaw("
                reservations.status,
                COUNT(*) as count,
                SUM(reservations.total_amount) as amount
            ")
            ->join('sales', 'reservations.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('reservations.status')
            ->get()
            ->keyBy('status');

        return [
            'immediate' => [
                'paid' => [
                    'count' => (int) ($immediateStats->get('paid')->count ?? 0),
                    'amount' => (float) ($immediateStats->get('paid')->amount ?? 0),
                ],
                'partial' => [
                    'count' => (int) ($immediateStats->get('partial')->count ?? 0),
                    'amount' => (float) ($immediateStats->get('partial')->amount ?? 0),
                ],
                'pending' => [
                    'count' => (int) ($immediateStats->get('pending')->count ?? 0),
                    'amount' => (float) ($immediateStats->get('pending')->amount ?? 0),
                ],
                'cancelled' => [
                    'count' => (int) ($immediateStats->get('cancelled')->count ?? 0),
                    'amount' => (float) ($immediateStats->get('cancelled')->amount ?? 0),
                ],
            ],
            'credit' => [
                'active' => [
                    'count' => (int) ($creditStats->get('active')->count ?? 0),
                    'amount' => (float) ($creditStats->get('active')->amount ?? 0),
                ],
                'partial_paid' => [
                    'count' => (int) ($creditStats->get('partial_paid')->count ?? 0),
                    'amount' => (float) ($creditStats->get('partial_paid')->amount ?? 0),
                ],
                'completed' => [
                    'count' => (int) ($creditStats->get('completed')->count ?? 0),
                    'amount' => (float) ($creditStats->get('completed')->amount ?? 0),
                ],
                'overdue' => [
                    'count' => (int) ($creditStats->get('overdue')->count ?? 0),
                    'amount' => (float) ($creditStats->get('overdue')->amount ?? 0),
                ],
                'defaulted' => [
                    'count' => (int) ($creditStats->get('defaulted')->count ?? 0),
                    'amount' => (float) ($creditStats->get('defaulted')->amount ?? 0),
                ],
                'recovered' => [
                    'count' => (int) ($creditStats->get('recovered')->count ?? 0),
                    'amount' => (float) ($creditStats->get('recovered')->amount ?? 0),
                ],
            ],
            'reservation' => [
                'pending' => [
                    'count' => (int) ($reservationStats->get('pending')->count ?? 0),
                    'amount' => (float) ($reservationStats->get('pending')->amount ?? 0),
                ],
                'confirmed' => [
                    'count' => (int) ($reservationStats->get('confirmed')->count ?? 0),
                    'amount' => (float) ($reservationStats->get('confirmed')->amount ?? 0),
                ],
                'partial_paid' => [
                    'count' => (int) ($reservationStats->get('partial_paid')->count ?? 0),
                    'amount' => (float) ($reservationStats->get('partial_paid')->amount ?? 0),
                ],
                'completed' => [
                    'count' => (int) ($reservationStats->get('completed')->count ?? 0),
                    'amount' => (float) ($reservationStats->get('completed')->amount ?? 0),
                ],
                'expired' => [
                    'count' => (int) ($reservationStats->get('expired')->count ?? 0),
                    'amount' => (float) ($reservationStats->get('expired')->amount ?? 0),
                ],
                'cancelled' => [
                    'count' => (int) ($reservationStats->get('cancelled')->count ?? 0),
                    'amount' => (float) ($reservationStats->get('cancelled')->amount ?? 0),
                ],
            ],
        ];
    }

            /**
             * Calcule le total payé (toutes sources confondues)
             */
            private function getTotalPaid(array $period): float
            {
                // Ventes immédiates payées
                $immediatePaid = Sale::where('sale_type', 'immediate')
                    ->where('payment_status', 'paid')
                    ->whereBetween('sale_date', [$period['start'], $period['end']])
                    ->sum('total_amount');

                // Crédits complétés (entièrement payés)
                $creditsPaid = Credit::where('status', 'completed')
                    ->join('sales', 'credits.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                    ->sum('credits.total_amount');

                // Réservations complétées
                $reservationsPaid = Reservation::where('status', 'completed')
                    ->join('sales', 'reservations.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                    ->sum('reservations.total_amount');

                return (float) ($immediatePaid + $creditsPaid + $reservationsPaid);
            }

            /**
             * Calcule le total en attente (toutes sources confondues)
             */
            private function getTotalPending(array $period): float
            {
                // Ventes immédiates pending ou partial
                $immediatePending = Sale::where('sale_type', 'immediate')
                    ->whereIn('payment_status', ['pending', 'partial'])
                    ->whereBetween('sale_date', [$period['start'], $period['end']])
                    ->sum('total_amount');

                // Crédits actifs (montant restant dû)
                $creditsPending = Credit::whereIn('status', ['active', 'partial_paid', 'overdue'])
                    ->join('sales', 'credits.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                    ->sum('credits.amount_due');

                // Réservations actives (montant restant)
                $reservationsPending = Reservation::whereIn('status', ['pending', 'confirmed', 'partial_paid'])
                    ->join('sales', 'reservations.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                    ->sum('reservations.remaining_amount');

                return (float) ($immediatePending + $creditsPending + $reservationsPending);
            }

    

        
        /**
         * Évolution des ventes dans le temps (pour graphiques temporels)
         * GET /api/statistics/sales/timeline
         */
        public function timeline(Request $request)
        {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'grouping' => 'required|in:day,week,month,year',
            ]);

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $grouping = $request->grouping;

            // Format de groupement SQL
            $dateFormat = match ($grouping) {
                'day' => "TO_CHAR(sale_date, 'YYYY-MM-DD')",
                'week' => "TO_CHAR(DATE_TRUNC('week', sale_date), 'YYYY-MM-DD')",
                'month' => "TO_CHAR(sale_date, 'YYYY-MM')",
                'year' => "TO_CHAR(sale_date, 'YYYY')",
            };

            $timeline = Sale::selectRaw("
                    {$dateFormat} as period,
                    COUNT(*) as sales_count,
                    SUM(total_amount) as revenue,
                    SUM(discount_amount) as discounts,
                    AVG(total_amount) as average_sale,
                    SUM(CASE WHEN sale_type = 'immediate' THEN total_amount ELSE 0 END) as immediate_revenue,
                    SUM(CASE WHEN sale_type = 'credit' THEN total_amount ELSE 0 END) as credit_revenue,
                    SUM(CASE WHEN sale_type = 'reservation' THEN total_amount ELSE 0 END) as reservation_revenue,
                    
                    -- Ventes immédiates payées (utilise sales.payment_status)
                    SUM(CASE WHEN sale_type = 'immediate' AND payment_status = 'paid' THEN total_amount ELSE 0 END) as immediate_paid
                ")
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->groupByRaw($dateFormat)
                ->orderByRaw($dateFormat)
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => $item->period,
                        'sales_count' => (int) $item->sales_count,
                        'revenue' => (float) $item->revenue,
                        'discounts' => (float) $item->discounts,
                        'average_sale' => $item->average_sale ? round((float) $item->average_sale, 2) : 0,
                        'immediate_revenue' => (float) $item->immediate_revenue,
                        'credit_revenue' => (float) $item->credit_revenue,
                        'reservation_revenue' => (float) $item->reservation_revenue,
                        'immediate_paid' => (float) $item->immediate_paid,
                    ];
                });

            // Ajouter les données de crédits et réservations par période
            $timeline = $this->enrichTimelineWithCreditAndReservationData($timeline, $startDate, $endDate, $grouping);

            return response()->json([
                'grouping' => $grouping,
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'data' => $timeline,
            ]);
        }

    /**
     * Enrichit les données timeline avec les statuts des crédits et réservations
     */
    private function enrichTimelineWithCreditAndReservationData($timeline, $startDate, $endDate, $grouping)
    {
        $dateFormat = match ($grouping) {
            'day' => "TO_CHAR(sales.sale_date, 'YYYY-MM-DD')",
            'week' => "TO_CHAR(DATE_TRUNC('week', sales.sale_date), 'YYYY-MM-DD')",
            'month' => "TO_CHAR(sales.sale_date, 'YYYY-MM')",
            'year' => "TO_CHAR(sales.sale_date, 'YYYY')",
        };

        // Données des crédits par période
        $creditData = Credit::selectRaw("
                {$dateFormat} as period,
                SUM(CASE WHEN credits.status = 'completed' THEN credits.total_amount ELSE 0 END) as credit_paid,
                SUM(CASE WHEN credits.status IN ('active', 'partial_paid', 'overdue') THEN credits.amount_due ELSE 0 END) as credit_pending
            ")
            ->join('sales', 'credits.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->groupByRaw($dateFormat)
            ->get()
            ->keyBy('period');

        // Données des réservations par période
        $reservationData = Reservation::selectRaw("
                {$dateFormat} as period,
                SUM(CASE WHEN reservations.status = 'completed' THEN reservations.total_amount ELSE 0 END) as reservation_paid,
                SUM(CASE WHEN reservations.status IN ('pending', 'confirmed', 'partial_paid') THEN reservations.remaining_amount ELSE 0 END) as reservation_pending
            ")
            ->join('sales', 'reservations.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->groupByRaw($dateFormat)
            ->get()
            ->keyBy('period');

        // Fusionner les données
        return $timeline->map(function ($item) use ($creditData, $reservationData) {
            $period = $item['period'];
            
            $credit = $creditData->get($period);
            $reservation = $reservationData->get($period);

            return array_merge($item, [
                'credit_paid' => $credit ? (float) $credit->credit_paid : 0,
                'credit_pending' => $credit ? (float) $credit->credit_pending : 0,
                'reservation_paid' => $reservation ? (float) $reservation->reservation_paid : 0,
                'reservation_pending' => $reservation ? (float) $reservation->reservation_pending : 0,
                
                // Total payé = immediate_paid + credit_paid + reservation_paid
                'total_paid' => ($item['immediate_paid'] ?? 0) + 
                            ($credit ? (float) $credit->credit_paid : 0) + 
                            ($reservation ? (float) $reservation->reservation_paid : 0),
            ]);
        });
    }
        

        /**
         * Top produits les plus vendus avec pagination
         * GET /api/statistics/sales/top-products
         */
        public function topProducts(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:5|max:100',
                'page' => 'nullable|integer|min:1',
                'order_by' => 'nullable|in:quantity,revenue',
            ]);

            $period = $this->getPeriod($request);
            $perPage = $request->input('per_page', 10);
            $orderBy = $request->input('order_by', 'revenue');

            $query = SaleItem::selectRaw('
                    product_variants.product_id,
                    products.name as product_name,
                    products.image_url,
                    products.base_price,
                    categories.name as category_name,
                    SUM(sale_items.quantity) as total_quantity,
                    SUM(sale_items.subtotal) as total_revenue,
                    COUNT(DISTINCT sale_items.sale_id) as sales_count,
                    AVG(sale_items.unit_price) as average_price
                ')
                ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                ->groupBy('product_variants.product_id', 'products.name', 'products.image_url', 'products.base_price', 'categories.name')
                ->orderByDesc($orderBy === 'quantity' ? 'total_quantity' : 'total_revenue');

            $paginator = $query->paginate($perPage);

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'order_by' => $orderBy,
                'data' => $paginator->map(function ($product) {
                    return [
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'image_url' => $product->image_url,
                        'base_price' => (float) $product->base_price,
                        'category_name' => $product->category_name,
                        'total_quantity' => (int) $product->total_quantity,
                        'total_revenue' => (float) $product->total_revenue,
                        'sales_count' => (int) $product->sales_count,
                        'average_price' => $product->average_price ? round((float) $product->average_price, 2) : 0,
                    ];
                }),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        }

        /**
         * Ventes par catégorie
         * GET /api/statistics/sales/by-category
         */
        public function byCategory(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $period = $this->getPeriod($request);

            $categories = SaleItem::selectRaw('
                    categories.id,
                    categories.name,
                    categories.image_url,
                    COUNT(DISTINCT sale_items.id) as items_sold,
                    SUM(sale_items.quantity) as total_quantity,
                    SUM(sale_items.subtotal) as total_revenue,
                    COUNT(DISTINCT sale_items.sale_id) as sales_count
                ')
                ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                ->groupBy('categories.id', 'categories.name', 'categories.image_url')
                ->orderByDesc('total_revenue')
                ->get();

            $totalRevenue = $categories->sum('total_revenue');

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'categories' => $categories->map(function ($category) use ($totalRevenue) {
                    $revenue = (float) $category->total_revenue;
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image_url' => $category->image_url,
                        'items_sold' => (int) $category->items_sold,
                        'total_quantity' => (int) $category->total_quantity,
                        'total_revenue' => $revenue,
                        'sales_count' => (int) $category->sales_count,
                        'percentage' => $totalRevenue > 0 ? round(($revenue / $totalRevenue) * 100, 2) : 0,
                    ];
                }),
                'summary' => [
                    'total_categories' => $categories->count(),
                    'total_revenue' => (float) $totalRevenue,
                ],
            ]);
        }

        /**
         * Ventes par vendeur
         * GET /api/statistics/sales/by-seller
         */
        public function bySeller(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $period = $this->getPeriod($request);

            $sellers = Sale::selectRaw("
                    users.id as seller_id,
                    users.name as seller_name,
                    COUNT(sales.id) as sales_count,
                    SUM(sales.total_amount) as total_revenue,
                    AVG(sales.total_amount) as average_sale,
                    SUM(sales.discount_amount) as total_discounts
                ")
                ->join('users', 'sales.user_id', '=', 'users.id')
                ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_revenue')
                ->get()
                ->map(function ($seller) {
                    return [
                        'seller_id' => $seller->seller_id,
                        'seller_name' => $seller->seller_name,
                        'sales_count' => (int) $seller->sales_count,
                        'total_revenue' => (float) $seller->total_revenue,
                        'average_sale' => $seller->average_sale ? round((float) $seller->average_sale, 2) : 0,
                        'total_discounts' => (float) $seller->total_discounts,
                    ];
                });

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'sellers' => $sellers,
                'summary' => [
                    'total_sellers' => $sellers->count(),
                    'total_revenue' => $sellers->sum('total_revenue'),
                    'total_sales' => $sellers->sum('sales_count'),
                ],
            ]);
        }

        /**
         * Ventes par méthode de paiement
         * GET /api/statistics/sales/by-payment-method
         */
        public function byPaymentMethod(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $period = $this->getPeriod($request);

            $paymentMethods = Sale::selectRaw('
                    payment_method,
                    COUNT(*) as sales_count,
                    SUM(total_amount) as total_amount
                ')
                ->whereBetween('sale_date', [$period['start'], $period['end']])
                ->whereNotNull('payment_method')
                ->groupBy('payment_method')
                ->get()
                ->map(function ($method) {
                    return [
                        'method' => $method->payment_method,
                        'sales_count' => (int) $method->sales_count,
                        'total_amount' => (float) $method->total_amount,
                    ];
                });

            $totalAmount = $paymentMethods->sum('total_amount');

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'payment_methods' => $paymentMethods->map(function ($method) use ($totalAmount) {
                    return [
                        'method' => $method['method'],
                        'sales_count' => $method['sales_count'],
                        'total_amount' => $method['total_amount'],
                        'percentage' => $totalAmount > 0 ? round(($method['total_amount'] / $totalAmount) * 100, 2) : 0,
                    ];
                }),
                'summary' => [
                    'total_amount' => $totalAmount,
                ],
            ]);
        }

        /**
         * Analyse des remises
         * GET /api/statistics/sales/discounts
         */
        public function discounts(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $period = $this->getPeriod($request);

            $discountStats = Sale::selectRaw('
                    COUNT(*) as total_sales,
                    SUM(CASE WHEN discount_amount > 0 THEN 1 ELSE 0 END) as sales_with_discount,
                    SUM(discount_amount) as total_discount,
                    AVG(CASE WHEN discount_amount > 0 THEN discount_amount END) as average_discount,
                    MAX(discount_amount) as max_discount,
                    SUM(subtotal) as total_subtotal,
                    SUM(total_amount) as total_final
                ')
                ->whereBetween('sale_date', [$period['start'], $period['end']])
                ->first();

            $discountReasons = Sale::selectRaw('
                    discount_reason,
                    COUNT(*) as count,
                    SUM(discount_amount) as total_discount,
                    AVG(discount_amount) as average_discount
                ')
                ->whereBetween('sale_date', [$period['start'], $period['end']])
                ->where('discount_amount', '>', 0)
                ->whereNotNull('discount_reason')
                ->groupBy('discount_reason')
                ->orderByDesc('total_discount')
                ->get()
                ->map(function ($reason) {
                    return [
                        'reason' => $reason->discount_reason,
                        'count' => (int) $reason->count,
                        'total_discount' => (float) $reason->total_discount,
                        'average_discount' => $reason->average_discount ? round((float) $reason->average_discount, 2) : 0,
                    ];
                });

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'summary' => [
                    'total_sales' => (int) $discountStats->total_sales,
                    'sales_with_discount' => (int) $discountStats->sales_with_discount,
                    'discount_rate' => $discountStats->total_sales > 0 
                        ? round(($discountStats->sales_with_discount / $discountStats->total_sales) * 100, 2) 
                        : 0,
                    'total_discount_given' => (float) $discountStats->total_discount,
                    'average_discount' => $discountStats->average_discount ? round((float) $discountStats->average_discount, 2) : 0,
                    'max_discount' => (float) $discountStats->max_discount,
                    'discount_impact' => $discountStats->total_subtotal > 0 
                        ? round(($discountStats->total_discount / $discountStats->total_subtotal) * 100, 2) 
                        : 0,
                ],
                'by_reason' => $discountReasons,
            ]);
        }
        /**
         * Statistiques des crédits
         * GET /api/statistics/sales/credits
         */
        public function credits(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $period = $this->getPeriod($request);

            $creditStats = Credit::selectRaw("
                    COUNT(*) as total_credits,
                    SUM(total_amount) as total_credit_amount,
                    SUM(amount_paid) as total_paid,
                    SUM(amount_due) as total_outstanding,
                    AVG(total_amount) as average_credit,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
                ")
                ->whereBetween('credit_date', [$period['start'], $period['end']])
                ->first();

            $creditsByCustomer = Credit::selectRaw('
                    customers.id,
                    customers.name,
                    customers.customer_number,
                    COUNT(credits.id) as credit_count,
                    SUM(credits.total_amount) as total_amount,
                    SUM(credits.amount_paid) as amount_paid,
                    SUM(credits.amount_due) as amount_due
                ')
                ->join('customers', 'credits.customer_id', '=', 'customers.id')
                ->whereBetween('credits.credit_date', [$period['start'], $period['end']])
                ->groupBy('customers.id', 'customers.name', 'customers.customer_number')
                ->orderByDesc('total_amount')
                ->limit(10)
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'customer_number' => $customer->customer_number,
                        'credit_count' => (int) $customer->credit_count,
                        'total_amount' => (float) $customer->total_amount,
                        'amount_paid' => (float) $customer->amount_paid,
                        'amount_due' => (float) $customer->amount_due,
                    ];
                });

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'summary' => [
                    'total_credits' => (int) $creditStats->total_credits,
                    'total_credit_amount' => (float) $creditStats->total_credit_amount,
                    'total_paid' => (float) $creditStats->total_paid,
                    'total_outstanding' => (float) $creditStats->total_outstanding,
                    'average_credit' => $creditStats->average_credit ? round((float) $creditStats->average_credit, 2) : 0,
                    'collection_rate' => $creditStats->total_credit_amount > 0 
                        ? round(($creditStats->total_paid / $creditStats->total_credit_amount) * 100, 2) 
                        : 0,
                    'active_count' => (int) $creditStats->active_count,
                    'completed_count' => (int) $creditStats->completed_count,
                    'overdue_count' => (int) $creditStats->overdue_count,
                ],
                'top_customers' => $creditsByCustomer,
            ]);
        }

        /**
         * Statistiques des réservations
         * GET /api/statistics/sales/reservations
         */
        public function reservations(Request $request)
        {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $period = $this->getPeriod($request);

            $reservationStats = Reservation::selectRaw("
                    COUNT(*) as total_reservations,
                    SUM(total_amount) as total_amount,
                    SUM(deposit_amount) as total_deposits,
                    SUM(remaining_amount) as total_remaining,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count,
                    SUM(CASE WHEN status IN ('pending', 'confirmed') THEN 1 ELSE 0 END) as active_count
                ")
                ->whereBetween('reservation_date', [$period['start'], $period['end']])
                ->first();

            return response()->json([
                'period' => [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ],
                'summary' => [
                    'total_reservations' => (int) $reservationStats->total_reservations,
                    'total_amount' => (float) $reservationStats->total_amount,
                    'total_deposits' => (float) $reservationStats->total_deposits,
                    'total_remaining' => (float) $reservationStats->total_remaining,
                    'completion_rate' => $reservationStats->total_reservations > 0 
                        ? round(($reservationStats->completed_count / $reservationStats->total_reservations) * 100, 2) 
                        : 0,
                    'cancellation_rate' => $reservationStats->total_reservations > 0 
                        ? round(($reservationStats->cancelled_count / $reservationStats->total_reservations) * 100, 2) 
                        : 0,
                    'active_count' => (int) $reservationStats->active_count,
                    'completed_count' => (int) $reservationStats->completed_count,
                    'cancelled_count' => (int) $reservationStats->cancelled_count,
                    'expired_count' => (int) $reservationStats->expired_count,
                ],
            ]);
        }

        /**
         * Helper: Détermine la période basée sur les paramètres
         */
        private function getPeriod(Request $request): array
        {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                return [
                    'start' => Carbon::parse($request->start_date)->startOfDay(),
                    'end' => Carbon::parse($request->end_date)->endOfDay(),
                    'label' => 'custom',
                ];
            }

            return match ($request->input('period', 'month')) {
                'today' => [
                    'start' => Carbon::today()->startOfDay(),
                    'end' => Carbon::today()->endOfDay(),
                    'label' => 'today',
                ],
                'week' => [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek(),
                    'label' => 'this_week',
                ],
                'year' => [
                    'start' => Carbon::now()->startOfYear(),
                    'end' => Carbon::now()->endOfYear(),
                    'label' => 'this_year',
                ],
                default => [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                    'label' => 'this_month',
                ],
            };
        }
    }