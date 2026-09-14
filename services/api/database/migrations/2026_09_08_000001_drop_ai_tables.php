<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supprime les tables liées au service AI retiré du projet.
     */
    public function up(): void
    {
        Schema::dropIfExists('ai_tasks');
        Schema::dropIfExists('ai_provider_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('ai_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // openai, github, anthropic, google, mistral, groq, cohere, custom
            $table->string('label')->nullable();
            $table->text('api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->string('default_model')->nullable();
            $table->json('available_models')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('rate_limit_rpm')->nullable();
            $table->unsignedInteger('rate_limit_rpd')->nullable();
            $table->unsignedInteger('daily_token_limit')->nullable();
            $table->unsignedInteger('tokens_used_today')->default(0);
            $table->unsignedInteger('requests_today')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('tokens_reset_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'label']);
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_config_id')->nullable()->constrained('ai_provider_configs')->onDelete('set null');
            $table->string('status')->default('pending');
            $table->text('intent')->nullable();
            $table->json('context')->nullable();
            $table->json('tasks')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }
};
