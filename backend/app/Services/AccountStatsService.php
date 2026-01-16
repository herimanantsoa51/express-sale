<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountStatsService
{
    public function getStats(Request $request)
    {
        $statsQuery = Account::query();
        
        // Appliquer les filtres
        $this->applyFilters($statsQuery, $request);
        
        // Récupérer le total d'argent
        $totalMoney = (float) $statsQuery->sum('current_balance');
        
        // Récupérer les comptes actifs/inactifs
        $activeAccountsCount = $statsQuery->clone()->where('is_active', true)->count();
        $inactiveAccountsCount = $statsQuery->clone()->where('is_active', false)->count();
        $totalAccountsCount = $statsQuery->count();
        
        // Calculer la moyenne
        $averageBalance = $totalAccountsCount > 0 ? $totalMoney / $totalAccountsCount : 0;
        
        // Récupérer les statistiques par type
        $byType = $this->getStatsByType($request);
        
        // Calculer les pourcentages
        if ($totalMoney > 0) {
            $byType = $byType->map(function ($type) use ($totalMoney) {
                $type['percentage'] = round(($type['total_balance'] / $totalMoney) * 100, 2);
                return $type;
            });
        }
        
        return [
            'total_money' => $totalMoney,
            'active_accounts' => $activeAccountsCount,
            'inactive_accounts' => $inactiveAccountsCount,
            'total_accounts' => $totalAccountsCount,
            'average_balance' => $averageBalance,
            'by_type' => $byType,
        ];
    }
    
    protected function getStatsByType(Request $request)
    {
        $query = Account::join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->selectRaw('
                account_types.id as type_id,
                account_types.display_name as type_name,
                account_types.code as type_code,
                SUM(accounts.current_balance) as total_balance,
                COUNT(accounts.id) as account_count
            ')
            ->groupBy('account_types.id', 'account_types.display_name', 'account_types.code');
        
        // Appliquer les mêmes filtres
        $this->applyFilters($query, $request);
        
        return $query->get();
    }
    
    protected function applyFilters($query, Request $request)
    {
        if ($request->has('type_id')) {
            $query->where('account_type_id', $request->type_id);
        }
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('accounts.name', 'like', "%$search%")
                  ->orWhere('accounts.account_number', 'like', "%$search%");
            });
        }
        
        return $query;
    }
}