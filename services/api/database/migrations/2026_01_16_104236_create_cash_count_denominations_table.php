<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_count_denominations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cash_count_id')
                ->constrained('cash_counts')
                ->cascadeOnDelete();

            // Valeur faciale : 100, 200, 500, 1000, 20000...
            $table->unsignedInteger('denomination');

            // Nombre de billets/pièces
            $table->unsignedInteger('quantity');

            // denomination * quantity
            $table->unsignedBigInteger('subtotal');

            $table->timestamps();

            // Un seul enregistrement par coupure et par comptage
            $table->unique(['cash_count_id', 'denomination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_count_denominations');
    }
};
