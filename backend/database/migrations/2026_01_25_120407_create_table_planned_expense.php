<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planned_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('estimated_amount', 15, 2);
            
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->tinyInteger('day_of_week')->nullable(); // 1-7
            $table->tinyInteger('day_of_month')->nullable(); // 1-31
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date')->nullable();
            
            $table->string('recipient_name')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['is_active', 'next_due_date']);
            $table->index('frequency');
        });

        // Ajouter la colonne planned_expense_id à account_transactions (optionnel)
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->foreignId('planned_expense_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropForeign(['planned_expense_id']);
            $table->dropColumn('planned_expense_id');
        });
        
        Schema::dropIfExists('planned_expenses');
    }
};