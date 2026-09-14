<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer l'ancienne contrainte
        DB::statement('ALTER TABLE transaction_types DROP CONSTRAINT IF EXISTS transaction_types_category_check');

        // Créer la nouvelle contrainte avec 'adjustment' ajouté
        DB::statement("ALTER TABLE transaction_types ADD CONSTRAINT transaction_types_category_check CHECK (category IN ('income', 'expense', 'transfer', 'adjustment','opening_balance','refund'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte modifiée
        DB::statement('ALTER TABLE transaction_types DROP CONSTRAINT IF EXISTS transaction_types_category_check');

        // Restaurer l'ancienne contrainte (sans 'adjustment')
        DB::statement("ALTER TABLE transaction_types ADD CONSTRAINT transaction_types_category_check CHECK (category IN ('income', 'expense', 'transfer','opening_balance','refund'))");
    }
};
