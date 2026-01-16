<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained('account_transactions')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->timestamp('payment_date');
            $table->string('notes')->nullable();
            $table->timestamps();
            
            $table->index(['reservation_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_deposits');
    }
};
