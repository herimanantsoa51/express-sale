<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\AccountCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controller pour la gestion des comptes monétaires
 */
class AccountController extends Controller
{
    /**
     * Liste tous les comptes avec filtres et pagination
     * GET /api/accounts
     * 
     * Query params:
     * - type_id: Filtrer par type de compte
     * - is_active: Filtrer par statut (true/false)
     * - search: Recherche dans le nom
     * - sort_by: Champ de tri (default: name)
     * - sort_order: Ordre de tri (asc/desc, default: asc)
     * - per_page: Nombre d'éléments par page (default: 15)
     */
    public function index(Request $request): AccountCollection
    {
        $query = Account::with(['accountType', 'creator']);

        // Filtre par type
        if ($request->has('type_id')) {
            $query->where('account_type_id', $request->type_id);
        }

        // Filtre par statut
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('account_number', 'like', "%$search%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'type') {
            $query->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
                  ->orderBy('account_types.display_name', $sortOrder)
                  ->select('accounts.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 15);
        $accounts = $query->paginate($perPage);

        return new AccountCollection($accounts);
    }

    /**
     * Créer un nouveau compte
     * POST /api/accounts
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        try {
            // Utiliser la fonction PostgreSQL pour créer le compte avec solde initial
            $accountId = \App\Helpers\PostgresTreasuryHelper::createAccountWithInitialBalance(
                $request->account_type_id,
                $request->name,
                $request->account_number,
                $request->initial_balance,
                $request->notes,
                Auth::id()
            );

            $account = Account::with('accountType', 'creator')->find($accountId);

            return response()->json([
                'status' => 'success',
                'message' => 'Compte créé avec succès',
                'data' => new AccountResource($account)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un compte spécifique
     * GET /api/accounts/{id}
     * 
     * Query params:
     * - include_stats: Inclure les statistiques (true/false)
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        $account->load(['accountType', 'creator']);

        return response()->json([
            'status' => 'success',
            'data' => new AccountResource($account)
        ]);
    }

    /**
     * Mettre à jour un compte
     * PUT/PATCH /api/accounts/{id}
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $account->update($request->validated());
        $account->load('accountType', 'creator');

        return response()->json([
            'status' => 'success',
            'message' => 'Compte mis à jour avec succès',
            'data' => new AccountResource($account)
        ]);
    }

    
    /**
     * Désactiver un compte (soft delete)
     * PATCH /api/accounts/{id}/deactivate
     */
    public function deactivate(Account $account): JsonResponse
    {
        // Vérifier si le compte a des transactions
        if ($account->transactions()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de désactiver un compte avec des transactions'
            ], 422);
        }

        $account->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'Compte désactivé avec succès',
            'data' => new AccountResource($account)
        ]);
    }

    /**
     * Activer un compte
     * PATCH /api/accounts/{id}/activate
     */
    public function activate(Account $account): JsonResponse
    {
        $account->update(['is_active' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Compte activé avec succès',
            'data' => new AccountResource($account)
        ]);
    }

    /**
     * Obtenir les statistiques d'un compte
     * GET /api/accounts/{id}/stats
     * 
     * Query params:
     * - start_date: Date de début (format: Y-m-d)
     * - end_date: Date de fin (format: Y-m-d)
     */
    public function stats(Request $request, Account $account): JsonResponse
    {
        $dateRange = null;
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $dateRange = [$request->start_date, $request->end_date];
        }

        $stats = $account->getStats($dateRange);

        return response()->json([
            'status' => 'success',
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                ],
                'period' => $dateRange ? [
                    'start' => $request->start_date,
                    'end' => $request->end_date,
                ] : 'all_time',
                'statistics' => $stats,
            ]
        ]);
    }

    /**
     * Obtenir le solde d'un compte à une date donnée
     * GET /api/accounts/{id}/balance-at-date?date=2025-01-15
     */
    public function balanceAtDate(Request $request, Account $account): JsonResponse
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $balance = $account->getBalanceAtDate($request->date);

        return response()->json([
            'status' => 'success',
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                ],
                'date' => $request->date,
                'balance' => $balance,
                'formatted_balance' => number_format($balance, 2, ',', ' ') . ' Ar',
            ]
        ]);
    }
    public function getCashMobileMoney()
    {
        $accounts = Account::query()
            ->select(['id', 'name', 'account_number', 'account_type_id'])
            ->where('is_active', true)
            ->whereHas('accountType', function ($query) {
                $query->whereIn('code', ['CASH', 'MOBILE_MONEY']);
            })
            ->with(['accountType:id,code'])
            ->orderBy('account_type_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'account_number' => $account->account_number,
                    'type' => strtolower($account->accountType->code)
                ];
            })
        ]);
    }
}