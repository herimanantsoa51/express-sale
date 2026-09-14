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
        // Supprimer l'ancienne contrainte qui force amount > 0
        DB::statement('ALTER TABLE account_transactions DROP CONSTRAINT IF EXISTS account_transactions_amount_check');

        // Créer une nouvelle contrainte qui accepte les montants positifs ET négatifs
        // mais interdit les montants nuls (amount != 0)
        DB::statement('ALTER TABLE account_transactions ADD CONSTRAINT account_transactions_amount_check CHECK (amount != 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte modifiée
        DB::statement('ALTER TABLE account_transactions DROP CONSTRAINT IF EXISTS account_transactions_amount_check');

        // Restaurer l'ancienne contrainte (amount > 0)
        DB::statement('ALTER TABLE account_transactions ADD CONSTRAINT account_transactions_amount_check CHECK (amount > 0)');
    }
};
