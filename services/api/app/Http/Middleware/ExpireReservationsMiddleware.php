<?php

namespace App\Http\Middleware;

use App\Models\Reservation;
use App\Services\ReservationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ExpireReservationsMiddleware
{
    public function __construct(
        private ReservationService $reservationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exécuter l'expiration des réservations en arrière-plan (non bloquant)
        $this->expireReservations();

        return $next($request);
    }

    /**
     * Expire les réservations périmées et libère le stock
     */
    protected function expireReservations(): void
    {
        try {
            // Récupérer les réservations expirées (limite à 10 pour ne pas ralentir les requêtes)
            $expiredReservations = Reservation::with(['items'])
                ->whereIn('status', ['pending', 'confirmed', 'partial_paid'])
                ->where('expiry_date', '<=', now())
                ->limit(10)
                ->get();

            if ($expiredReservations->isEmpty()) {
                return;
            }

            foreach ($expiredReservations as $reservation) {
                try {
                    // Libère le stock via les batches FIFO + marque la réservation expirée
                    $this->reservationService->expireReservation(
                        (int) $reservation->id,
                        "Expiration automatique - Date d'expiration dépassée"
                    );

                    Log::info("Réservation #{$reservation->id} expirée automatiquement (middleware)", [
                        'reservation_id' => $reservation->id,
                        'reservation_number' => $reservation->reservation_number,
                        'customer_id' => $reservation->customer_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Erreur expiration réservation #{$reservation->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Ne pas bloquer la requête en cas d'erreur
            Log::error('Erreur middleware expiration réservations', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
