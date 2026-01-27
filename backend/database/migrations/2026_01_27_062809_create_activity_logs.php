// database/migrations/xxxx_create_activity_logs_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('status')->default('success'); // 'success', 'failed', 'error'
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable(); // ⭐ Nouveau
            $table->text('error_trace')->nullable();   // ⭐ Nouveau
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};