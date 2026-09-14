<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\AccountTransaction;
use App\Models\Credit;
use App\Models\PlannedExpense;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $query = Sale::betweenDates($period['start'], $period['end'])->where('status', 'CONFIRMED');

        // CA FACTURÉ (théorique)
        $totalInvoiced = $this->calculateInvoicedRevenue($period);

        // ✅ CA ENCAISSÉ (réel via AccountTransaction)
        $totalCollected = $this->calculateCollectedRevenue($period);

        // Calcul du total moyen (exclure les valeurs nulles et cancelled)
        $averageSaleValue = Sale::betweenDates($period['start'], $period['end'])
            ->where('payment_status', '!=', PaymentStatus::CANCELLED)
            ->avg('total_amount');

        // Calcul par type de vente
        $byType = [
            'immediate' => [
                'count' => (clone $query)->where('sale_type', 'immediate')->count(),
                'invoiced' => (float) (clone $query)->where('sale_type', 'immediate')->sum('total_amount'),
            ],
            'credit' => [
                'count' => (clone $query)->where('sale_type', 'credit')->count(),
                'invoiced' => (float) (clone $query)->where('sale_type', 'credit')->sum('total_amount'),
            ],
            'reservation' => [
                'count' => (clone $query)->where('sale_type', 'reservation')->count(),
                'invoiced' => (float) (clone $query)->where('sale_type', 'reservation')->sum('total_amount'),
            ],
        ];

        $byPaymentStatus = $this->calculatePaymentStatusByType($period);

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['label'],
            ],
            'kpis' => [
                'total_sales_count' => $query->count(),

                // ✅ DISTINCTION CLAIRE
                'total_invoiced' => (float) $totalInvoiced, // CA facturé
                'total_collected' => (float) $totalCollected, // CA encaissé
                'collection_rate' => $totalInvoiced > 0
                    ? round(($totalCollected / $totalInvoiced) * 100, 2)
                    : 0,

                'total_paid' => (float) $this->getTotalPaid($period),
                'total_pending' => (float) $this->getTotalPending($period),
                'average_sale_value' => $averageSaleValue ? round((float) $averageSaleValue, 2) : 0,
                'total_discount_given' => (float) $query->sum('discount_amount'),
            ],
            'by_type' => $byType,
            'by_payment_status' => $byPaymentStatus,
        ]);
    }

    private function calculateInvoicedRevenue(array $period): float
    {
        // 1. Ventes immédiates NON annulées
        $immediateSales = Sale::where('sale_type', 'immediate')
            ->where('status', 'CONFIRMED') // ✅ AJOUT
            ->whereBetween('sale_date', [$period['start'], $period['end']])
            ->sum('total_amount');

        // 2. Crédits NON annulés
        $creditSales = Credit::where('status', '!=', 'cancelled') // ✅ AJOUT
            ->whereBetween('credit_date', [$period['start'], $period['end']])
            ->sum('total_amount');

        // 3. Réservations NON annulées
        $reservationSales = Reservation::where('status', '!=', 'cancelled') // ✅ AJOUT
            ->whereBetween('reservation_date', [$period['start'], $period['end']])
            ->sum('total_amount');

        return (float) ($immediateSales + $creditSales + $reservationSales);
    }

    /**
     * Calcule les statuts de paiement en fonction du type de vente
     */
    private function calculatePaymentStatusByType(array $period): array
    {
        $getStat = function ($collection, $key) {
            $item = $collection->get($key);

            return [
                'count' => (int) ($item->count ?? 0),
                'amount' => (float) ($item->amount ?? 0),
            ];
        };

        // 1. VENTES IMMÉDIATES
        $immediateStats = Sale::selectRaw('
                payment_status,
                COUNT(*) as count,
                SUM(sales.total_amount) as amount
            ')
            ->where('sale_type', 'immediate')
            ->whereBetween('sale_date', [$period['start'], $period['end']])
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        // 2. CRÉDITS
        $creditStats = Credit::selectRaw('
            credits.status,  
            COUNT(*) as count,
            SUM(credits.total_amount) as amount
            ')
            ->join('sales', 'credits.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('credits.status')  // ✅ Préfixé
            ->get()
            ->keyBy('status');

        // 3. RÉSERVATIONS
        $reservationStats = Reservation::selectRaw('
                reservations.status,  
                COUNT(*) as count,
                SUM(reservations.total_amount) as amount
            ')
            ->join('sales', 'reservations.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('reservations.status')  // ✅ Préfixé
            ->get()
            ->keyBy('status');

        return [
            'immediate' => [
                'paid' => $getStat($immediateStats, 'paid'),
                'partial' => $getStat($immediateStats, 'partial'),
                'pending' => $getStat($immediateStats, 'pending'),
                'cancelled' => $getStat($immediateStats, 'cancelled'),
            ],
            'credit' => [
                'active' => $getStat($creditStats, 'active'),
                'partial_paid' => $getStat($creditStats, 'partial_paid'),
                'completed' => $getStat($creditStats, 'completed'),
                'overdue' => $getStat($creditStats, 'overdue'),
                'defaulted' => $getStat($creditStats, 'defaulted'),
                'recovered' => $getStat($creditStats, 'recovered'),
            ],
            'reservation' => [
                'pending' => $getStat($reservationStats, 'pending'),
                'confirmed' => $getStat($reservationStats, 'confirmed'),
                'partial_paid' => $getStat($reservationStats, 'partial_paid'),
                'completed' => $getStat($reservationStats, 'completed'),
                'expired' => $getStat($reservationStats, 'expired'),
                'cancelled' => $getStat($reservationStats, 'cancelled'),
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
            ->where('sales.payment_status', 'paid')
            ->whereBetween('sale_date', [$period['start'], $period['end']])
            ->sum('total_amount');

        // Crédits complétés (entièrement payés)
        $creditsPaid = Credit::where('credits.status', 'completed')
            ->join('sales', 'credits.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->sum('credits.total_amount');

        // Réservations complétées
        $reservationsPaid = Reservation::where('reservations.status', 'completed')
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
            ->whereIn('sales.payment_status', ['pending', 'partial'])
            ->whereBetween('sale_date', [$period['start'], $period['end']])
            ->sum('total_amount');

        // Crédits actifs (montant restant dû)
        $creditsPending = Credit::whereIn('credits.status', ['active', 'partial_paid', 'overdue'])
            ->join('sales', 'credits.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->sum('credits.amount_due');

        // Réservations actives (montant restant)
        $reservationsPending = Reservation::whereIn('reservations.status', ['pending', 'confirmed', 'partial_paid'])
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
            'day' => "TO_CHAR(sales.sale_date, 'YYYY-MM-DD')",
            'week' => "TO_CHAR(DATE_TRUNC('week', sales.sale_date), 'YYYY-MM-DD')",
            'month' => "TO_CHAR(sales.sale_date, 'YYYY-MM')",
            'year' => "TO_CHAR(sales.sale_date, 'YYYY')",
        };

        // 1. CA FACTURÉ par période (toutes ventes confirmées)
        $invoicedTimeline = Sale::selectRaw("
                {$dateFormat} as period,
                COUNT(*) as sales_count,
                SUM(total_amount) as revenue
            ")
            ->where('status', 'CONFIRMED')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupByRaw($dateFormat)
            ->orderByRaw($dateFormat)
            ->get()
            ->keyBy('period');

        // 2. CA ENCAISSÉ par période (via AccountTransaction)
        $transactionDateFormat = match ($grouping) {
            'day' => "TO_CHAR(at.transaction_date, 'YYYY-MM-DD')",
            'week' => "TO_CHAR(DATE_TRUNC('week', at.transaction_date), 'YYYY-MM-DD')",
            'month' => "TO_CHAR(at.transaction_date, 'YYYY-MM')",
            'year' => "TO_CHAR(at.transaction_date, 'YYYY')",
        };

        // CORRECTION : Appliquer manuellement la logique notCancelled avec les bons alias
        $collectedTimeline = AccountTransaction::selectRaw("
            {$transactionDateFormat} as period,
            SUM(at.amount) as total_paid
            ")
            ->from('account_transactions as at')
            ->whereNotNull('at.sale_id')
            ->whereNull('at.reversed_transaction_id') // Condition 1
            ->whereNotExists(function ($query) {
                // Condition 2 : pas de transaction qui annule celle-ci
                $query->select(DB::raw(1))
                    ->from('account_transactions as at2')
                    ->whereRaw('at2.reversed_transaction_id = at.id');
            })
            ->whereBetween('at.transaction_date', [$startDate, $endDate])
            ->groupByRaw($transactionDateFormat)
            ->orderByRaw($transactionDateFormat)
            ->get()
            ->keyBy('period');

        // 3. Fusionner les données
        $allPeriods = $invoicedTimeline->keys()
            ->merge($collectedTimeline->keys())
            ->unique()
            ->sort();

        $timeline = $allPeriods->map(function ($period) use ($invoicedTimeline, $collectedTimeline) {
            $invoiced = $invoicedTimeline->get($period);
            $collected = $collectedTimeline->get($period);

            return [
                'period' => $period,
                'sales_count' => (int) ($invoiced->sales_count ?? 0),
                'revenue' => (float) ($invoiced->revenue ?? 0), // ✅ Nom correct pour frontend
                'total_paid' => (float) ($collected->total_paid ?? 0), // ✅ Nom correct
            ];
        })->values();

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

        $sellers = Sale::selectRaw('
                users.id as seller_id,
                users.name as seller_name,
                COUNT(sales.id) as sales_count,
                SUM(sales.total_amount) as total_revenue,
                AVG(sales.total_amount) as average_sale,
                SUM(sales.discount_amount) as total_discounts
            ')
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
     *
     * Utilise AccountTransaction pour capturer TOUS les paiements:
     * - Ventes immédiates
     * - Paiements de crédits (installments)
     * - Finalisations de réservations
     *
     * La méthode de paiement vient de: account.accountType.display_name
     */
    public function byPaymentMethod(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $period = $this->getPeriod($request);

        // Correction de byPaymentMethod()
        $paymentTransactions = AccountTransaction::selectRaw('
        account_types.display_name as payment_method,
        account_types.code as payment_method_code,
        sales.sale_type,
        COUNT(account_transactions.id) as transaction_count,
        SUM(account_transactions.amount) as total_amount
        ')
            ->join('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->join('sales', 'account_transactions.sale_id', '=', 'sales.id')
            ->whereNotNull('account_transactions.sale_id')
            ->whereNull('account_transactions.reversed_transaction_id') // Condition 1
            ->whereNotExists(function ($query) {
                // Condition 2 avec le bon alias
                $query->select(DB::raw(1))
                    ->from('account_transactions as at2')
                    ->whereRaw('at2.reversed_transaction_id = account_transactions.id');
            })
            ->whereBetween('account_transactions.transaction_date', [$period['start'], $period['end']])
            ->groupBy('account_types.display_name', 'account_types.code', 'sales.sale_type')
            ->get();

        // Grouper par méthode de paiement
        $paymentMethods = $paymentTransactions->groupBy('payment_method')->map(function ($group, $method) {
            $methodCode = $group->first()->payment_method_code;

            return [
                'method' => $method,
                'method_code' => $methodCode,
                'transaction_count' => (int) $group->sum('transaction_count'),
                'total_amount' => (float) $group->sum('total_amount'),
                'by_sale_type' => [
                    'immediate' => (float) $group->where('sale_type', 'immediate')->sum('total_amount'),
                    'credit' => (float) $group->where('sale_type', 'credit')->sum('total_amount'),
                    'reservation' => (float) $group->where('sale_type', 'reservation')->sum('total_amount'),
                ],
            ];
        })->values();

        $totalAmount = $paymentMethods->sum('total_amount');

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
            ],
            'payment_methods' => $paymentMethods->map(function ($method) use ($totalAmount) {
                return [
                    'method' => $method['method'],
                    'method_code' => $method['method_code'],
                    'transaction_count' => $method['transaction_count'],
                    'total_amount' => $method['total_amount'],
                    'percentage' => $totalAmount > 0 ? round(($method['total_amount'] / $totalAmount) * 100, 2) : 0,
                    'by_sale_type' => $method['by_sale_type'],
                ];
            }),
            'summary' => [
                'total_amount' => (float) $totalAmount,
                'total_transactions' => (int) $paymentMethods->sum('transaction_count'),
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
     * Calcule le CA réellement encaissé (via AccountTransaction)
     */
    private function calculateCollectedRevenue(array $period): float
    {
        // Transactions liées aux ventes (argent entré)
        $collected = AccountTransaction::whereNotNull('sale_id')
            ->notCancelled() // ← Ça marche ici car pas de join
            ->whereBetween('transaction_date', [$period['start'], $period['end']])
            ->sum('amount');

        return (float) $collected;
    }
    // ==================== NOUVELLES MÉTHODES FINANCIÈRES ====================

    /**
     * Dashboard financier complet
     * GET /api/statistics/financial/dashboard
     */
    public function financialDashboard(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:week,month,year',
        ]);

        $period = $this->getPeriod($request);

        $revenue = $this->calculateRevenue($period);
        $costs = $this->calculateCosts($period);
        $procurement = $this->calculateProcurementCosts($period);
        $expenses = $this->calculateExpenses($period);
        $losses = $this->calculateLosses($period);
        $profits = $this->calculateProfits($period);

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['label'],
            ],
            'revenue' => $revenue,
            'costs' => $costs,
            'procurement' => $procurement, // ✅ Coûts d'approvisionnement
            'profits' => $profits,
            'expenses' => $expenses, // ✅ Dépenses opérationnelles (sans approvisionnements)
            'losses' => $losses,
            'margins' => $this->calculateMargins($period),
            'cancelled' => $this->calculateCancelled($period),

            // ✅ NOUVEAU : Vue d'ensemble des dépenses
            'total_expenses_breakdown' => [
                'procurement' => (float) $procurement['total'],
                'operational' => (float) $expenses['actual']['total'],
                'losses' => (float) $losses['summary']['total_losses'],
                'total' => (float) ($procurement['total'] + $expenses['actual']['total'] + $losses['summary']['total_losses']),
            ],
        ]);
    }

    /**
     * Vue d'ensemble des bénéfices
     * GET /api/statistics/financial/profits
     */
    public function profitsOverview(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:week,month,year',
        ]);

        $period = $this->getPeriod($request);
        $profits = $this->calculateProfits($period);

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['label'],
            ],
            'summary' => $profits['summary'],
            'by_type' => $profits['by_type'],
            'by_product' => $this->getProfitsByProduct($period),
            'by_category' => $this->getProfitsByCategory($period),
        ]);
    }

    /**
     * Vue d'ensemble des dépenses opérationnelles
     * GET /api/statistics/financial/expenses
     */
    public function expensesOverview(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:week,month,year',
        ]);

        $period = $this->getPeriod($request);
        $expenses = $this->calculateExpenses($period);

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['label'],
            ],
            'actual' => $expenses['actual'],
            'planned' => $expenses['planned'],
            'comparison' => $expenses['comparison'],
        ]);
    }

    /**
     * Vue d'ensemble des pertes de stock
     * GET /api/statistics/financial/losses
     */
    public function lossesOverview(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:week,month,year',
        ]);

        $period = $this->getPeriod($request);
        $losses = $this->calculateLosses($period);

        return response()->json([
            'period' => [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'label' => $period['label'],
            ],
            'summary' => $losses['summary'],
            'by_type' => $losses['by_type'],
            'by_product' => $losses['by_product'],
        ]);
    }

    /**
     * Graphique temporel des finances
     * GET /api/statistics/financial/timeline
     */
    public function financialTimeline(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'grouping' => 'required|in:day,week,month',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $grouping = $request->grouping;

        // AJOUTEZ le préfixe de table ICI
        $dateFormat = match ($grouping) {
            'day' => "TO_CHAR(sale_item_batches.created_at, 'YYYY-MM-DD')",
            'week' => "TO_CHAR(DATE_TRUNC('week', sale_item_batches.created_at), 'YYYY-MM-DD')",
            'month' => "TO_CHAR(sale_item_batches.created_at, 'YYYY-MM')",
        };

        $timeline = $this->getFinancialTimelineData($startDate, $endDate, $dateFormat);

        return response()->json([
            'grouping' => $grouping,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'data' => $timeline,
        ]);
    }
    // private function calculateCollectedRevenue(array $period): float
    // {
    //     // Transactions liées aux ventes (argent entré)
    //     $collected = AccountTransaction::whereNotNull('sale_id')
    //         ->whereNull('reversed_transaction_id') // Exclure les annulations
    //         ->whereBetween('transaction_date', [$period['start'], $period['end']])
    //         ->sum('amount');

    //     return (float) $collected;
    // }
    // ========== MÉTHODES PRIVÉES DE CALCUL FINANCIER ==========

    /**
     * Calcule les revenus réels encaissés
     */
    private function calculateRevenue(array $period): array
    {
        // Ventes immédiates payées
        $immediateRevenue = AccountTransaction::join('sales', 'account_transactions.sale_id', '=', 'sales.id')
            ->where('sales.sale_type', 'immediate')
            ->whereNull('account_transactions.reversed_transaction_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('account_transactions as at2')
                    ->whereRaw('at2.reversed_transaction_id = account_transactions.id');
            }) // ← condition 2 (équivalent de whereDoesntHave)
            ->whereBetween('account_transactions.transaction_date', [$period['start'], $period['end']])
            ->sum('account_transactions.amount');
        // Crédits: montants payés
        $creditRevenue = AccountTransaction::join('sales', 'account_transactions.sale_id', '=', 'sales.id')
            ->where('sales.sale_type', 'credit')
            ->whereNull('account_transactions.reversed_transaction_id') // ← avec alias complet
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('account_transactions as at2')
                    ->whereRaw('at2.reversed_transaction_id = account_transactions.id'); // ← alias correct
            })
            ->whereBetween('account_transactions.transaction_date', [$period['start'], $period['end']])
            ->sum('account_transactions.amount');
        // Réservations: dépôts confirmés
        $reservationConfirmedDeposits = AccountTransaction::query()
            ->join('sales', 'account_transactions.sale_id', '=', 'sales.id')
            ->join('reservations', 'reservations.sale_id', '=', 'sales.id')
            ->where('sales.sale_type', 'reservation')
            ->whereNull('account_transactions.reversed_transaction_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('account_transactions as at2')
                    ->whereRaw('at2.reversed_transaction_id = account_transactions.id');
            }) // ← condition 2 (équivalent de whereDoesntHave)
            ->where('reservations.status', 'confirmed')
            ->whereBetween('account_transactions.transaction_date', [$period['start'], $period['end']])
            ->sum('account_transactions.amount');

        $reservationCompletedTotal = Reservation::join('sales', 'reservations.sale_id', '=', 'sales.id')
            ->where('reservations.status', 'completed')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->sum('reservations.total_amount');

        $totalRevenue = $immediateRevenue + $creditRevenue + $reservationConfirmedDeposits + $reservationCompletedTotal;

        return [
            'total' => (float) $totalRevenue,
            'immediate' => (float) $immediateRevenue,
            'credit' => (float) $creditRevenue,
            'reservation' => (float) ($reservationConfirmedDeposits + $reservationCompletedTotal),
            'breakdown' => [
                'reservation_deposits' => (float) $reservationConfirmedDeposits,
                'reservation_completed' => (float) $reservationCompletedTotal,
            ],
        ];
    }

    /**
     * Calcule les coûts des produits vendus (COGS)
     */
    private function calculateCosts(array $period): array
    {
        // Coûts des produits vendus (status = 'sold')
        $soldCosts = DB::table('sale_item_batches')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereIn('sale_item_batches.status', ['sold', 'reserved'])
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->selectRaw('SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as total_cost')
            ->first();

        // Coûts par type de vente
        $costsByType = [
            'immediate' => $this->getCostsByType($period, 'immediate'),
            'credit' => $this->getCostsByType($period, 'credit'),
            'reservation' => $this->getCostsByType($period, 'reservation'),
        ];

        return [
            'total' => (float) ($soldCosts->total_cost ?? 0),
            'by_type' => $costsByType,
        ];
    }

    /**
     * Calcule les bénéfices (revenus - coûts)
     */
    private function calculateProfits(array $period): array
    {
        $revenue = $this->calculateRevenue($period);
        $costs = $this->calculateCosts($period);
        $expenses = $this->calculateExpenses($period); // ✅ Sans approvisionnements
        $losses = $this->calculateLosses($period);

        // Bénéfice brut = Revenus - Coûts des produits vendus (COGS)
        $grossProfit = $revenue['total'] - $costs['total'];

        // Bénéfice net = Bénéfice brut - Dépenses opérationnelles - Pertes
        // ✅ Les approvisionnements sont déjà dans $costs via stock_batches.total_unit_cost
        $netProfit = $grossProfit - $expenses['actual']['total'] - $losses['summary']['total_value'];

        // Par type de vente
        $profitsByType = [
            'immediate' => [
                'revenue' => $revenue['immediate'],
                'costs' => $costs['by_type']['immediate'],
                'profit' => $revenue['immediate'] - $costs['by_type']['immediate'],
                'margin' => $revenue['immediate'] > 0
                    ? round((($revenue['immediate'] - $costs['by_type']['immediate']) / $revenue['immediate']) * 100, 2)
                    : 0,
            ],
            'credit' => [
                'revenue' => $revenue['credit'],
                'costs' => $costs['by_type']['credit'],
                'profit' => $revenue['credit'] - $costs['by_type']['credit'],
                'margin' => $revenue['credit'] > 0
                    ? round((($revenue['credit'] - $costs['by_type']['credit']) / $revenue['credit']) * 100, 2)
                    : 0,
            ],
            'reservation' => [
                'revenue' => $revenue['reservation'],
                'costs' => $costs['by_type']['reservation'],
                'profit' => $revenue['reservation'] - $costs['by_type']['reservation'],
                'margin' => $revenue['reservation'] > 0
                    ? round((($revenue['reservation'] - $costs['by_type']['reservation']) / $revenue['reservation']) * 100, 2)
                    : 0,
            ],
        ];

        return [
            'summary' => [
                'gross_profit' => (float) $grossProfit,
                'net_profit' => (float) $netProfit,
                'gross_margin' => $revenue['total'] > 0 ? round(($grossProfit / $revenue['total']) * 100, 2) : 0,
                'net_margin' => $revenue['total'] > 0 ? round(($netProfit / $revenue['total']) * 100, 2) : 0,
            ],
            'by_type' => $profitsByType,
        ];
    }

    /**
     * Calcule les coûts d'approvisionnement (achats de stock)
     */
    private function calculateProcurementCosts(array $period): array
    {
        // Coûts des approvisionnements via account_transactions
        $procurementTransactions = AccountTransaction::whereNotNull('stock_receipt_id')
            ->notCancelled()
            ->whereBetween('transaction_date', [$period['start'], $period['end']])
            ->selectRaw('
                    SUM(amount) as total_cost,
                    COUNT(*) as transaction_count
                ')
            ->first();

        // Détail par réception de stock
        $byReceipt = AccountTransaction::whereNotNull('stock_receipt_id')
            ->notCancelled()
            ->whereBetween('transaction_date', [$period['start'], $period['end']])
            ->selectRaw('
                    stock_receipt_id,
                    SUM(amount) as total_amount
                ')
            ->groupBy('stock_receipt_id')
            ->with('stockReceipt')
            ->get()
            ->map(function ($item) {
                return [
                    'receipt_id' => $item->stock_receipt_id,
                    'receipt_number' => $item->stockReceipt->receipt_number ?? 'N/A',
                    'amount' => (float) $item->total_amount,
                    'date' => $item->stockReceipt->received_date ?? null,
                ];
            });

        return [
            'total' => (float) ($procurementTransactions->total_cost ?? 0),
            'transaction_count' => (int) ($procurementTransactions->transaction_count ?? 0),
            'by_receipt' => $byReceipt,
        ];
    }

    /**
     * Calcule les dépenses opérationnelles
     */
    /**
     * Calcule les dépenses opérationnelles (HORS approvisionnements)
     */
    private function calculateExpenses(array $period): array
    {
        // ✅ DÉPENSES RÉELLES : EXCLURE les approvisionnements (stock_receipt_id)
        $actualExpenses = AccountTransaction::whereNotNull('expense_category_id')
            ->notCancelled()
            ->whereNull('stock_receipt_id')
            ->whereBetween('transaction_date', [$period['start'], $period['end']])
            ->selectRaw('
                expense_category_id,
                SUM(amount) as total
            ')
            ->groupBy('expense_category_id')
            ->with('expenseCategory')
            ->get();

        $actualByCategory = $actualExpenses->map(function ($expense) {
            return [
                'category_id' => $expense->expense_category_id,
                'category_name' => $expense->expenseCategory->name ?? 'Non catégorisé',
                'amount' => (float) $expense->total,
            ];
        });

        // ✅ DÉPENSES PLANIFIÉES : Calculer le nombre de mois COMPLETS
        $plannedExpenses = PlannedExpense::active()->get();

        // Nombre de mois complets dans la période
        $startMonth = $period['start']->copy()->startOfMonth();
        $endMonth = $period['end']->copy()->startOfMonth();
        $numberOfMonths = $startMonth->diffInMonths($endMonth) + 1;

        $plannedTotal = $plannedExpenses->sum(function ($planned) use ($numberOfMonths) {
            // ✅ Multiplier par le nombre de mois sans division
            if ($planned->frequency === 'monthly') {
                return $planned->estimated_amount * $numberOfMonths;
            }

            // Pour les autres fréquences, ajuster selon besoin
            return $planned->estimated_amount * $numberOfMonths; // ou votre logique
        });

        $plannedByCategory = $plannedExpenses->groupBy('expense_category_id')->map(function ($group, $categoryId) use ($numberOfMonths) {
            $category = $group->first()->expenseCategory;
            $total = $group->sum(function ($planned) use ($numberOfMonths) {
                if ($planned->frequency === 'monthly') {
                    return $planned->estimated_amount * $numberOfMonths;
                }

                return $planned->estimated_amount * $numberOfMonths;
            });

            return [
                'category_id' => $categoryId,
                'category_name' => $category->name ?? 'Non catégorisé',
                'amount' => (float) $total,
            ];
        })->values();

        $actualTotal = $actualByCategory->sum('amount');

        return [
            'actual' => [
                'total' => (float) $actualTotal,
                'by_category' => $actualByCategory,
            ],
            'planned' => [
                'total' => (float) $plannedTotal,
                'by_category' => $plannedByCategory,
            ],
            'comparison' => [
                'difference' => (float) ($actualTotal - $plannedTotal),
                'variance_percent' => $plannedTotal > 0
                    ? round((($actualTotal - $plannedTotal) / $plannedTotal) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Calcule les pertes de stock valorisées
     */
    private function calculateLosses(array $period): array
    {
        // Récupérer les pertes avec valorisation au prix de vente
        $losses = StockMovement::where('movement_type', StockMovement::TYPE_LOSS)
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->with(['variant.product'])
            ->get();

        $totalValue = 0;
        $byType = [];
        $byProduct = [];

        foreach ($losses as $loss) {
            $basePrice = $loss->variant->product->base_price ?? 0;
            $lossValue = $loss->quantity * $basePrice;
            $totalValue += $lossValue;

            // Par type de perte
            $lossType = $loss->loss_type ?? 'other';
            if (! isset($byType[$lossType])) {
                $byType[$lossType] = [
                    'type' => $lossType,
                    'quantity' => 0,
                    'value' => 0,
                ];
            }
            $byType[$lossType]['quantity'] += $loss->quantity;
            $byType[$lossType]['value'] += $lossValue;

            // Par produit
            $productId = $loss->variant->product_id;
            $productName = $loss->variant->product->name ?? 'Inconnu';

            if (! isset($byProduct[$productId])) {
                $byProduct[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'quantity' => 0,
                    'value' => 0,
                ];
            }
            $byProduct[$productId]['quantity'] += $loss->quantity;
            $byProduct[$productId]['value'] += $lossValue;
        }

        // Crédits defaulted considérés comme pertes
        $defaultedCredits = Credit::query()
            ->where('credits.status', 'defaulted')
            ->join('sales', 'credits.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->sum('credits.amount_due');

        return [
            'summary' => [
                'total_value' => (float) $totalValue,
                'total_quantity' => (int) $losses->sum('quantity'),
                'defaulted_credits' => (float) $defaultedCredits,
                'total_losses' => (float) ($totalValue + $defaultedCredits),
            ],
            'by_type' => array_values($byType),
            'by_product' => array_values($byProduct),
        ];
    }

    /**
     * Calcule les marges
     */
    private function calculateMargins(array $period): array
    {
        $revenue = $this->calculateRevenue($period);
        $costs = $this->calculateCosts($period);
        $expenses = $this->calculateExpenses($period);

        $grossMargin = $revenue['total'] - $costs['total'];
        $netMargin = $grossMargin - $expenses['actual']['total'];

        return [
            'gross_margin' => [
                'amount' => (float) $grossMargin,
                'percentage' => $revenue['total'] > 0 ? round(($grossMargin / $revenue['total']) * 100, 2) : 0,
            ],
            'net_margin' => [
                'amount' => (float) $netMargin,
                'percentage' => $revenue['total'] > 0 ? round(($netMargin / $revenue['total']) * 100, 2) : 0,
            ],
        ];
    }

    private function calculateCancelled(array $period): array
    {
        // SaleItemBatch annulés
        $cancelledBatches = SaleItemBatch::selectRaw('
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue_lost
            ')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_item_batches.status', 'cancelled')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->first();

        // Réservations annulées avec analyse des transactions
        $cancelledReservations = DB::table('reservations')
            ->selectRaw('
                reservations.id,
                reservations.deposit_amount,
                sales.id as sale_id,
                COUNT(account_transactions.id) as transaction_count,
                SUM(CASE WHEN account_transactions.reversed_transaction_id IS NOT NULL THEN 1 ELSE 0 END) as reversal_count,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM account_transactions at2 
                    WHERE at2.reversed_transaction_id = account_transactions.id
                ) THEN 1 ELSE 0 END) as cancelled_count
            ')
            ->join('sales', 'reservations.sale_id', '=', 'sales.id')
            ->leftJoin('account_transactions', 'sales.id', '=', 'account_transactions.sale_id')
            ->where('reservations.status', 'cancelled')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('reservations.id', 'reservations.deposit_amount', 'sales.id')
            ->get();

        $reservationRefunds = 0;
        $reservationKept = 0;

        foreach ($cancelledReservations as $reservation) {
            // Si au moins une transaction a été annulée OU est une annulation
            if ($reservation->reversal_count > 0 || $reservation->cancelled_count > 0) {
                $reservationRefunds += $reservation->deposit_amount;
            } else {
                $reservationKept += $reservation->deposit_amount;
            }
        }

        return [
            'product_sales' => (float) ($cancelledBatches->revenue_lost ?? 0),
            'reservations' => [
                'refunded' => (float) $reservationRefunds,
                'kept' => (float) $reservationKept,
                'total' => (float) ($reservationRefunds + $reservationKept),
            ],
            'total_cancelled_value' => (float) (($cancelledBatches->revenue_lost ?? 0) + $reservationRefunds + $reservationKept),
        ];
    }

    // ========== MÉTHODES UTILITAIRES FINANCIÈRES ==========

    /**
     * Coûts par type de vente
     */
    private function getCostsByType(array $period, string $saleType): float
    {
        $cost = DB::table('sale_item_batches')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_item_batches.status', 'sold')
            ->where('sales.sale_type', $saleType)
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->selectRaw('SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as total_cost')
            ->first();

        return (float) ($cost->total_cost ?? 0);
    }

    /**
     * Bénéfices par produit
     */
    private function getProfitsByProduct(array $period): array
    {
        return SaleItemBatch::selectRaw('
                products.id as product_id,
                products.name as product_name,
                SUM(sale_item_batches.quantity) as quantity_sold,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as costs,
                SUM(sale_item_batches.quantity * ((sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale) - stock_batches.total_unit_cost)) as profit
            ')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('sale_item_batches.status', 'sold')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('profit')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $revenue = (float) $item->revenue;
                $costs = (float) $item->costs;
                $profit = (float) $item->profit;

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity_sold' => (int) $item->quantity_sold,
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'profit' => $profit,
                    'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Bénéfices par catégorie
     */
    private function getProfitsByCategory(array $period): array
    {
        return SaleItemBatch::selectRaw('
                categories.id as category_id,
                categories.name as category_name,
                SUM(sale_item_batches.quantity) as quantity_sold,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as costs,
                SUM(sale_item_batches.quantity * ((sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale) - stock_batches.total_unit_cost)) as profit
            ')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sale_item_batches.status', 'sold')
            ->whereBetween('sales.sale_date', [$period['start'], $period['end']])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('profit')
            ->get()
            ->map(function ($item) {
                $revenue = (float) $item->revenue;
                $costs = (float) $item->costs;
                $profit = (float) $item->profit;

                return [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category_name,
                    'quantity_sold' => (int) $item->quantity_sold,
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'profit' => $profit,
                    'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Données pour graphique temporel financier
     */
    private function getFinancialTimelineData($startDate, $endDate, $dateFormat): array
    {
        // Revenus par période
        $revenueData = SaleItemBatch::selectRaw("
                {$dateFormat} as period,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as costs
            ")
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_item_batches.status', 'sold')
            ->whereBetween('sale_item_batches.created_at', [$startDate, $endDate])
            ->groupByRaw($dateFormat)
            ->orderByRaw($dateFormat)
            ->get()
            ->keyBy('period');

        // Pour les dépenses aussi, préfixez avec account_transactions
        $expensesDateFormat = str_replace('sale_item_batches.created_at', 'account_transactions.transaction_date', $dateFormat);

        $expensesData = AccountTransaction::selectRaw("
                {$expensesDateFormat} as period,
                SUM(amount) as expenses
            ")
            ->whereNotNull('expense_category_id')
            ->notCancelledWithAlias('account_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupByRaw($expensesDateFormat)
            ->orderByRaw($expensesDateFormat)
            ->get()
            ->keyBy('period');

        // Fusionner les données
        $allPeriods = $revenueData->keys()->merge($expensesData->keys())->unique()->sort();

        return $allPeriods->map(function ($period) use ($revenueData, $expensesData) {
            $revenue = (float) ($revenueData->get($period)->revenue ?? 0);
            $costs = (float) ($revenueData->get($period)->costs ?? 0);
            $expenses = (float) ($expensesData->get($period)->expenses ?? 0);

            $grossProfit = $revenue - $costs;
            $netProfit = $grossProfit - $expenses;

            return [
                'period' => $period,
                'revenue' => $revenue,
                'costs' => $costs,
                'expenses' => $expenses,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'gross_margin' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0,
                'net_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Convertit une dépense planifiée en équivalent pour la période
     */
    private function convertToEquivalent(float $amount, string $frequency, int $periodDays): float
    {
        return match ($frequency) {
            'daily' => $amount * $periodDays,
            'weekly' => $amount * ($periodDays / 7),
            'monthly' => $amount * ($periodDays / 30),
            'yearly' => $amount * ($periodDays / 365),
            default => 0,
        };
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
