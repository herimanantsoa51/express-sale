<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // EXÉCUTER AU DÉMARRAGE si pas de rattrapage récent
        $schedule->command('tasks:catchup --days=2')
            ->when(function () {
                // Exécuter seulement si pas de rattrapage dans les dernières 12h
                $lastCatchup = cache()->get('last_catchup_at');
                return !$lastCatchup || now()->diffInHours($lastCatchup) > 12;
            })
            ->name('startup-catchup')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/startup.log'));
        
        // Générer les notifications toutes les heures
        $schedule->command('notifications:generate')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/notifications.log'));
        
        // Nettoyer les anciennes notifications (1x par jour à 2h du matin)
        $schedule->command('tasks:cleanup')
            ->dailyAt('02:00')
            ->name('daily-cleanup');
        
        // Vérifier les réservations expirées (toutes les 6h)
        $schedule->call(function () {
            \App\Models\Reservation::whereIn('status', ['pending', 'confirmed', 'partial_paid'])
                ->where('expiry_date', '<=', now())
                ->update(['status' => 'expired']);
        })
        ->everySixHours()
        ->name('update-expired-reservations');
        
        // Vérifier les crédits en retard (1x par jour à 1h du matin)
        $schedule->call(function () {
            \App\Models\Credit::whereIn('status', ['active', 'partial_paid'])
                ->whereHas('installments', function($q) {
                    $q->where('status', 'pending')
                      ->where('due_date', '<', now());
                })
                ->update(['status' => 'overdue']);
        })
        ->dailyAt('01:00')
        ->name('update-overdue-credits');
        
        // Vérification de sécurité toutes les 3h
        $schedule->command('tasks:safety-check')
            ->everyThreeHours()
            ->name('safety-check');
    }
    
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
    
    /**
     * Bootstrapper personnalisé pour exécuter au vrai démarrage
     * (Quand artisan schedule:run est lancé)
     */
    protected function bootstrappers(): array
    {
        return array_merge(
            [\App\Bootstrap\StartupCatchup::class], // Voir étape 4
            parent::bootstrappers(),
        );
    }
}