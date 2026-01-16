<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountType;
use App\Models\TransactionType;
use App\Models\ExpenseCategory;

class TreasurySeeder extends Seeder
{
    /**
     * Seed les données initiales pour la gestion de trésorerie
     */
    public function run(): void
    {
        $this->seedAccountTypes();
        $this->seedTransactionTypes();
        $this->seedExpenseCategories();
    }

    /**
     * Seed les types de comptes
     */
    private function seedAccountTypes(): void
    {
        $types = AccountType::getDefaultTypes();

        foreach ($types as $type) {
            AccountType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('✓ Types de comptes créés');
    }

    /**
     * Seed les types de transactions
     */
    private function seedTransactionTypes(): void
    {
        $types = TransactionType::getDefaultTypes();

        foreach ($types as $type) {
            TransactionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('✓ Types de transactions créés');
    }

    /**
     * Seed les catégories de dépenses
     */
    private function seedExpenseCategories(): void
    {
        $categories = ExpenseCategory::getDefaultCategories();

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('✓ Catégories de dépenses créées');
    }
}
