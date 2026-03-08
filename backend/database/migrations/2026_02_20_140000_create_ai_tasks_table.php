<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_config_id')->nullable()->constrained('ai_provider_configs')->nullOnDelete();
            $table->string('status')->default('queued'); // queued | processing | completed | failed
            $table->string('intent');
            $table->json('context')->nullable();
            $table->json('tasks')->nullable(); // normalized tasks returned by AI
            $table->json('raw_response')->nullable(); // provider raw response
            $table->string('error_message')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['provider_config_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tasks');
    }
};
