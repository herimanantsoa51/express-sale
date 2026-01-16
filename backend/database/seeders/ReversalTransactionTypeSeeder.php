<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReversalTransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier si le type REVERSAL existe déjà
        $exists = DB::table('transaction_types')
            ->where('code', 'REVERSAL')
            ->exists();

        if (!$exists) {
            DB::table('transaction_types')->insert([
                'name' => 'Annulation',
                'code' => 'REVERSAL',
                'category' => 'adjustment',
                'display_name' => 'Annulation/Contre-passation',
                'description' => 'Transaction d\'annulation/contre-passation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Type de transaction REVERSAL créé avec succès');
        } else {
            $this->command->info('Type de transaction REVERSAL existe déjà');
        }
    }
}