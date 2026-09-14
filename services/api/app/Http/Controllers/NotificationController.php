<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * Liste des notifications de l'utilisateur connecté
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('unread_only')) {
            $query->unread();
        }

        if ($request->has('not_dismissed_only')) {
            $query->notDismissed();
        }

        if ($request->has('active_only')) {
            $query->active(); // Non lues ET non dismissées
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('severity')) {
            $query->bySeverity($request->severity);
        }

        $notifications = $query->paginate($request->per_page ?? 20);

        return response()->json($notifications);
    }

    /**
     * Compteur de notifications actives
     * GET /api/notifications/count
     */
    public function count()
    {
        $counts = [
            'total' => Notification::where('user_id', Auth::id())->count(),
            'unread' => Notification::where('user_id', Auth::id())->unread()->count(),
            'active' => Notification::where('user_id', Auth::id())->active()->count(),
            'by_type' => Notification::where('user_id', Auth::id())
                ->active()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];

        return response()->json($counts);
    }

    /**
     * Marquer comme lue
     * PATCH /api/notifications/{id}/read
     */
    public function markAsRead(Notification $notification)
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'notification' => $notification,
        ]);
    }

    /**
     * Marquer toutes comme lues
     * POST /api/notifications/mark-all-read
     */
    public function markAllAsRead()
    {
        $updated = Notification::where('user_id', Auth::id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => "{$updated} notifications marquées comme lues",
        ]);
    }

    /**
     * Dismisser une notification (ne plus afficher)
     * DELETE /api/notifications/{id}/dismiss
     */
    public function dismiss(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->dismiss();

        return response()->json([
            'message' => 'Notification dismissée',
        ]);
    }

    /**
     * Dismisser toutes les notifications d'un type
     * POST /api/notifications/dismiss-by-type
     */
    public function dismissByType(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:stock_low,stock_out,reservation_expiring,credit_due,planned_expense_due',
        ]);

        $updated = Notification::where('user_id', Auth::id())
            ->where('type', $request->type)
            ->whereNull('dismissed_at')
            ->update(['dismissed_at' => now()]);

        return response()->json([
            'message' => "{$updated} notifications de type '{$request->type}' dismissées",
        ]);
    }

    /**
     * Supprimer une notification
     * DELETE /api/notifications/{id}
     */
    public function destroy(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json([
            'message' => 'Notification supprimée',
        ], 200);
    }

    /**
     * Nettoyer les anciennes notifications
     * DELETE /api/notifications/cleanup
     */
    public function cleanup(Request $request)
    {
        $days = $request->days ?? 30;

        $deleted = Notification::where('user_id', Auth::id())
            ->where(function ($q) {
                $q->where('is_read', true)
                    ->orWhereNotNull('dismissed_at');
            })
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        return response()->json([
            'message' => "{$deleted} anciennes notifications supprimées",
        ]);
    }

    /**
     * Préférences de notifications
     * GET /api/notifications/preferences
     */
    public function getPreferences()
    {
        $preferences = NotificationPreference::where('user_id', Auth::id())
            ->get();

        // Créer les préférences manquantes avec valeurs par défaut
        $types = [
            'stock_low',
            'stock_out',
            'reservation_expiring',
            'credit_due',
            'planned_expense_due',
        ];
        $existingTypes = $preferences->pluck('notification_type')->toArray();

        foreach ($types as $type) {
            if (! in_array($type, $existingTypes)) {
                $preferences->push(
                    NotificationPreference::getOrCreateForUser(Auth::id(), $type)
                );
            }
        }

        return response()->json($preferences);
    }

    /**
     * Mettre à jour les préférences
     * PUT /api/notifications/preferences/{id}
     */
    public function updatePreference(Request $request, NotificationPreference $preference)
    {
        // Vérifier que l'utilisateur possède cette préférence
        if ($preference->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Non autorisé',
            ], 403);
        }

        $validated = $request->validate([
            'reminder_interval_days' => 'sometimes|integer|min:1|max:30',
            'enabled' => 'sometimes|boolean',
        ]);

        $preference->update($validated);

        return response()->json([
            'message' => 'Préférences mises à jour',
            'preference' => $preference,
        ]);
    }

    /**
     * Générer manuellement les notifications (ADMIN)
     * POST /api/notifications/generate
     */
    public function generate(Request $request)
    {
        // Vérifier que l'utilisateur est admin
        if (! Auth::user()->role == 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $type = $request->type;

        if ($type) {
            $count = match ($type) {
                'stock_low' => $this->notificationService->generateStockLowNotifications(),
                'stock_out' => $this->notificationService->generateStockOutNotifications(),
                'reservation_expiring' => $this->notificationService->generateReservationExpiringNotifications(),
                'credit_due' => $this->notificationService->generateCreditDueNotifications(),
                'planned_expense_due' => $this->notificationService->generatePlannedExpenseDueNotifications(),
                default => 0
            };

            return response()->json([
                'message' => "{$count} notifications '{$type}' générées",
            ]);
        }

        $stats = $this->notificationService->generateAllNotifications();

        return response()->json([
            'message' => 'Notifications générées',
            'stats' => $stats,
            'total' => array_sum($stats),
        ]);
    }
}
