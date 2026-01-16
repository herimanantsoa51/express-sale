<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

/**
 * Commande pour recalculer les scores de fiabilité des clients
 * 
 * Usage: php artisan customers:recalculate-scores
 * 
 * Options:
 * --customer-id=X : Recalculer uniquement pour un client spécifique
 * --adjust-limits : Ajuster aussi les limites de crédit
 */
class RecalculateCustomerScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:recalculate-scores
                          {--customer-id= : ID du client spécifique à recalculer}
                          {--adjust-limits : Ajuster aussi les limites de crédit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcule les scores de fiabilité des clients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->option('customer-id');
        $adjustLimits = $this->option('adjust-limits');
        
        if ($customerId) {
            $this->recalculateSingleCustomer($customerId, $adjustLimits);
        } else {
            $this->recalculateAllCustomers($adjustLimits);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Recalcule le score d'un seul client
     */
    private function recalculateSingleCustomer(int $customerId, bool $adjustLimits): void
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            $this->error("Client #{$customerId} introuvable");
            return;
        }
        
        $oldScore = $customer->reliability_score;
        $customer->recalculateReliabilityScore();
        $newScore = $customer->reliability_score;
        
        $this->info("Client: {$customer->name}");
        $this->info("Score: {$oldScore} → {$newScore}");
        
        if ($adjustLimits) {
            $oldLimit = $customer->credit_limit;
            $customer->adjustCreditLimit();
            $newLimit = $customer->credit_limit;
            $this->info("Limite crédit: {$oldLimit} Ar → {$newLimit} Ar");
        }
        
        $this->info('✓ Score recalculé avec succès');
    }
    
    /**
     * Recalcule les scores de tous les clients
     */
    private function recalculateAllCustomers(bool $adjustLimits): void
    {
        $customers = Customer::where('is_active', true)->get();
        
        $this->info("Recalcul des scores pour {$customers->count()} clients...");
        $progressBar = $this->output->createProgressBar($customers->count());
        $progressBar->start();
        
        $stats = [
            'improved' => 0,
            'declined' => 0,
            'unchanged' => 0,
        ];
        
        foreach ($customers as $customer) {
            $oldScore = $customer->reliability_score;
            $customer->recalculateReliabilityScore();
            $newScore = $customer->reliability_score;
            
            // Statistiques
            if ($newScore > $oldScore) {
                $stats['improved']++;
            } elseif ($newScore < $oldScore) {
                $stats['declined']++;
            } else {
                $stats['unchanged']++;
            }
            
            if ($adjustLimits) {
                $customer->adjustCreditLimit();
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Afficher les statistiques
        $this->info('✓ Recalcul terminé');
        $this->newLine();
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['Améliorés', $stats['improved']],
                ['Détériorés', $stats['declined']],
                ['Inchangés', $stats['unchanged']],
            ]
        );
    }
}