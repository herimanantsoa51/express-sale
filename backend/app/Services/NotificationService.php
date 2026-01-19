<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Génère toutes les notifications système
     */
    public function generateAllNotifications(): array
    {
        $stats = [
            'stock_low' => 0,
            'stock_out' => 0,
            'reservation_expiring' => 0,
            'credit_due' => 0
        ];

        $stats['stock_low'] = $this->generateStockLowNotifications();
        $stats['stock_out'] = $this->generateStockOutNotifications();
        $stats['reservation_expiring'] = $this->generateReservationExpiringNotifications();
        $stats['credit_due'] = $this->generateCreditDueNotifications();

        return $stats;
    }

    /**
     * Génère les notifications de stock faible
     */
    public function generateStockLowNotifications(): int
    {
        $count = 0;
        $admins = $this->getAdminUsers();

        // Récupérer les variants avec stock faible ET qui ont des ventes
        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->whereRaw('stock_quantity > 0')
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->whereHas('stockReceiptItems') // A eu des mouvements de stock
            ->get();

        foreach ($admins as $admin) {
            $preference = NotificationPreference::getOrCreateForUser(
                $admin->id,
                NotificationPreference::TYPE_STOCK_LOW
            );

            if (!$preference->enabled) {
                continue;
            }

            foreach ($variants as $variant) {
                if ($this->shouldNotify($admin->id, $variant->id, 'stock_low', $preference->reminder_interval_days)) {
                    $this->createStockLowNotification($admin->id, $variant);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Génère les notifications de stock épuisé
     */
    public function generateStockOutNotifications(): int
    {
        $count = 0;
        $admins = $this->getAdminUsers();

        // Variants épuisés qui ont eu des ventes
        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->where('stock_quantity', '<=', 0)
            ->whereHas('stockReceiptItems')
            ->get();

        foreach ($admins as $admin) {
            $preference = NotificationPreference::getOrCreateForUser(
                $admin->id,
                NotificationPreference::TYPE_STOCK_OUT
            );

            if (!$preference->enabled) {
                continue;
            }

            foreach ($variants as $variant) {
                if ($this->shouldNotify($admin->id, $variant->id, 'stock_out', $preference->reminder_interval_days)) {
                    $this->createStockOutNotification($admin->id, $variant);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Génère les notifications de réservations expirant bientôt
     */
    public function generateReservationExpiringNotifications(): int
    {
        $count = 0;
        $admins = $this->getAdminUsers();

        foreach ($admins as $admin) {
            $preference = NotificationPreference::getOrCreateForUser(
                $admin->id,
                NotificationPreference::TYPE_RESERVATION_EXPIRING
            );

            if (!$preference->enabled) {
                continue;
            }

            // Réservations expirant dans X jours
            $reservations = Reservation::with(['sale', 'customer'])
                ->whereIn('status', ['confirmed', 'partial_paid'])
                ->whereBetween('expiry_date', [
                    now(),
                    now()->addDays($preference->reminder_interval_days)
                ])
                ->get();

            foreach ($reservations as $reservation) {
                if ($this->shouldNotify($admin->id, $reservation->id, 'reservation_expiring', $preference->reminder_interval_days)) {
                    $this->createReservationExpiringNotification($admin->id, $reservation);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Génère les notifications d'échéances de crédit
     */
    public function generateCreditDueNotifications(): int
    {
        $count = 0;
        $admins = $this->getAdminUsers();

        foreach ($admins as $admin) {
            $preference = NotificationPreference::getOrCreateForUser(
                $admin->id,
                NotificationPreference::TYPE_CREDIT_DUE
            );

            if (!$preference->enabled) {
                continue;
            }

            // Crédits avec échéances dans X jours
            $credits = Credit::with(['sale', 'customer', 'installments'])
                ->whereIn('status', ['active', 'partial_paid'])
                ->whereHas('installments', function($q) use ($preference) {
                    $q->where('status', 'pending')
                      ->whereBetween('due_date', [
                          now(),
                          now()->addDays($preference->reminder_interval_days)
                      ]);
                })
                ->get();

            foreach ($credits as $credit) {
                if ($this->shouldNotify($admin->id, $credit->id, 'credit_due', $preference->reminder_interval_days)) {
                    $this->createCreditDueNotification($admin->id, $credit);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Vérifie si on doit notifier (deduplication)
     */
    private function shouldNotify(int $userId, int $entityId, string $type, int $intervalDays): bool
    {
        $dedupKey = "{$type}_{$entityId}";
        $cutoffDate = now()->subDays($intervalDays);

        // Chercher une notification existante non dismissée dans l'intervalle
        $exists = Notification::where('user_id', $userId)
            ->where('dedup_key', $dedupKey)
            ->whereNull('dismissed_at')
            ->where('created_at', '>', $cutoffDate)
            ->exists();

        return !$exists;
    }

    /**
     * Crée une notification de stock faible
     */
    private function createStockLowNotification(int $userId, ProductVariant $variant): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => 'stock_low',
            'title' => 'Stock faible',
            'message' => sprintf(
                'Le produit "%s" (%s) a un stock faible : %d unités (seuil: %d)',
                $variant->product->name,
                $variant->sku,
                $variant->stock_quantity,
                $variant->low_stock_threshold
            ),
            'severity' => 'warning',
            'dedup_key' => "stock_low_{$variant->id}",
            'data' => [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'sku' => $variant->sku,
                'stock_quantity' => $variant->stock_quantity,
                'threshold' => $variant->low_stock_threshold
            ]
        ]);
    }

    /**
     * Crée une notification de stock épuisé
     */
    private function createStockOutNotification(int $userId, ProductVariant $variant): void
    {
        Notification::create([
            'user_id' => $userId,
            'type' => 'stock_out',
            'title' => 'Stock épuisé',
            'message' => sprintf(
                'Le produit "%s" (%s) est en rupture de stock !',
                $variant->product->name,
                $variant->sku
            ),
            'severity' => 'critical',
            'dedup_key' => "stock_out_{$variant->id}",
            'data' => [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'sku' => $variant->sku
            ]
        ]);
    }

    /**
     * Crée une notification de réservation expirant
     */
    private function createReservationExpiringNotification(int $userId, Reservation $reservation): void
    {
        $daysLeft = now()->diffInDays($reservation->expiry_date);
        
        Notification::create([
            'user_id' => $userId,
            'type' => 'reservation_expiring',
            'title' => 'Réservation expire bientôt',
            'message' => sprintf(
                'La réservation #%s du client "%s" expire dans %d jour(s) - Montant restant: %.2f',
                $reservation->sale->sale_number,
                $reservation->customer->name,
                $daysLeft,
                $reservation->remaining_amount
            ),
            'severity' => 'info',
            'dedup_key' => "reservation_expiring_{$reservation->id}",
            'data' => [
                'reservation_id' => $reservation->id,
                'sale_id' => $reservation->sale_id,
                'customer_id' => $reservation->customer_id,
                'expiry_date' => $reservation->expiry_date->toDateTimeString(),
                'days_left' => $daysLeft,
                'remaining_amount' => (float) $reservation->remaining_amount
            ]
        ]);
    }

    /**
     * Crée une notification d'échéance de crédit
     */
    private function createCreditDueNotification(int $userId, Credit $credit): void
    {
        $nextInstallment = $credit->getNextInstallment();
        
        if (!$nextInstallment) {
            return;
        }

        $daysLeft = now()->diffInDays($nextInstallment->due_date);

        Notification::create([
            'user_id' => $userId,
            'type' => 'credit_due',
            'title' => 'Échéance de crédit proche',
            'message' => sprintf(
                'Le crédit #%s du client "%s" a une échéance dans %d jour(s) - Montant: %.2f',
                $credit->sale->sale_number,
                $credit->customer->name,
                $daysLeft,
                $nextInstallment->amount_due
            ),
            'severity' => 'warning',
            'dedup_key' => "credit_due_{$credit->id}",
            'data' => [
                'credit_id' => $credit->id,
                'sale_id' => $credit->sale_id,
                'customer_id' => $credit->customer_id,
                'installment_id' => $nextInstallment->id,
                'due_date' => $nextInstallment->due_date->toDateTimeString(),
                'days_left' => $daysLeft,
                'amount_due' => (float) $nextInstallment->amount_due
            ]
        ]);
    }

    /**
     * Récupère tous les utilisateurs admin
     */
    /**
 * Récupère tous les utilisateurs admin
 */
    private function getAdminUsers()
    {
        // Seulement par rôle (sans is_admin)
        return User::where('role', 'admin')->get();
    }
}