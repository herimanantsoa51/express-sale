<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            // Ajouter la colonne pour tracer les annulations
            $table->foreignId('reversed_transaction_id')
                ->nullable()
                ->after('related_transaction_id')
                ->constrained('account_transactions')
                ->onDelete('set null')
                ->comment('Transaction originale annulée par cette transaction');

            // Ajouter un index pour les recherches
            $table->index('reversed_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropForeign(['reversed_transaction_id']);
            $table->dropIndex(['reversed_transaction_id']);
            $table->dropColumn('reversed_transaction_id');
        });
    }
};
