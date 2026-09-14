<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService
{
    public function __construct(
        private SaleService $saleService,
        private StockService $stockService,
    ) {}

    /**
     * Crée une réservation (indépendante de Sale)
     */
    public function createReservation(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $data['items'] = $this->saleService->enrichItemsWithPrices($data['items']);

            $stockValidation = $this->saleService->validateStockAvailability($data['items']);
            if (! $stockValidation['valid']) {
                throw new \Exception(implode('; ', $stockValidation['errors']));
            }

            $totals = $this->saleService->calculateTotals($data['items'], $data['discount_amount'] ?? 0);
            $depositAmount = $data['deposit_amount'] ?? 0;

            if ($depositAmount > $totals['total']) {
                throw new \Exception("L'acompte ne peut pas dépasser le montant total");
            }

            // Garde-fou : un acompte versé doit être encaissé sur un compte
            if ($depositAmount > 0 && empty($data['account_id'])) {
                throw new \Exception("Le compte de trésorerie (account_id) est requis pour encaisser l'acompte");
            }

            // Distribuer la remise si applicable
            if (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
                $data['items'] = $this->saleService->distributeDiscount($data['items'], $data['discount_amount']);
            }

            // Créer la réservation directement (SANS Sale)
            $reservation = Reservation::create([
                'reservation_number' => Reservation::generateNumber(),
                'customer_id' => $data['customer_id'],
                'user_id' => Auth::id(),
                'reservation_date' => now(),
                'expiry_date' => Carbon::parse($data['expiry_date']),
                'total_amount' => $totals['total'],
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_reason' => $data['discount_reason'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'deposit_amount' => $depositAmount,
                'remaining_amount' => $totals['total'] - $depositAmount,
                'status' => $depositAmount > 0 ? 'confirmed' : 'pending',
            ]);

            // Créer les items propres à la réservation
            foreach ($data['items'] as $item) {
                $reservationItem = ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'variant_id' => $item['variant_id'],
                    'location_id' => $item['location_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // FIFO avec la nouvelle méthode dédiée
                $this->stockService->consumeFifoForReservation(
                    reservationItemId: $reservationItem->id,
                    variantId: $item['variant_id'],
                    locationId: $item['location_id'],
                    quantityToSell: $item['quantity'],
                    unitPriceAtSale: $item['unit_price'],
                    discount: $item['discount_amount'] ?? 0,
                );
            }

            // Transaction financière pour l'acompte
            if ($depositAmount > 0 && isset($data['account_id'])) {
                $this->saleService->createReservationTransaction(
                    $reservation,
                    $data['account_id'],
                    $depositAmount,
                    "Acompte réservation #{$reservation->reservation_number}",
                );
            }

            Log::info('Réservation créée (indépendante)', [
                'reservation_id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
            ]);

            return $reservation->load(['items.variant.product', 'customer', 'user', 'transactions']);
        });
    }

    /**
     * Finaliser une réservation (paiement final)
     */
    public function completeReservation(int $reservationId, array $data): Reservation
    {
        return DB::transaction(function () use ($reservationId, $data) {
            $reservation = Reservation::with(['items', 'customer'])->findOrFail($reservationId);

            $remainingAmount = $reservation->remaining_amount;

            $transaction = $this->saleService->createReservationTransaction(
                $reservation,
                $data['account_id'],
                $remainingAmount,
                "Paiement final réservation #{$reservation->reservation_number}",
                $data['notes'] ?? '',
            );

            if (! $transaction || ! $transaction->id) {
                throw new \Exception('Erreur lors de la création de la transaction');
            }

            $reservation->deposit_amount += $remainingAmount;
            $reservation->remaining_amount = 0;
            $reservation->transaction_complete_id = $transaction->id;
            $reservation->status = 'completed';
            $reservation->completed_at = now();
            $reservation->save();

            // Finaliser tous les items réservés (reserved → sold)
            foreach ($reservation->items as $item) {
                $this->stockService->finalizeReservationFifo($item->id);
            }

            $reservation->customer->recordPurchase($reservation->total_amount);

            Log::info('Réservation complétée', [
                'reservation_id' => $reservation->id,
            ]);

            return $reservation->load(['items.variant.product', 'transactions', 'customer']);
        });
    }

    /**
     * Annuler une réservation : marque uniquement la réservation comme annulée.
     *
     * Politique métier : AUCUN automatisme au-delà du statut — ni remboursement
     * de l'acompte, ni libération du stock réservé. Ces opérations sont faites
     * manuellement par l'opérateur (transaction inverse, ajustement de stock).
     */
    public function cancelReservation(int $reservationId, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = Reservation::with('customer')->findOrFail($reservationId);

            $reservation->status = 'cancelled';
            $reservation->cancellation_reason = $reason;
            $reservation->save();

            $reservation->customer->recordCancelledReservation();

            Log::info('Réservation annulée', [
                'reservation_id' => $reservation->id,
                'reason' => $reason,
            ]);

            return $reservation->load(['customer']);
        });
    }

    /**
     * Expirer une réservation : marque uniquement la réservation comme expirée.
     * Aucun automatisme : le stock réservé n'est pas libéré et l'acompte n'est
     * pas remboursé — régularisation manuelle par l'opérateur.
     */
    public function expireReservation(int $reservationId, string $reason = "Expiration automatique - Date d'expiration dépassée"): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = Reservation::findOrFail($reservationId);

            $reservation->status = 'expired';
            $reservation->cancellation_reason = $reason;
            $reservation->save();

            Log::info('Réservation expirée', [
                'reservation_id' => $reservation->id,
                'reservation_number' => $reservation->reservation_number,
                'reason' => $reason,
            ]);

            return $reservation->load(['customer']);
        });
    }
}
