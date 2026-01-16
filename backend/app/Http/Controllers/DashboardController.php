<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Credit;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\AccountTransaction;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Dashboard principal avec date dynamique
     * GET /api/dashboard?date=2026-01-11&period=7days
     */
    public function index(Request $request)
    {
        // Date sélectionnée (par défaut aujourd'hui en timezone Madagascar)
        $selectedDate = $request->input('date') 
            ? Carbon::parse($request->input('date'), 'Indian/Antananarivo')
            : Carbon::now('Indian/Antananarivo');
        
        $period = $request->input('period', '7days'); // 7days, 1month, 2months, 3months

        // Date de la veille
        $previousDate = $selectedDate->copy()->subDay();

        return response()->json([
            'date' => $selectedDate->format('Y-m-d'),
            'summary' => [
                'ca' => $this->getCAStats($selectedDate, $previousDate),
                'immediate_sales' => $this->getImmediateSalesStats($selectedDate, $previousDate),
                'new_credits' => $this->getNewCreditsStats($selectedDate, $previousDate),
                'credit_payments' => $this->getCreditPaymentsStats($selectedDate, $previousDate),
                'new_reservations' => $this->getNewReservationsStats($selectedDate, $previousDate),
                'expenses' => $this->getExpensesStats($selectedDate, $previousDate),
                'customers' => $this->getCustomersStats($selectedDate, $previousDate),
                'stock_value' => $this->getStockValue()
            ],
            'trends' => $this->getTrends($selectedDate, $period),
            'top_products' => $this->getTopProducts($selectedDate),
            'navigation' => [
                'previous_date' => $previousDate->format('Y-m-d'),
                'next_date' => $selectedDate->copy()->addDay()->format('Y-m-d'),
                'can_go_forward' => $selectedDate->lt(Carbon::now('Indian/Antananarivo'))
            ]
        ]);
    }

    /**
     * Chiffre d'affaires du jour
     */
    private function getCAStats(Carbon $date, Carbon $previousDate): array
    {
        // CA Possible (tout ce qui est vendu aujourd'hui)
        $caPossible = Sale::whereDate('sale_date', $date)
            ->whereIn('payment_status', ['paid', 'partial', 'pending'])
            ->sum('total_amount');

        $caPossibleYesterday = Sale::whereDate('sale_date', $previousDate)
            ->whereIn('payment_status', ['paid', 'partial', 'pending'])
            ->sum('total_amount');

        // CA Encaissé (argent réellement reçu aujourd'hui)
        $caEncaisse = AccountTransaction::whereDate('transaction_date', $date)
            ->whereHas('transactionType', function($q) {
                $q->where('category', 'income');
            })
            ->sum('amount');

        $caEncaisseYesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereHas('transactionType', function($q) {
                $q->where('category', 'income');
            })
            ->sum('amount');

        return [
            'possible' => [
                'value' => (float) $caPossible,
                'vs_yesterday' => $this->calculatePercentage($caPossible, $caPossibleYesterday)
            ],
            'encaisse' => [
                'value' => (float) $caEncaisse,
                'vs_yesterday' => $this->calculatePercentage($caEncaisse, $caEncaisseYesterday)
            ]
        ];
    }

    /**
     * Ventes immédiates du jour
     */
    private function getImmediateSalesStats(Carbon $date, Carbon $previousDate): array
    {
        $today = Sale::whereDate('sale_date', $date)
            ->where('sale_type', 'immediate')
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as value')
            ->first();

        $yesterday = Sale::whereDate('sale_date', $previousDate)
            ->where('sale_type', 'immediate')
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as value')
            ->first();

        return [
            'count' => (int) ($today->count ?? 0),
            'value' => (float) ($today->value ?? 0),
            'vs_yesterday' => [
                'count' => $this->calculatePercentage($today->count ?? 0, $yesterday->count ?? 0),
                'value' => $this->calculatePercentage($today->value ?? 0, $yesterday->value ?? 0)
            ]
        ];
    }

    /**
     * Nouveaux crédits créés aujourd'hui
     */
    private function getNewCreditsStats(Carbon $date, Carbon $previousDate): array
    {
        $today = Credit::whereDate('credit_date', $date)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as value')
            ->first();

        $yesterday = Credit::whereDate('credit_date', $previousDate)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as value')
            ->first();

        return [
            'count' => (int) ($today->count ?? 0),
            'value' => (float) ($today->value ?? 0),
            'vs_yesterday' => [
                'count' => $this->calculatePercentage($today->count ?? 0, $yesterday->count ?? 0),
                'value' => $this->calculatePercentage($today->value ?? 0, $yesterday->value ?? 0)
            ]
        ];
    }

    /**
     * Paiements de crédits reçus aujourd'hui (échéances)
     */
    private function getCreditPaymentsStats(Carbon $date, Carbon $previousDate): array
    {
        // Transactions de type CREDIT_PAYMENT
        $today = AccountTransaction::whereDate('transaction_date', $date)
            ->whereHas('transactionType', function($q) {
                $q->where('code', 'CREDIT_PAYMENT');
            })
            ->selectRaw('COUNT(*) as count, SUM(amount) as value')
            ->first();

        $yesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereHas('transactionType', function($q) {
                $q->where('code', 'CREDIT_PAYMENT');
            })
            ->selectRaw('COUNT(*) as count, SUM(amount) as value')
            ->first();

        return [
            'count' => (int) ($today->count ?? 0),
            'value' => (float) ($today->value ?? 0),
            'vs_yesterday' => [
                'count' => $this->calculatePercentage($today->count ?? 0, $yesterday->count ?? 0),
                'value' => $this->calculatePercentage($today->value ?? 0, $yesterday->value ?? 0)
            ]
        ];
    }

    /**
     * Nouvelles réservations créées aujourd'hui
     */
    private function getNewReservationsStats(Carbon $date, Carbon $previousDate): array
    {
        $today = Reservation::whereDate('reservation_date', $date)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as value')
            ->first();

        $yesterday = Reservation::whereDate('reservation_date', $previousDate)
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as value')
            ->first();

        return [
            'count' => (int) ($today->count ?? 0),
            'value' => (float) ($today->value ?? 0),
            'vs_yesterday' => [
                'count' => $this->calculatePercentage($today->count ?? 0, $yesterday->count ?? 0),
                'value' => $this->calculatePercentage($today->value ?? 0, $yesterday->value ?? 0)
            ]
        ];
    }

    /**
     * Dépenses du jour (tous types confondus)
     * Inclut: EXPENSE, REFUND + toutes les dépenses avec expense_category_id
     */
    private function getExpensesStats(Carbon $date, Carbon $previousDate): array
    {
        // Toutes transactions de catégorie expense + refund
        // ou ayant un expense_category_id (paiements fournisseurs, transitaires, dépenses opérationnelles)
        $today = AccountTransaction::whereDate('transaction_date', $date)
            ->where(function($q) {
                $q->whereHas('transactionType', function($q2) {
                    $q2->whereIn('category', ['expense']);
                })
                ->orWhereNotNull('expense_category_id');
            })
            ->sum('amount');

        $yesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->where(function($q) {
                $q->whereHas('transactionType', function($q2) {
                    $q2->whereIn('category', ['expense']);
                })
                ->orWhereNotNull('expense_category_id');
            })
            ->sum('amount');

        return [
            'value' => (float) $today,
            'vs_yesterday' => $this->calculatePercentage($today, $yesterday),
            'breakdown' => $this->getExpensesBreakdown($date)
        ];
    }

    /**
     * Détail des dépenses par catégorie pour aujourd'hui
     */
    private function getExpensesBreakdown(Carbon $date): array
    {
        return AccountTransaction::selectRaw('
                expense_categories.name as category,
                expense_categories.icon,
                SUM(account_transactions.amount) as total
            ')
            ->join('expense_categories', 'account_transactions.expense_category_id', '=', 'expense_categories.id')
            ->whereDate('account_transactions.transaction_date', $date)
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.icon')
            ->orderByDesc('total')
            ->get()
            ->map(function($item) {
                return [
                    'category' => $item->category,
                    'icon' => $item->icon,
                    'total' => (float) $item->total
                ];
            })
            ->toArray();
    }

    /**
     * Statistiques clients du jour
     */
    private function getCustomersStats(Carbon $date, Carbon $previousDate): array
    {
        // Nouveaux clients créés
        $newToday = Customer::whereDate('created_at', $date)->count();
        $newYesterday = Customer::whereDate('created_at', $previousDate)->count();

        // Clients revenus (ayant fait une transaction aujourd'hui mais pas leur première)
        $returningToday = Sale::whereDate('sale_date', $date)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count(DB::raw('DISTINCT customer_id'));

        $returningYesterday = Sale::whereDate('sale_date', $previousDate)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count(DB::raw('DISTINCT customer_id'));

        return [
            'new_customers' => [
                'count' => $newToday,
                'vs_yesterday' => $this->calculatePercentage($newToday, $newYesterday)
            ],
            'returning_customers' => [
                'count' => $returningToday,
                'vs_yesterday' => $this->calculatePercentage($returningToday, $returningYesterday)
            ]
        ];
    }

    /**
     * Valeur totale du stock actuel (base_price × stock_quantity)
     */
    private function getStockValue(): array
    {
        $value = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
            ->selectRaw('SUM(products.base_price * product_variants.stock_quantity) as total')
            ->where('product_variants.is_active', true)
            ->value('total');

        return [
            'value' => (float) ($value ?? 0)
        ];
    }

    /**
     * Tendances financières sur période
     */
    private function getTrends(Carbon $selectedDate, string $period): array
    {
        $periodData = $this->getPeriodRange($selectedDate, $period);
        $startDate = $periodData['start'];
        $endDate = $periodData['end'];
        $groupBy = $periodData['group_by'];

        if ($groupBy === 'day') {
            // Données par jour
            $data = Sale::selectRaw("
                DATE(sale_date) as date,
                SUM(CASE WHEN sale_type = 'immediate' THEN total_amount ELSE 0 END) as immediate_sales,
                SUM(CASE WHEN sale_type = 'credit' THEN total_amount ELSE 0 END) as credits,
                SUM(CASE WHEN sale_type = 'reservation' THEN total_amount ELSE 0 END) as reservations,
                SUM(total_amount) as total
            ")
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'immediate_sales' => (float) $item->immediate_sales,
                    'credits' => (float) $item->credits,
                    'reservations' => (float) $item->reservations,
                    'total' => (float) $item->total
                ];
            });
        } else {
            // Données par semaine
            $data = Sale::selectRaw("
                DATE_TRUNC('week', sale_date) as week,
                SUM(CASE WHEN sale_type = 'immediate' THEN total_amount ELSE 0 END) as immediate_sales,
                SUM(CASE WHEN sale_type = 'credit' THEN total_amount ELSE 0 END) as credits,
                SUM(CASE WHEN sale_type = 'reservation' THEN total_amount ELSE 0 END) as reservations,
                SUM(total_amount) as total
            ")
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->map(function($item) {
                return [
                    'week' => Carbon::parse($item->week)->format('Y-m-d'),
                    'immediate_sales' => (float) $item->immediate_sales,
                    'credits' => (float) $item->credits,
                    'reservations' => (float) $item->reservations,
                    'total' => (float) $item->total
                ];
            });
        }

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'group_by' => $groupBy,
            'data' => $data
        ];
    }

    /**
     * Top 5 produits les plus vendus aujourd'hui (par valeur)
     */
    private function getTopProducts(Carbon $date): array
    {
        return SaleItem::selectRaw('
                product_variants.id as variant_id,
                products.name as product_name,
                products.image_url,
                SUM(sale_items.quantity) as quantity_sold,
                SUM(sale_items.subtotal) as total_value
            ')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->whereDate('sales.sale_date', $date)
            ->groupBy('product_variants.id', 'products.name', 'products.image_url')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->product_name,
                    'image_url' => $item->image_url,
                    'quantity_sold' => (int) $item->quantity_sold,
                    'total_value' => (float) $item->total_value
                ];
            })
            ->toArray();
    }

    /**
     * Calcule le pourcentage de variation
     */
    private function calculatePercentage($current, $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? '+100.0%' : '0.0%';
        }
        
        $percentage = (($current - $previous) / $previous) * 100;
        $sign = $percentage >= 0 ? '+' : '';
        
        return $sign . number_format($percentage, 1) . '%';
    }

    /**
     * Détermine la plage de dates et le groupement selon la période
     */
    private function getPeriodRange(Carbon $selectedDate, string $period): array
    {
        switch ($period) {
            case '7days':
                return [
                    'start' => $selectedDate->copy()->subDays(6),
                    'end' => $selectedDate,
                    'group_by' => 'day'
                ];
            case '1month':
                return [
                    'start' => $selectedDate->copy()->subMonth()->addDay(),
                    'end' => $selectedDate,
                    'group_by' => 'day'
                ];
            case '2months':
                return [
                    'start' => $selectedDate->copy()->subMonths(2)->addDay(),
                    'end' => $selectedDate,
                    'group_by' => 'week'
                ];
            case '3months':
                return [
                    'start' => $selectedDate->copy()->subMonths(3)->addDay(),
                    'end' => $selectedDate,
                    'group_by' => 'week'
                ];
            default:
                return [
                    'start' => $selectedDate->copy()->subDays(6),
                    'end' => $selectedDate,
                    'group_by' => 'day'
                ];
        }
    }
}