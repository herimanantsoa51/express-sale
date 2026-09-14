<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedInteger('reservation_id')->nullable()->after('sale_id');
            $table->unsignedInteger('credit_id')->nullable()->after('reservation_id');

            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations')
                ->nullOnDelete();

            $table->foreign('credit_id')
                ->references('id')
                ->on('credits')
                ->nullOnDelete();

            $table->index('reservation_id');
            $table->index('credit_id');
        });

        // Remplir les nouvelles colonnes à partir des données existantes
        // Les transactions liées à des crédits passent par sale_id → credits.sale_id
        DB::statement('
            UPDATE account_transactions at
            SET credit_id = c.id
            FROM credits c
            WHERE at.sale_id = c.sale_id
              AND at.credit_id IS NULL
        ');

        // Les transactions liées à des réservations passent par sale_id → reservations.sale_id
        DB::statement('
            UPDATE account_transactions at
            SET reservation_id = r.id
            FROM reservations r
            WHERE at.sale_id = r.sale_id
              AND at.reservation_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropForeign(['credit_id']);
            $table->dropColumn(['reservation_id', 'credit_id']);
        });
    }
};
