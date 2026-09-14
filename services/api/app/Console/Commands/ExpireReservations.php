<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire les réservations dont la date d\'expiration est dépassée et libère le stock';

    /**
     * Execute the console command.
     */
    public function handle(ReservationService $reservationService)
    {
        $this->info('🔍 Recherche des réservations expirées...');

        // Récupérer toutes les réservations expirées qui ne sont pas encore marquées comme telles
        $expiredReservations = Reservation::with(['items'])
            ->whereIn('status', ['pending', 'confirmed', 'partial_paid'])
            ->where('expiry_date', '<=', now())
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('✅ Aucune réservation expirée trouvée.');

            return 0;
        }

        $this->info("📦 {$expiredReservations->count()} réservation(s) expirée(s) trouvée(s).");

        $successCount = 0;
        $errorCount = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                $reservationService->expireReservation(
                    (int) $reservation->id,
                    "Expiration automatique - Date d'expiration dépassée"
                );

                $this->line("  ✅ Réservation #{$reservation->id} ({$reservation->reservation_number}) - Stock libéré");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("  ❌ Erreur réservation #{$reservation->id}: {$e->getMessage()}");

                Log::error("Erreur lors de l'expiration de la réservation #{$reservation->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $errorCount++;
            }
        }

        $this->newLine();
        $this->info('📊 Résumé:');
        $this->info("  ✅ Réussies: {$successCount}");
        if ($errorCount > 0) {
            $this->error("  ❌ Erreurs: {$errorCount}");
        }

        return $successCount > 0 ? 0 : 1;
    }
}
