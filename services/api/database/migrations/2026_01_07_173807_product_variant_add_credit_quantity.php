<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Ajouter les colonnes manquantes
            if (! Schema::hasColumn('product_variants', 'credit_quantity')) {
                $table->integer('credit_quantity')->default(0)->after('reserved_quantity');
            }
            if (! Schema::hasColumn('product_variants', 'reserved_quantity')) {
                $table->integer('reserved_quantity')->default(0)->after('stock_quantity');
            }

            // Vérifier et ajouter d'autres colonnes si nécessaire
            if (! Schema::hasColumn('product_variants', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(5)->after('credit_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['credit_quantity', 'reserved_quantity', 'low_stock_threshold']);
        });
    }
};
