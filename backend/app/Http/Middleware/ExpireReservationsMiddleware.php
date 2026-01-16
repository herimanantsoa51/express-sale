<?php

namespace App\Http\Middleware;

use App\Models\Reservation;
use App\Models\ProductVariantLocation;
use App\Models\StockMovement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ExpireReservationsMiddleware
{
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
            $expiredReservations = Reservation::with(['sale.items'])
                ->whereIn('status', ['pending', 'confirmed', 'partial_paid'])
                ->where('expiry_date', '<=', now())
                ->limit(10)
                ->get();

            if ($expiredReservations->isEmpty()) {
                return;
            }

            foreach ($expiredReservations as $reservation) {
                try {
                    DB::transaction(function () use ($reservation) {
                        // Libérer le stock pour chaque article
                        foreach ($reservation->sale->items as $item) {
                            $this->releaseReservedStock(
                                $item->variant_id,
                                $item->quantity,
                                $reservation->sale->id
                            );
                        }

                        // Mettre à jour le statut de la réservation
                        $reservation->update([
                            'status' => 'expired',
                            'cancellation_reason' => 'Expiration automatique - Date d\'expiration dépassée',
                        ]);

                        // Mettre à jour le statut de la vente
                        $reservation->sale->update([
                            'payment_status' => 'cancelled',
                        ]);

                        Log::info("Réservation #{$reservation->id} expirée automatiquement (middleware)", [
                            'reservation_id' => $reservation->id,
                            'sale_number' => $reservation->sale->sale_number,
                            'customer_id' => $reservation->customer_id,
                        ]);
                    });
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

    /**
     * Libère le stock réservé et le remet à l'emplacement d'origine
     */
    protected function releaseReservedStock(int $variantId, int $quantity, int $saleId): void
    {
        // Trouver le mouvement de réservation original
        $reservationMovement = StockMovement::where('variant_id', $variantId)
            ->where('sale_id', $saleId)
            ->where('movement_type', 'reservation')
            ->first();

        if ($reservationMovement && $reservationMovement->from_location_id) {
            // Remettre le stock à l'emplacement original
            $pvl = ProductVariantLocation::firstOrCreate(
                [
                    'variant_id' => $variantId,
                    'location_id' => $reservationMovement->from_location_id,
                ],
                ['quantity' => 0]
            );

            // Augmenter le stock disponible (DÉBLOQUÉ)
            $pvl->increment('quantity', $quantity);

            // Créer un mouvement de libération automatique
            StockMovement::create([
                'variant_id' => $variantId,
                'from_location_id' => null,
                'to_location_id' => $reservationMovement->from_location_id,
                'quantity' => $quantity,
                'movement_type' => 'return',
                'sale_id' => $saleId,
                'performed_by' => null, // Automatique
                'reason' => 'reservation_expired',
                'notes' => "Réservation #{$saleId} expirée automatiquement",
            ]);

            // Recalculer le stock total
            $pvl->variant->recalculateTotalStock();
        }
    }
}
