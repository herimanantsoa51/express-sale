<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ProductVariantLocation;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
    public function handle()
    {
        $this->info('🔍 Recherche des réservations expirées...');

        // Récupérer toutes les réservations expirées qui ne sont pas encore marquées comme telles
        $expiredReservations = Reservation::with(['sale.items'])
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

                    Log::info("Réservation #{$reservation->id} expirée automatiquement", [
                        'reservation_id' => $reservation->id,
                        'sale_number' => $reservation->sale->sale_number,
                        'customer_id' => $reservation->customer_id,
                        'expiry_date' => $reservation->expiry_date,
                    ]);
                });

                $this->line("  ✅ Réservation #{$reservation->id} (Vente {$reservation->sale->sale_number}) - Stock libéré");
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
        $this->info("📊 Résumé:");
        $this->info("  ✅ Réussies: {$successCount}");
        if ($errorCount > 0) {
            $this->error("  ❌ Erreurs: {$errorCount}");
        }

        return $successCount > 0 ? 0 : 1;
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
                'performed_by' => null, // Automatique (pas d'utilisateur)
                'reason' => 'reservation_expired',
                'notes' => "Réservation #{$saleId} expirée automatiquement - Stock libéré",
            ]);

            // Recalculer le stock total
            $pvl->variant->recalculateTotalStock();
        }
    }
}
