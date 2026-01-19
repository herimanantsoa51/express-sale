<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Credit;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CatchUpMissedTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:catchup 
                            {--days=1 : Nombre de jours à vérifier en arrière}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rattraper les tâches manquées lors d\'un arrêt du serveur';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $days = (int) $this->option('days');
        $this->info("🔍 Rattrapage des tâches des {$days} derniers jours...");
        
        $stats = [];
        
        // 1. Rattraper les réservations expirées
        $stats['reservations_expired'] = $this->catchUpExpiredReservations($days);
        
        // 2. Rattraper les crédits en retard
        $stats['credits_overdue'] = $this->catchUpOverdueCredits($days);
        
        // 3. Rattraper les notifications (optionnel, car elles sont générées à la volée)
        $stats['notifications_generated'] = $notificationService->generateAllNotifications();
        
        // 4. Supprimer les anciennes notifications (si plus de 90 jours)
        $stats['old_notifications_deleted'] = $this->cleanupOldNotifications();
        
        $this->info("✅ Rattrapage terminé !");
        $this->table(
            ['Tâche', 'Résultat'],
            collect($stats)->map(fn($result, $task) => [$task, is_array($result) ? json_encode($result) : $result])->toArray()
        );
        
        Log::info("Tâches rattrapées", $stats);
        
        // Enregistrer le dernier rattrapage
        cache()->put('last_catchup_at', now(), now()->addDays(7));
    }
    
    private function catchUpExpiredReservations(int $days): array
    {
        $count = Reservation::whereIn('status', ['pending', 'confirmed', 'partial_paid'])
            ->where('expiry_date', '<=', now())
            ->where('expiry_date', '>=', now()->subDays($days))
            ->update(['status' => 'expired']);
            
        return [
            'count' => $count,
            'message' => "{$count} réservations marquées comme expirées"
        ];
    }
    
    private function catchUpOverdueCredits(int $days): array
    {
        $count = Credit::whereIn('status', ['active', 'partial_paid'])
            ->whereHas('installments', function($q) use ($days) {
                $q->where('status', 'pending')
                  ->where('due_date', '<', now())
                  ->where('due_date', '>=', now()->subDays($days));
            })
            ->update(['status' => 'overdue']);
            
        return [
            'count' => $count,
            'message' => "{$count} crédits marqués comme en retard"
        ];
    }
    
    private function cleanupOldNotifications(): array
    {
        $count = Notification::where(function($q) {
                $q->where('is_read', true)
                  ->orWhereNotNull('dismissed_at');
            })
            ->where('created_at', '<', now()->subDays(90))
            ->delete();
            
        return [
            'count' => $count,
            'message' => "{$count} anciennes notifications supprimées"
        ];
    }
}