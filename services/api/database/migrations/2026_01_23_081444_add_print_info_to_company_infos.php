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
        Schema::table('company_infos', function (Blueprint $table) {
            // Paramètres imprimante
            $table->string('printer_path')->default('/dev/usb/lp0')->after('invoice_signature');

            // Impression automatique
            $table->boolean('auto_print_immediate_sale')->default(false)->after('printer_path');
            $table->boolean('auto_print_credit')->default(false)->after('auto_print_immediate_sale');
            $table->boolean('auto_print_credit_payment')->default(false)->after('auto_print_credit');
            $table->boolean('auto_print_reservation')->default(false)->after('auto_print_credit_payment');
            $table->boolean('auto_print_reservation_complete')->default(false)->after('auto_print_reservation');
            $table->boolean('auto_print_cash_count')->default(false)->after('auto_print_reservation_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_infos', function (Blueprint $table) {
            $table->dropColumn([
                'printer_path',
                'auto_print_immediate_sale',
                'auto_print_credit',
                'auto_print_credit_payment',
                'auto_print_reservation',
                'auto_print_reservation_complete',
                'auto_print_cash_count',
            ]);
        });
    }
};
