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
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->renameColumn('yen_rate', 'yuan_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->renameColumn('yuan_rate', 'yen_rate');
        });
    }
};
