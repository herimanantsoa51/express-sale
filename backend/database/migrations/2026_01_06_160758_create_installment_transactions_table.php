<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_id')->constrained('credit_installments')->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained('account_transactions')->onDelete('cascade');
            $table->decimal('amount', 12, 2); // Montant de ce paiement partiel
            $table->timestamp('payment_date');
            $table->timestamps();
            
            $table->index(['installment_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_transactions');
    }
};
