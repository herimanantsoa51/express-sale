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
        Schema::table('customers', function (Blueprint $table) {

            // Suppression des index EXISTANTS
            $table->dropIndex('idx_customers_last_purchase');
            $table->dropIndex('idx_customers_active_score');

            // Suppression des colonnes
            $table->dropColumn([
                'first_purchase_date',
                'last_purchase_date',
                'total_purchases',
                'total_spent',
                'on_time_payments',
                'late_payments',
                'cancelled_reservations',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Dates des achats
            $table->date('first_purchase_date')
                ->nullable()
                ->after('is_active')
                ->comment('Date du premier achat du client');

            $table->date('last_purchase_date')
                ->nullable()
                ->after('first_purchase_date')
                ->comment('Date du dernier achat du client');

            // Statistiques d'achats
            $table->integer('total_purchases')
                ->default(0)
                ->after('last_purchase_date')
                ->comment('Nombre total d\'achats effectués');

            $table->decimal('total_spent', 15, 2)
                ->default(0)
                ->after('total_purchases')
                ->comment('Montant total dépensé (Ar)');

            // Compteurs de paiements pour calcul du score
            $table->integer('on_time_payments')
                ->default(0)
                ->after('total_spent')
                ->comment('Nombre de paiements effectués à temps');

            $table->integer('late_payments')
                ->default(0)
                ->after('on_time_payments')
                ->comment('Nombre de paiements en retard');

            // Compteur de réservations annulées (impact négatif sur score)
            $table->integer('cancelled_reservations')
                ->default(0)
                ->after('late_payments')
                ->comment('Nombre de réservations annulées par le client');

            // Index pour améliorer les performances des requêtes
            $table->index('last_purchase_date', 'idx_customers_last_purchase');
            $table->index(['is_active', 'reliability_score'], 'idx_customers_active_score');
        });
    }
};
