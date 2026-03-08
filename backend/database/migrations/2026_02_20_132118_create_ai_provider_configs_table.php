<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // openai, github, anthropic, google, mistral, groq, cohere, custom
            $table->string('label')->nullable(); // Nom affiché personnalisé
            $table->text('api_key')->nullable(); // Clé API chiffrée
            $table->string('base_url')->nullable(); // URL custom (pour providers custom)
            $table->string('default_model')->nullable(); // Modèle par défaut
            $table->json('available_models')->nullable(); // Liste des modèles disponibles
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Provider par défaut de l'user
            $table->unsignedInteger('rate_limit_rpm')->nullable(); // Requêtes par minute
            $table->unsignedInteger('rate_limit_rpd')->nullable(); // Requêtes par jour
            $table->unsignedInteger('daily_token_limit')->nullable(); // Limite tokens/jour
            $table->unsignedInteger('tokens_used_today')->default(0);
            $table->unsignedInteger('requests_today')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('tokens_reset_at')->nullable();
            $table->json('metadata')->nullable(); // Config supplémentaire
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'label']);
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_configs');
    }
};
