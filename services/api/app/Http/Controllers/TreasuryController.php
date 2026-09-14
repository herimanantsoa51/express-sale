<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller pour la vue globale de la trésorerie
 */
class TreasuryController extends Controller
{
    /**
     * Vue d'ensemble de la trésorerie
     * GET /api/treasury/overview
     *
     * Query params:
     * - start_date: Date de début pour les statistiques
     * - end_date: Date de fin pour les statistiques
     */
    public function overview(Request $request): JsonResponse
    {
        // Récupérer tous les comptes actifs
        $accounts = Account::with('accountType')
            ->active()
            ->orderBy('account_type_id')
            ->orderBy('name')
            ->get();

        // Calculer le total global
        $totalBalance = $accounts->sum('current_balance');

        // Regrouper par type de compte
        $accountsByType = $accounts->groupBy('account_type_id')->map(function ($typeAccounts) {
            $accountType = $typeAccounts->first()->accountType;

            return [
                'type' => [
                    'id' => $accountType->id,
                    'code' => $accountType->code,
                    'display_name' => $accountType->display_name,
                ],
                'total_balance' => (float) $typeAccounts->sum('current_balance'),
                'formatted_balance' => number_format($typeAccounts->sum('current_balance'), 2, ',', ' ').' Ar',
                'accounts_count' => $typeAccounts->count(),
                'accounts' => $typeAccounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'balance' => (float) $account->current_balance,
                        'formatted_balance' => number_format($account->current_balance, 2, ',', ' ').' Ar',
                    ];
                })->values(),
            ];
        })->values();

        // Statistiques sur la période demandée
        $dateRange = null;
        if ($request->has('start_date') && $request->has('end_date')) {
            $dateRange = [$request->start_date, $request->end_date];
        }

        $stats = $this->calculatePeriodStats($dateRange);

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_balance' => (float) $totalBalance,
                    'formatted_total' => number_format($totalBalance, 2, ',', ' ').' Ar',
                    'total_accounts' => $accounts->count(),
                    'last_updated' => now()->format('Y-m-d H:i:s'),
                ],
                'by_type' => $accountsByType,
                'period_stats' => $stats,
            ],
        ]);
    }

    /**
     * Vue de la trésorerie avec conversion en devises
     * GET /api/treasury/with-currencies
     */
    public function withCurrencies(): JsonResponse
    {
        // Récupérer le taux actuel
        $currentRate = CurrencyRate::getSystemRate();

        if (! $currentRate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun taux de change actif défini',
            ], 404);
        }

        // Récupérer tous les comptes actifs
        $accounts = Account::with('accountType')->active()->get();
        $totalAriaryBalance = $accounts->sum('current_balance');

        // Convertir en différentes devises
        $currencies = [
            'euro' => [
                'code' => 'EUR',
                'symbol' => '€',
                'rate' => (float) $currentRate->euro_rate,
                'amount' => $totalAriaryBalance / $currentRate->euro_rate,
            ],
            'dollar' => [
                'code' => 'USD',
                'symbol' => '$',
                'rate' => (float) $currentRate->dollar_rate,
                'amount' => $totalAriaryBalance / $currentRate->dollar_rate,
            ],
            'yen' => [
                'code' => 'JPY',
                'symbol' => '¥',
                'rate' => (float) $currentRate->yen_rate,
                'amount' => $totalAriaryBalance / $currentRate->yen_rate,
            ],
            'dirham' => [
                'code' => 'MAD',
                'symbol' => 'MAD',
                'rate' => (float) $currentRate->dirham_rate,
                'amount' => $totalAriaryBalance / $currentRate->dirham_rate,
            ],
            'baht' => [
                'code' => 'THB',
                'symbol' => '฿',
                'rate' => (float) $currentRate->baht_rate,
                'amount' => $totalAriaryBalance / $currentRate->baht_rate,
            ],
        ];

        // Formater les montants
        foreach ($currencies as $key => $currency) {
            $currencies[$key]['formatted_amount'] = number_format($currency['amount'], 2, '.', ',');
            $currencies[$key]['with_symbol'] = $currency['symbol'].' '.number_format($currency['amount'], 2, '.', ',');
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'ariary_balance' => [
                    'amount' => (float) $totalAriaryBalance,
                    'formatted' => number_format($totalAriaryBalance, 2, ',', ' ').' Ar',
                ],
                'currencies' => $currencies,
                'exchange_rate' => [
                    'id' => $currentRate->id,
                    'effective_date' => $currentRate->effective_date->format('Y-m-d'),
                    'is_recent' => $currentRate->isRecent(),
                    'days_old' => $currentRate->effective_date->diffInDays(Carbon::today()),
                ],
            ],
        ]);
    }

    /**
     * Statistiques détaillées de trésorerie
     * GET /api/treasury/stats
     *
     * Query params:
     * - start_date: Date de début
     * - end_date: Date de fin
     * - group_by: Regroupement (day/week/month, default: day)
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'in:day,week,month',
        ]);

        $dateRange = [$request->start_date, $request->end_date];
        $groupBy = $request->get('group_by', 'day');

        $stats = $this->calculatePeriodStats($dateRange);
        $timeSeriesData = $this->getTimeSeriesData($dateRange, $groupBy);

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => [
                    'start' => $request->start_date,
                    'end' => $request->end_date,
                    'group_by' => $groupBy,
                ],
                'summary' => $stats,
                'time_series' => $timeSeriesData,
            ],
        ]);
    }

    /**
     * Calcule les statistiques pour une période
     */
    private function calculatePeriodStats(?array $dateRange): array
    {
        $query = AccountTransaction::query();

        if ($dateRange) {
            $query->betweenDates($dateRange[0], $dateRange[1]);
        }

        $income = (clone $query)->income()->sum('amount');
        $expense = (clone $query)->expense()->sum('amount');

        $transferIn = (clone $query)
            ->transfer()
            ->where('amount', '>', 0)
            ->sum('amount');

        $transferOut = (clone $query)
            ->transfer()
            ->where('amount', '<', 0)
            ->sum('amount');

        $netFlow = $income + $expense + $transferIn + $transferOut;

        return [
            'total_income' => (float) $income,
            'formatted_income' => number_format($income, 2, ',', ' ').' Ar',
            'total_expense' => (float) abs($expense),
            'formatted_expense' => number_format(abs($expense), 2, ',', ' ').' Ar',
            'total_transfer_in' => (float) $transferIn,
            'total_transfer_out' => (float) abs($transferOut),
            'net_flow' => (float) $netFlow,
            'formatted_net_flow' => number_format($netFlow, 2, ',', ' ').' Ar',
            'transactions_count' => $query->count(),
        ];
    }

    /**
     * Récupère les données en série temporelle
     */
    private function getTimeSeriesData(array $dateRange, string $groupBy): array
    {
        $format = match ($groupBy) {
            'week' => '%Y-W%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $transactions = AccountTransaction::selectRaw("
                DATE_FORMAT(transaction_date, '$format') as period,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense,
                COUNT(*) as count
            ")
            ->betweenDates($dateRange[0], $dateRange[1])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $transactions->map(function ($item) {
            return [
                'period' => $item->period,
                'income' => (float) $item->income,
                'expense' => (float) $item->expense,
                'net' => (float) ($item->income - $item->expense),
                'transactions_count' => $item->count,
            ];
        })->toArray();
    }
}
