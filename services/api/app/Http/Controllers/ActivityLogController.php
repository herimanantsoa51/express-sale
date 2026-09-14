<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Liste paginée des logs avec filtres
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,role')
            ->orderBy('created_at', 'desc');

        // Filtre par utilisateur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre par action(s)
        if ($request->filled('action')) {
            if (is_array($request->action)) {
                $query->whereIn('action', $request->action);
            } else {
                $query->where('action', $request->action);
            }
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Par défaut, afficher seulement les succès
            $query->where('status', 'success');
        }

        // Voir tous les statuts
        if ($request->show_all === 'true' || $request->show_all === true) {
            $query->whereIn('status', ['success', 'failed', 'error']);
        }

        // Filtre par type de modèle
        if ($request->filled('model_type')) {
            $modelType = $request->model_type;
            // Permettre de rechercher juste le nom de classe
            if (! str_contains($modelType, '\\')) {
                $modelType = "App\\Models\\{$modelType}";
            }
            $query->where('model_type', $modelType);
        }

        // Filtre par ID de modèle
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // Filtre par catégorie d'actions
        if ($request->filled('category')) {
            $actions = $this->getActionsByCategory($request->category);
            if (! empty($actions)) {
                $query->whereIn('action', $actions);
            }
        }

        // Filtre par plage de dates
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Recherche dans la description
        if ($request->filled('search')) {
            $query->where('description', 'like', "%{$request->search}%");
        }

        // Filtre par IP (utile pour détecter les actions suspectes)
        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        $perPage = $request->per_page ?? 20;
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Détail d'un log spécifique
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user:id,name,role');

        return response()->json($activityLog);
    }

    /**
     * Logs liés à un modèle spécifique
     */
    public function byModel(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $modelType = $request->model_type;

        // Permettre de passer juste le nom de classe
        if (! str_contains($modelType, '\\')) {
            $modelType = "App\\Models\\{$modelType}";
        }

        $query = ActivityLog::with('user:id,name,role')
            ->where('model_type', $modelType)
            ->where('model_id', $request->model_id)
            ->orderBy('created_at', 'desc');

        // Filtres optionnels
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->per_page ?? 20;
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Liste des échecs uniquement
     */
    public function failures(Request $request)
    {
        $query = ActivityLog::with('user:id,name,role')
            ->whereIn('status', ['failed', 'error'])
            ->orderBy('created_at', 'desc');

        // Filtres optionnels
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->per_page ?? 20;
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Statistiques globales
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)
            : now()->subDays(30);

        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)
            : now();

        $totalLogs = ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        $stats = [
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
            ],
            'total' => $totalLogs,

            // Par statut
            'by_status' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->status => $item->count]),

            // Taux de succès
            'success_rate' => $totalLogs > 0
                ? round(ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('status', 'success')
                    ->count() / $totalLogs * 100, 2)
                : 0,

            // Par utilisateur (top 10)
            'by_user' => ActivityLog::with('user:id,name')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user->name ?? 'Utilisateur supprimé',
                        'count' => $item->count,
                    ];
                }),

            // Par action (top 10)
            'by_action' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'action' => $item->action,
                        'label' => ActivityAction::labels()[$item->action] ?? $item->action,
                        'count' => $item->count,
                    ];
                }),

            // Par type de modèle
            'by_model' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('model_type')
                ->selectRaw('model_type, COUNT(*) as count')
                ->groupBy('model_type')
                ->orderByDesc('count')
                ->get()
                ->map(function ($item) {
                    return [
                        'model_type' => class_basename($item->model_type),
                        'count' => $item->count,
                    ];
                }),

            // Échecs récents (7 derniers jours)
            'recent_failures' => ActivityLog::whereIn('status', ['failed', 'error'])
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),

            // Activité par jour (derniers 30 jours)
            'daily_activity' => ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($item) => [
                    'date' => $item->date,
                    'count' => $item->count,
                ]),
        ];

        return response()->json($stats);
    }

    /**
     * Actions groupées par catégorie
     */
    public function actionsByCategory()
    {
        $categories = [
            'sales' => [
                'label' => 'Ventes',
                'actions' => $this->getActionsByCategory('sales'),
            ],
            'credits' => [
                'label' => 'Crédits',
                'actions' => $this->getActionsByCategory('credits'),
            ],
            'reservations' => [
                'label' => 'Réservations',
                'actions' => $this->getActionsByCategory('reservations'),
            ],
            'stock' => [
                'label' => 'Stock',
                'actions' => $this->getActionsByCategory('stock'),
            ],
            'products' => [
                'label' => 'Produits',
                'actions' => $this->getActionsByCategory('products'),
            ],
            'customers' => [
                'label' => 'Clients',
                'actions' => $this->getActionsByCategory('customers'),
            ],
            'accounts' => [
                'label' => 'Comptes',
                'actions' => $this->getActionsByCategory('accounts'),
            ],
            'users' => [
                'label' => 'Utilisateurs',
                'actions' => $this->getActionsByCategory('users'),
            ],
        ];

        return response()->json($categories);
    }

    /**
     * Supprimer les logs entre deux dates
     */
    public function deleteBetweenDates(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($request->date_from)->startOfDay();
        $dateTo = Carbon::parse($request->date_to)->endOfDay();

        DB::beginTransaction();
        try {
            // Compter avant suppression
            $count = ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])->count();

            // Supprimer
            ActivityLog::whereBetween('created_at', [$dateFrom, $dateTo])->delete();

            DB::commit();

            return response()->json([
                'message' => 'Suppression réussie',
                'deleted_count' => $count,
                'date_from' => $dateFrom->format('Y-m-d H:i:s'),
                'date_to' => $dateTo->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer les logs plus anciens qu'une date
     */
    public function deleteOlderThan(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date)->endOfDay();

        DB::beginTransaction();
        try {
            $count = ActivityLog::where('created_at', '<', $date)->count();

            ActivityLog::where('created_at', '<', $date)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Suppression réussie',
                'deleted_count' => $count,
                'before_date' => $date->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer les logs par statut
     */
    public function deleteByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:success,failed,error',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = ActivityLog::where('status', $request->status);

        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        DB::beginTransaction();
        try {
            $count = $query->count();
            $query->delete();

            DB::commit();

            return response()->json([
                'message' => 'Suppression réussie',
                'deleted_count' => $count,
                'status' => $request->status,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Nettoyer automatiquement les vieux logs
     * (À appeler via une tâche planifiée)
     */
    public function autoCleanup(Request $request)
    {
        $request->validate([
            'keep_days' => 'nullable|integer|min:1',
        ]);

        // Par défaut, garder 90 jours
        $keepDays = $request->keep_days ?? 90;
        $date = now()->subDays($keepDays);

        DB::beginTransaction();
        try {
            $count = ActivityLog::where('created_at', '<', $date)->count();

            ActivityLog::where('created_at', '<', $date)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Nettoyage automatique réussi',
                'deleted_count' => $count,
                'kept_days' => $keepDays,
                'deleted_before' => $date->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors du nettoyage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les actions par catégorie
     */
    private function getActionsByCategory(string $category): array
    {
        return match ($category) {
            'sales' => [
                ActivityAction::SALE_CREATED,
                ActivityAction::SALE_CANCELLED,
            ],
            'credits' => [
                ActivityAction::CREDIT_CREATED,
                ActivityAction::CREDIT_CANCELLED,
                ActivityAction::INSTALLMENT_PAID,
            ],
            'reservations' => [
                ActivityAction::RESERVATION_CREATED,
                ActivityAction::RESERVATION_COMPLETED,
                ActivityAction::RESERVATION_CANCELLED,
                ActivityAction::RESERVATION_DEPOSIT_ADDED,
            ],
            'stock' => [
                ActivityAction::STOCK_RECEIPT_CREATED,
                ActivityAction::STOCK_RECEIPT_VALIDATED,
                ActivityAction::STOCK_RECEIPT_CANCELLED,
                ActivityAction::STOCK_TRANSFERRED,
                ActivityAction::STOCK_ADJUSTED,
                ActivityAction::STOCK_LOSS_DECLARED,
            ],
            'products' => [
                ActivityAction::PRODUCT_CREATED,
                ActivityAction::PRODUCT_UPDATED,
                ActivityAction::PRODUCT_DELETED,
                ActivityAction::PRODUCT_PRICES_UPDATED,
            ],
            'customers' => [
                ActivityAction::CUSTOMER_CREATED,
                ActivityAction::CUSTOMER_UPDATED,
                ActivityAction::CUSTOMER_CREDIT_LIMIT_ADJUSTED,
                ActivityAction::CUSTOMER_LOYALTY_POINTS_ADDED,
            ],
            'accounts' => [
                ActivityAction::ACCOUNT_CREATED,
                ActivityAction::ACCOUNT_ACTIVATED,
                ActivityAction::ACCOUNT_DEACTIVATED,
                ActivityAction::EXPENSE_CREATED,
                ActivityAction::TRANSACTION_CANCELLED,
            ],
            'users' => [
                ActivityAction::USER_CREATED,
                ActivityAction::USER_UPDATED,
                ActivityAction::USER_STATUS_TOGGLED,
            ],
            default => [],
        };
    }
}
