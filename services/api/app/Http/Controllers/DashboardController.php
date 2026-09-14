<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard principal avec date dynamique
     * GET /api/dashboard?date=2026-01-11&period=7days
     */
    public function index(Request $request)
    {
        $selectedDate = $request->input('date')
            ? Carbon::parse($request->input('date'), 'Indian/Antananarivo')
            : Carbon::now('Indian/Antananarivo');

        $period = $request->input('period', '7days');
        $previousDate = $selectedDate->copy()->subDay();

        return response()->json([
            'date' => $selectedDate->format('Y-m-d'),
            'summary' => [
                'ca' => $this->getCAStats($selectedDate, $previousDate),
                'immediate_sales' => $this->getImmediateSalesStats($selectedDate, $previousDate),
                'credit_sales' => $this->getCreditSalesStats($selectedDate, $previousDate),
                'reservation_sales' => $this->getReservationSalesStats($selectedDate, $previousDate),
                'profits' => $this->getProfitsStats($selectedDate, $previousDate),
                'expenses' => $this->getExpensesStats($selectedDate, $previousDate),
                'losses' => $this->getLossesStats($selectedDate, $previousDate),
                'customers' => $this->getCustomersStats($selectedDate, $previousDate),
                'stock_value' => $this->getStockValue(), // Par prix de vente
                'stock_cost_value' => $this->getStockCostValue(), // Par coût (nouveau)
            ],
            'trends' => $this->getTrends($selectedDate, $period),
            'top_products' => $this->getTopProducts($selectedDate),
            'navigation' => [
                'previous_date' => $previousDate->format('Y-m-d'),
                'next_date' => $selectedDate->copy()->addDay()->format('Y-m-d'),
                'can_go_forward' => $selectedDate->lt(Carbon::now('Indian/Antananarivo')),
            ],
        ]);
    }

    /**
     * ✅ CA FACTURÉ vs CA ENCAISSÉ
     */
    private function getCAStats(Carbon $date, Carbon $previousDate): array
    {
        // CA FACTURÉ (toutes ventes confirmées du jour)
        $caFacture = Sale::whereDate('sale_date', $date)
            ->where('status', 'CONFIRMED')
            ->sum('total_amount');

        $caFactureYesterday = Sale::whereDate('sale_date', $previousDate)
            ->where('status', 'CONFIRMED')
            ->sum('total_amount');

        // CA ENCAISSÉ (via AccountTransaction - argent réellement reçu)
        $caEncaisse = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('sale_id')
            ->notCancelled()
            ->sum('amount');

        $caEncaisseYesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereNotNull('sale_id')
            ->notCancelled()
            ->sum('amount');

        return [
            'facture' => [
                'value' => (float) $caFacture,
                'vs_yesterday' => $this->calculatePercentage($caFacture, $caFactureYesterday),
            ],
            'encaisse' => [
                'value' => (float) $caEncaisse,
                'vs_yesterday' => $this->calculatePercentage($caEncaisse, $caEncaisseYesterday),
            ],
        ];
    }

    /**
     * ✅ VENTES IMMÉDIATES avec bénéfice net
     */
    private function getImmediateSalesStats(Carbon $date, Carbon $previousDate): array
    {
        $today = $this->getSaleTypeStats($date, 'immediate');
        $yesterday = $this->getSaleTypeStats($previousDate, 'immediate');

        return [
            'count' => (int) $today['count'],
            'revenue' => (float) $today['revenue'],
            'costs' => (float) $today['costs'],
            'profit' => (float) $today['profit'],
            'margin' => $today['revenue'] > 0 ? round(($today['profit'] / $today['revenue']) * 100, 2) : 0,
            'vs_yesterday' => [
                'count' => $this->calculatePercentage($today['count'], $yesterday['count']),
                'revenue' => $this->calculatePercentage($today['revenue'], $yesterday['revenue']),
                'profit' => $this->calculatePercentage($today['profit'], $yesterday['profit']),
            ],
        ];
    }

    /**
     * ✅ CRÉDITS avec bénéfice net
     */
    private function getCreditSalesStats(Carbon $date, Carbon $previousDate): array
    {
        $today = $this->getSaleTypeStats($date, 'credit');
        $yesterday = $this->getSaleTypeStats($previousDate, 'credit');

        // Paiements de crédits reçus aujourd'hui (lien direct via credit_id)
        $paymentsToday = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('credit_id')
            ->notCancelled()
            ->sum('amount');

        $paymentsYesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereNotNull('credit_id')
            ->notCancelled()
            ->sum('amount');

        // Compter le nombre de paiements (crédits distincts ayant reçu un paiement)
        $paymentsCountToday = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('credit_id')
            ->notCancelled()
            ->distinct('credit_id')
            ->count('credit_id');

        $paymentsCountYesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereNotNull('credit_id')
            ->notCancelled()
            ->distinct('credit_id')
            ->count('credit_id');

        return [
            'new_credits' => [
                'count' => (int) $today['count'],
                'revenue' => (float) $today['revenue'],
                'profit' => (float) $today['profit'],
                'vs_yesterday' => [
                    'count' => $this->calculatePercentage($today['count'], $yesterday['count']),
                    'revenue' => $this->calculatePercentage($today['revenue'], $yesterday['revenue']),
                ],
            ],
            'payments' => [
                'value' => (float) $paymentsToday,
                'count' => (int) $paymentsCountToday,
                'vs_yesterday' => [
                    'value' => $this->calculatePercentage($paymentsToday, $paymentsYesterday),
                    'count' => $this->calculatePercentage($paymentsCountToday, $paymentsCountYesterday),
                ],
            ],
        ];
    }

    /**
     * ✅ RÉSERVATIONS avec bénéfice net ET paiements
     */
    private function getReservationSalesStats(Carbon $date, Carbon $previousDate): array
    {
        $today = $this->getSaleTypeStats($date, 'reservation');
        $yesterday = $this->getSaleTypeStats($previousDate, 'reservation');

        // Paiements de réservations reçus aujourd'hui (lien direct via reservation_id)
        $reservationPaymentsToday = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('reservation_id')
            ->notCancelled()
            ->sum('amount');

        $reservationPaymentsYesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereNotNull('reservation_id')
            ->notCancelled()
            ->sum('amount');

        // Compter le nombre de paiements de réservations
        $reservationPaymentsCountToday = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('reservation_id')
            ->notCancelled()
            ->distinct('reservation_id')
            ->count('reservation_id');

        $reservationPaymentsCountYesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereNotNull('reservation_id')
            ->notCancelled()
            ->distinct('reservation_id')
            ->count('reservation_id');

        return [
            'new_reservations' => [
                'count' => (int) $today['count'],
                'revenue' => (float) $today['revenue'],
                'profit' => (float) $today['profit'],
                'margin' => $today['revenue'] > 0 ? round(($today['profit'] / $today['revenue']) * 100, 2) : 0,
                'vs_yesterday' => [
                    'count' => $this->calculatePercentage($today['count'], $yesterday['count']),
                    'revenue' => $this->calculatePercentage($today['revenue'], $yesterday['revenue']),
                    'profit' => $this->calculatePercentage($today['profit'], $yesterday['profit']),
                ],
            ],
            'payments' => [
                'value' => (float) $reservationPaymentsToday,
                'count' => (int) $reservationPaymentsCountToday,
                'vs_yesterday' => [
                    'value' => $this->calculatePercentage($reservationPaymentsToday, $reservationPaymentsYesterday),
                    'count' => $this->calculatePercentage($reservationPaymentsCountToday, $reservationPaymentsCountYesterday),
                ],
            ],
        ];
    }

    /**
     * ✅ BÉNÉFICES GLOBAUX
     */
    private function getProfitsStats(Carbon $date, Carbon $previousDate): array
    {
        $today = $this->calculateDayProfits($date);
        $yesterday = $this->calculateDayProfits($previousDate);

        return [
            'gross_profit' => [
                'value' => (float) $today['gross_profit'],
                'vs_yesterday' => $this->calculatePercentage($today['gross_profit'], $yesterday['gross_profit']),
            ],
            'net_profit' => [
                'value' => (float) $today['net_profit'],
                'vs_yesterday' => $this->calculatePercentage($today['net_profit'], $yesterday['net_profit']),
            ],
            'margins' => [
                'gross_margin' => $today['revenue'] > 0 ? round(($today['gross_profit'] / $today['revenue']) * 100, 2) : 0,
                'net_margin' => $today['revenue'] > 0 ? round(($today['net_profit'] / $today['revenue']) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * ✅ DÉPENSES (hors approvisionnements)
     */
    private function getExpensesStats(Carbon $date, Carbon $previousDate): array
    {
        $today = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('expense_category_id')
            ->notCancelled()
            ->sum('amount');

        $yesterday = AccountTransaction::whereDate('transaction_date', $previousDate)
            ->whereNotNull('expense_category_id')
            ->notCancelled()
            ->sum('amount');

        return [
            'value' => (float) $today,
            'vs_yesterday' => $this->calculatePercentage($today, $yesterday),
            'breakdown' => $this->getExpensesBreakdown($date),
        ];
    }

    /**
     * ✅ PERTES (stock + crédits defaulted)
     */
    private function getLossesStats(Carbon $date, Carbon $previousDate): array
    {
        $todayLosses = $this->calculateDayLosses($date);
        $yesterdayLosses = $this->calculateDayLosses($previousDate);

        return [
            'stock_losses' => [
                'value' => (float) $todayLosses['stock_losses'],
                'vs_yesterday' => $this->calculatePercentage($todayLosses['stock_losses'], $yesterdayLosses['stock_losses']),
            ],
            'total_losses' => [
                'value' => (float) $todayLosses['total'],
                'vs_yesterday' => $this->calculatePercentage($todayLosses['total'], $yesterdayLosses['total']),
            ],
        ];
    }

    /**
     * Statistiques clients
     */
    private function getCustomersStats(Carbon $date, Carbon $previousDate): array
    {
        $newToday = Customer::whereDate('created_at', $date)->count();
        $newYesterday = Customer::whereDate('created_at', $previousDate)->count();

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
                'vs_yesterday' => $this->calculatePercentage($newToday, $newYesterday),
            ],
            'returning_customers' => [
                'count' => $returningToday,
                'vs_yesterday' => $this->calculatePercentage($returningToday, $returningYesterday),
            ],
        ];
    }

    /**
     * Valeur totale du stock par prix de vente (original)
     */
    private function getStockValue(): array
    {
        $value = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
            ->selectRaw('SUM(products.base_price * product_variants.stock_quantity) as total')
            ->where('product_variants.is_active', true)
            ->value('total');

        return [
            'value' => (float) ($value ?? 0),
        ];
    }

    private function getStockCostValue(): array
    {
        // Calcul basé sur les stock_batches disponibles (non vendus)
        $value = StockBatch::where('remaining_quantity', '>', 0)
            ->selectRaw('SUM(remaining_quantity * total_unit_cost) as total_cost')
            ->value('total_cost');

        return [
            'value' => (float) ($value ?? 0),
            'breakdown' => $this->getStockCostBreakdown(),
        ];
    }

    /**
     * ✅ NOUVEAU : Détail de la valeur du stock par produit (regroupé des variants)
     */
    private function getStockCostBreakdown(): array
    {
        return StockBatch::selectRaw('
                products.id as product_id,
                products.name as product_name,
                COUNT(DISTINCT product_variants.id) as variants_count,
                SUM(stock_batches.remaining_quantity) as total_quantity,
                SUM(stock_batches.remaining_quantity * stock_batches.total_unit_cost) as total_cost,
                AVG(stock_batches.total_unit_cost) as avg_unit_cost
            ')
            ->join('product_variants', 'stock_batches.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('stock_batches.remaining_quantity', '>', 0)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                // Récupérer le SKU du variant principal (le premier par ordre alphabétique)
                $mainVariant = ProductVariant::where('product_id', $item->product_id)
                    ->orderBy('sku')
                    ->first();

                // Récupérer l'image du produit ou du premier variant
                $imageUrl = $item->product_image;
                if (! $imageUrl && $mainVariant) {
                    $imageUrl = $mainVariant->image_path;
                }

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'image_url' => $imageUrl,
                    'variants_count' => (int) $item->variants_count,
                    'main_sku' => $mainVariant?->sku ?? 'N/A',
                    'total_quantity' => (int) $item->total_quantity,
                    'total_cost' => (float) $item->total_cost,
                    'avg_unit_cost' => (float) $item->avg_unit_cost,
                ];
            })
            ->toArray();
    }

    /**
     * ✅ TENDANCES avec bénéfice
     */
    private function getTrends(Carbon $selectedDate, string $period): array
    {
        $periodData = $this->getPeriodRange($selectedDate, $period);
        $startDate = $periodData['start'];
        $endDate = $periodData['end'];
        $groupBy = $periodData['group_by'];

        if ($groupBy === 'day') {
            $dateFormat = 'DATE(sale_date)';
        } else {
            $dateFormat = "DATE_TRUNC('week', sale_date)";
        }

        // Revenus et coûts par période
        $salesData = DB::table('sale_item_batches')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw("
                {$dateFormat} as period,
                sales.sale_type,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as costs
            ")
            ->whereIn('sale_item_batches.status', ['sold', 'reserved'])
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->groupByRaw("{$dateFormat}, sales.sale_type")
            ->orderByRaw($dateFormat)
            ->get()
            ->groupBy('period');

        // Dépenses par période
        $expensesDateFormat = $groupBy === 'day'
            ? 'DATE(transaction_date)'
            : "DATE_TRUNC('week', transaction_date)";

        $expensesData = AccountTransaction::selectRaw("
                {$expensesDateFormat} as period,
                SUM(amount) as expenses
            ")
            ->notCancelled()
            ->whereNotNull('expense_category_id')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupByRaw($expensesDateFormat)
            ->orderByRaw($expensesDateFormat)
            ->get()
            ->keyBy('period');

        // ✅ NOUVEAU : Paiements de réservations par période
        $reservationPaymentsDateFormat = $groupBy === 'day'
            ? 'DATE(transaction_date)'
            : "DATE_TRUNC('week', transaction_date)";

        $reservationPaymentsData = AccountTransaction::selectRaw("
                {$reservationPaymentsDateFormat} as period,
                SUM(amount) as reservation_payments
            ")
            ->whereNotNull('reservation_id')
            ->notCancelled()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupByRaw($reservationPaymentsDateFormat)
            ->orderByRaw($reservationPaymentsDateFormat)
            ->get()
            ->keyBy('period');

        // Fusionner les données
        $allPeriods = $salesData->keys()
            ->merge($expensesData->keys())
            ->merge($reservationPaymentsData->keys())
            ->unique()
            ->sort();

        $data = $allPeriods->map(function ($period) use ($salesData, $expensesData, $reservationPaymentsData, $groupBy) {
            $sales = $salesData->get($period, collect());

            $immediate = $sales->where('sale_type', 'immediate')->first();
            $credit = $sales->where('sale_type', 'credit')->first();
            $reservation = $sales->where('sale_type', 'reservation')->first();

            $immediateRevenue = (float) ($immediate->revenue ?? 0);
            $immediateCosts = (float) ($immediate->costs ?? 0);
            $creditRevenue = (float) ($credit->revenue ?? 0);
            $creditCosts = (float) ($credit->costs ?? 0);
            $reservationRevenue = (float) ($reservation->revenue ?? 0);
            $reservationCosts = (float) ($reservation->costs ?? 0);

            $totalRevenue = $immediateRevenue + $creditRevenue + $reservationRevenue;
            $totalCosts = $immediateCosts + $creditCosts + $reservationCosts;
            $expenses = (float) ($expensesData->get($period)->expenses ?? 0);
            $reservationPayments = (float) ($reservationPaymentsData->get($period)->reservation_payments ?? 0);

            $grossProfit = $totalRevenue - $totalCosts;
            $netProfit = $grossProfit - $expenses;

            return [
                'period' => $groupBy === 'day' ? $period : Carbon::parse($period)->format('Y-m-d'),
                'immediate_sales' => $immediateRevenue,
                'immediate_profit' => $immediateRevenue - $immediateCosts,
                'credits' => $creditRevenue,
                'credit_profit' => $creditRevenue - $creditCosts,
                'reservations' => $reservationRevenue,
                'reservation_profit' => $reservationRevenue - $reservationCosts,
                'reservation_payments' => $reservationPayments, // ✅ Nouveau
                'total_revenue' => $totalRevenue,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
            ];
        });

        return [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'group_by' => $groupBy,
            'data' => $data,
        ];
    }

    /**
     * ✅ TOP PRODUITS avec tri par bénéfice, quantité ou CA
     */
    private function getTopProducts(Carbon $date, string $sortBy = 'revenue'): array
    {
        $products = DB::table('sale_item_batches')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_variants', 'sale_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->selectRaw('
                products.id as product_id,
                products.name as product_name,
                products.image_url as product_image,
                SUM(sale_item_batches.quantity) as quantity_sold,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as total_revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as total_cost,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale - stock_batches.total_unit_cost)) as total_profit
            ')
            ->whereIn('sale_item_batches.status', ['sold', 'reserved'])
            ->whereDate('sales.sale_date', $date)
            ->where('sales.status', 'CONFIRMED')
            ->groupBy('products.id', 'products.name', 'products.image_url');

        // Tri selon le critère
        switch ($sortBy) {
            case 'profit':
                $products->orderByDesc('total_profit');
                break;
            case 'quantity':
                $products->orderByDesc('quantity_sold');
                break;
            case 'revenue':
            default:
                $products->orderByDesc('total_revenue');
                break;
        }

        return $products
            ->limit(10)
            ->get()
            ->map(function ($item) {
                // Utiliser l'image du produit, ou chercher l'image du premier variant si null
                $imageUrl = $item->product_image;

                if (! $imageUrl) {
                    $variant = ProductVariant::where('product_id', $item->product_id)
                        ->whereNotNull('image_path')
                        ->first();
                    $imageUrl = $variant?->image_path;
                }

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'image_url' => $imageUrl,
                    'quantity_sold' => (int) $item->quantity_sold,
                    'total_revenue' => (float) $item->total_revenue,
                    'total_cost' => (float) $item->total_cost,
                    'total_profit' => (float) $item->total_profit,
                    'profit_margin' => $item->total_revenue > 0
                        ? round(($item->total_profit / $item->total_revenue) * 100, 2)
                        : 0,
                ];
            })
            ->toArray();
    }

    // ========== MÉTHODES PRIVÉES UTILITAIRES ==========

    /**
     * Calcule revenus, coûts et bénéfice pour un type de vente
     */
    private function getSaleTypeStats(Carbon $date, string $saleType): array
    {
        $status = ['sold'];
        if ($saleType == 'reservation') {
            array_push($status, 'reserved');
        }

        $stats = DB::table('sale_item_batches')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('
                COUNT(DISTINCT sales.id) as count,
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as costs
            ')
            ->whereIn('sale_item_batches.status', $status)
            ->where('sales.sale_type', $saleType)
            ->where('sales.status', 'CONFIRMED')
            ->whereDate('sales.sale_date', $date)
            ->first();

        $revenue = (float) ($stats->revenue ?? 0);
        $costs = (float) ($stats->costs ?? 0);

        return [
            'count' => (int) ($stats->count ?? 0),
            'revenue' => $revenue,
            'costs' => $costs,
            'profit' => $revenue - $costs,
        ];
    }

    /**
     * Calcule les bénéfices globaux du jour
     */
    private function calculateDayProfits(Carbon $date): array
    {
        // Revenus et coûts
        $stats = DB::table('sale_item_batches')
            ->join('stock_batches', 'sale_item_batches.batch_id', '=', 'stock_batches.id')
            ->join('sale_items', 'sale_item_batches.sale_item_id', '=', 'sale_items.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('
                SUM(sale_item_batches.quantity * (sale_item_batches.unit_price_at_sale - sale_item_batches.discount_at_sale)) as revenue,
                SUM(sale_item_batches.quantity * stock_batches.total_unit_cost) as costs
            ')
            ->whereIn('sale_item_batches.status', ['sold', 'reserved'])
            ->whereDate('sales.sale_date', $date)
            ->where('sales.status', 'CONFIRMED')
            ->first();

        $revenue = (float) ($stats->revenue ?? 0);
        $costs = (float) ($stats->costs ?? 0);

        // Dépenses
        $expenses = AccountTransaction::whereDate('transaction_date', $date)
            ->whereNotNull('expense_category_id')
            ->notCancelled()
            ->whereNull('stock_receipt_id')
            ->sum('amount');

        $grossProfit = $revenue - $costs;
        $netProfit = $grossProfit - $expenses;

        return [
            'revenue' => $revenue,
            'costs' => $costs,
            'expenses' => (float) $expenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Calcule les pertes du jour
     */
    private function calculateDayLosses(Carbon $date): array
    {
        // Pertes de stock valorisées
        $stockLosses = StockMovement::where('movement_type', StockMovement::TYPE_LOSS)
            ->whereDate('created_at', $date)
            ->with(['variant.product'])
            ->get()
            ->sum(function ($loss) {
                $basePrice = $loss->variant->product->base_price ?? 0;

                return $loss->quantity * $basePrice;
            });

        return [
            'stock_losses' => (float) $stockLosses,
            'total' => (float) $stockLosses,
        ];
    }

    /**
     * Détail des dépenses par catégorie
     */
    private function getExpensesBreakdown(Carbon $date): array
    {
        return AccountTransaction::selectRaw('
                expense_categories.name as category,
                expense_categories.icon,
                SUM(account_transactions.amount) as total
            ')
            ->notCancelled()
            ->join('expense_categories', 'account_transactions.expense_category_id', '=', 'expense_categories.id')
            ->whereDate('account_transactions.transaction_date', $date)
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.icon')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'icon' => $item->icon,
                    'total' => (float) $item->total,
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

        return $sign.number_format($percentage, 1).'%';
    }

    /**
     * Détermine la plage de dates selon la période
     */
    private function getPeriodRange(Carbon $selectedDate, string $period): array
    {
        switch ($period) {
            case '7days':
                return [
                    'start' => $selectedDate->copy()->subDays(6),
                    'end' => $selectedDate,
                    'group_by' => 'day',
                ];
            case '1month':
                return [
                    'start' => $selectedDate->copy()->subMonth()->addDay(),
                    'end' => $selectedDate,
                    'group_by' => 'day',
                ];
            case '2months':
                return [
                    'start' => $selectedDate->copy()->subMonths(2)->addDay(),
                    'end' => $selectedDate,
                    'group_by' => 'week',
                ];
            case '3months':
                return [
                    'start' => $selectedDate->copy()->subMonths(3)->addDay(),
                    'end' => $selectedDate,
                    'group_by' => 'week',
                ];
            default:
                return [
                    'start' => $selectedDate->copy()->subDays(6),
                    'end' => $selectedDate,
                    'group_by' => 'day',
                ];
        }
    }
}
