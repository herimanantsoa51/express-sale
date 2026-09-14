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
        // Créer la table coordinates
        Schema::create('coordinates', function (Blueprint $table) {
            $table->id();
            $table->string('country', 100)->index();
            $table->string('city', 100)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index composé pour recherche efficace
            $table->unique(['country', 'city']);
        });

        // Ajouter foreign key sur suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('coordinate_id')
                ->nullable()
                ->after('name')
                ->constrained('coordinates')
                ->nullOnDelete();

            // Supprimer l'ancienne colonne location si elle existe
            // $table->dropColumn('location');
        });

        // Ajouter foreign key sur freight_forwarders
        Schema::table('freight_forwarders', function (Blueprint $table) {
            $table->foreignId('coordinate_id')
                ->nullable()
                ->after('name')
                ->constrained('coordinates')
                ->nullOnDelete();

            // On garde location pour la compatibilité, mais coordinate_id sera prioritaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_forwarders', function (Blueprint $table) {
            $table->dropForeign(['coordinate_id']);
            $table->dropColumn('coordinate_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['coordinate_id']);
            $table->dropColumn('coordinate_id');
        });

        Schema::dropIfExists('coordinates');
    }
};
