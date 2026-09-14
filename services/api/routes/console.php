<?php

/*
|--------------------------------------------------------------------------
| Tâches planifiées — Express Sale (Laravel 11)
|--------------------------------------------------------------------------
| Attention : app/Console/Kernel.php est du code mort en Laravel 11 —
| c'est ICI que les schedules doivent être définis (ou via ->withSchedule()).
| Lancement en production : cron toutes les minutes -> `php artisan schedule:run`.
*/

use App\Models\Credit;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Support\Facades\Schedule;

// Rattrapage des tâches manquées (si pas de rattrapage dans les dernières 12h)
Schedule::command('tasks:catchup --days=2')
    ->when(function () {
        $lastCatchup = cache()->get('last_catchup_at');

        return ! $lastCatchup || now()->diffInHours($lastCatchup) > 12;
    })
    ->name('startup-catchup')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/startup.log'));

// Génération des notifications (toutes les heures)
Schedule::command('notifications:generate')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/notifications.log'));

// Nettoyage des anciennes notifications (1x par jour à 2h du matin)
Schedule::command('tasks:cleanup')
    ->dailyAt('02:00')
    ->name('daily-cleanup');

// Expiration des réservations (toutes les 6h) — statut uniquement.
// Politique métier : aucun automatisme — le stock réservé n'est pas libéré et
// l'acompte n'est pas remboursé ; régularisation manuelle par l'opérateur.
Schedule::call(function () {
    $reservationService = app(ReservationService::class);

    Reservation::with('items')
        ->whereIn('status', ['pending', 'confirmed', 'partial_paid'])
        ->where('expiry_date', '<=', now())
        ->get()
        ->each(function (Reservation $reservation) use ($reservationService) {
            $reservationService->expireReservation(
                (int) $reservation->id,
                "Expiration automatique - Date d'expiration dépassée"
            );
        });
})
    ->everySixHours()
    ->name('expire-reservations')
    ->withoutOverlapping();

// Crédits en retard (1x par jour à 1h du matin)
Schedule::call(function () {
    Credit::whereIn('status', ['active', 'partial_paid'])
        ->whereHas('installments', function ($q) {
            $q->where('status', 'pending')
                ->where('due_date', '<', now());
        })
        ->update(['status' => 'overdue']);
})
    ->dailyAt('01:00')
    ->name('update-overdue-credits');

// Vérification de sécurité (toutes les 3h)
Schedule::command('tasks:safety-check')
    ->everyThreeHours()
    ->name('safety-check');
