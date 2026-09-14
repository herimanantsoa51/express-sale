<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class GenerateNotifications extends Command
{
    protected $signature = 'notifications:generate
                            {--type= : Type spécifique (stock_low, stock_out, reservation_expiring, credit_due)}';

    protected $description = 'Génère les notifications système (stock, réservations, crédits)';

    public function handle(NotificationService $notificationService)
    {
        $this->info('🔔 Génération des notifications...');

        $type = $this->option('type');

        if ($type) {
            $this->generateSpecificType($notificationService, $type);
        } else {
            $this->generateAll($notificationService);
        }

        return Command::SUCCESS;
    }

    private function generateAll(NotificationService $service)
    {
        $stats = $service->generateAllNotifications();

        $this->newLine();
        $this->info('✅ Notifications générées :');
        $this->table(
            ['Type', 'Nombre'],
            [
                ['Stock faible', $stats['stock_low']],
                ['Stock épuisé', $stats['stock_out']],
                ['Réservations expirant', $stats['reservation_expiring']],
                ['Échéances crédits', $stats['credit_due']],
            ]
        );

        $total = array_sum($stats);
        $this->info("📊 Total: {$total} notifications créées");
    }

    private function generateSpecificType(NotificationService $service, string $type)
    {
        $count = match ($type) {
            'stock_low' => $service->generateStockLowNotifications(),
            'stock_out' => $service->generateStockOutNotifications(),
            'reservation_expiring' => $service->generateReservationExpiringNotifications(),
            'credit_due' => $service->generateCreditDueNotifications(),
            default => throw new \InvalidArgumentException("Type invalide: {$type}")
        };

        $this->info("✅ {$count} notifications '{$type}' créées");
    }
}
