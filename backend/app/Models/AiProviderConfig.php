<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AiProviderConfig extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'label',
        'api_key',
        'base_url',
        'default_model',
        'available_models',
        'is_active',
        'is_default',
        'rate_limit_rpm',
        'rate_limit_rpd',
        'daily_token_limit',
        'tokens_used_today',
        'requests_today',
        'last_used_at',
        'tokens_reset_at',
        'metadata',
    ];

    protected $casts = [
        'available_models' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
        'tokens_reset_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    // ── Chiffrement automatique de la clé API ──

    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getDecryptedApiKey(): ?string
    {
        if (!$this->attributes['api_key']) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['api_key']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retourne la clé masquée pour l'affichage (ex: sk-***...***abc)
     */
    public function getMaskedKeyAttribute(): ?string
    {
        $key = $this->getDecryptedApiKey();
        if (!$key) return null;

        $len = strlen($key);
        if ($len <= 8) return str_repeat('•', $len);

        $prefix = substr($key, 0, 4);
        $suffix = substr($key, -4);
        $middle = str_repeat('•', min($len - 8, 20));

        return "{$prefix}{$middle}{$suffix}";
    }

    /**
     * Vérifie si la clé API est configurée
     */
    public function getHasKeyAttribute(): bool
    {
        return !empty($this->attributes['api_key']);
    }

    // ── Relations ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Limites & Usage ──

    public function getUsagePercentageAttribute(): ?float
    {
        if (!$this->daily_token_limit) return null;
        return round(($this->tokens_used_today / $this->daily_token_limit) * 100, 1);
    }

    public function getRequestsPercentageAttribute(): ?float
    {
        if (!$this->rate_limit_rpd) return null;
        return round(($this->requests_today / $this->rate_limit_rpd) * 100, 1);
    }

    public function isRateLimited(): bool
    {
        if ($this->rate_limit_rpd && $this->requests_today >= $this->rate_limit_rpd) {
            return true;
        }
        if ($this->daily_token_limit && $this->tokens_used_today >= $this->daily_token_limit) {
            return true;
        }
        return false;
    }

    public function incrementUsage(int $tokensUsed = 0): void
    {
        $this->increment('requests_today');
        if ($tokensUsed > 0) {
            $this->increment('tokens_used_today', $tokensUsed);
        }
        $this->update(['last_used_at' => now()]);
    }

    public function resetDailyUsage(): void
    {
        $this->update([
            'tokens_used_today' => 0,
            'requests_today' => 0,
            'tokens_reset_at' => now(),
        ]);
    }

    // ── Providers connus avec infos par défaut ──

    public static function getProviderDefinitions(): array
    {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'icon' => 'openai',
                'color' => '#10a37f',
                'base_url' => 'https://api.openai.com/v1',
                'key_prefix' => 'sk-',
                'is_paid' => true,
                'models' => [
                    ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'context' => 128000, 'tier' => 'premium'],
                    ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'context' => 128000, 'tier' => 'standard'],
                    ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'context' => 128000, 'tier' => 'premium'],
                    ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'context' => 16385, 'tier' => 'budget'],
                    ['id' => 'o1-preview', 'name' => 'O1 Preview', 'context' => 128000, 'tier' => 'premium'],
                    ['id' => 'o1-mini', 'name' => 'O1 Mini', 'context' => 128000, 'tier' => 'standard'],
                ],
                'default_limits' => ['rpm' => 60, 'rpd' => 10000, 'tokens' => 1000000],
            ],
            'github' => [
                'name' => 'GitHub Models',
                'icon' => 'github',
                'color' => '#24292f',
                'base_url' => 'https://models.inference.ai.azure.com',
                'key_prefix' => 'ghp_',
                'is_paid' => false,
                'models' => [
                    ['id' => 'gpt-4o', 'name' => 'GPT-4o (GitHub)', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini (GitHub)', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'Meta-Llama-3.1-405B-Instruct', 'name' => 'Llama 3.1 405B', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'Meta-Llama-3.1-70B-Instruct', 'name' => 'Llama 3.1 70B', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'Mistral-Large', 'name' => 'Mistral Large', 'context' => 32000, 'tier' => 'free'],
                    ['id' => 'Phi-3.5-MoE-instruct', 'name' => 'Phi 3.5 MoE', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'AI21-Jamba-1.5-Large', 'name' => 'AI21 Jamba 1.5 Large', 'context' => 256000, 'tier' => 'free'],
                ],
                'default_limits' => ['rpm' => 15, 'rpd' => 150, 'tokens' => 150000],
            ],
            'anthropic' => [
                'name' => 'Anthropic',
                'icon' => 'anthropic',
                'color' => '#d97706',
                'base_url' => 'https://api.anthropic.com/v1',
                'key_prefix' => 'sk-ant-',
                'is_paid' => true,
                'models' => [
                    ['id' => 'claude-sonnet-4-20250514', 'name' => 'Claude Sonnet 4', 'context' => 200000, 'tier' => 'premium'],
                    ['id' => 'claude-3-5-sonnet-20241022', 'name' => 'Claude 3.5 Sonnet', 'context' => 200000, 'tier' => 'premium'],
                    ['id' => 'claude-3-5-haiku-20241022', 'name' => 'Claude 3.5 Haiku', 'context' => 200000, 'tier' => 'standard'],
                    ['id' => 'claude-3-opus-20240229', 'name' => 'Claude 3 Opus', 'context' => 200000, 'tier' => 'premium'],
                ],
                'default_limits' => ['rpm' => 60, 'rpd' => 10000, 'tokens' => 1000000],
            ],
            'google' => [
                'name' => 'Google Gemini',
                'icon' => 'google',
                'color' => '#4285f4',
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'key_prefix' => 'AI',
                'is_paid' => false,
                'models' => [
                    ['id' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash', 'context' => 1048576, 'tier' => 'free'],
                    ['id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro', 'context' => 2097152, 'tier' => 'standard'],
                    ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash', 'context' => 1048576, 'tier' => 'free'],
                    ['id' => 'gemini-1.5-flash-8b', 'name' => 'Gemini 1.5 Flash 8B', 'context' => 1048576, 'tier' => 'free'],
                ],
                'default_limits' => ['rpm' => 15, 'rpd' => 1500, 'tokens' => 1000000],
            ],
            'mistral' => [
                'name' => 'Mistral AI',
                'icon' => 'mistral',
                'color' => '#f97316',
                'base_url' => 'https://api.mistral.ai/v1',
                'key_prefix' => '',
                'is_paid' => true,
                'models' => [
                    ['id' => 'mistral-large-latest', 'name' => 'Mistral Large', 'context' => 128000, 'tier' => 'premium'],
                    ['id' => 'mistral-medium-latest', 'name' => 'Mistral Medium', 'context' => 32000, 'tier' => 'standard'],
                    ['id' => 'mistral-small-latest', 'name' => 'Mistral Small', 'context' => 32000, 'tier' => 'budget'],
                    ['id' => 'open-mixtral-8x22b', 'name' => 'Mixtral 8x22B', 'context' => 65000, 'tier' => 'standard'],
                    ['id' => 'codestral-latest', 'name' => 'Codestral', 'context' => 32000, 'tier' => 'standard'],
                ],
                'default_limits' => ['rpm' => 60, 'rpd' => 10000, 'tokens' => 500000],
            ],
            'groq' => [
                'name' => 'Groq',
                'icon' => 'groq',
                'color' => '#f55036',
                'base_url' => 'https://api.groq.com/openai/v1',
                'key_prefix' => 'gsk_',
                'is_paid' => false,
                'models' => [
                    ['id' => 'llama-3.3-70b-versatile', 'name' => 'Llama 3.3 70B', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'llama-3.1-8b-instant', 'name' => 'Llama 3.1 8B Instant', 'context' => 128000, 'tier' => 'free'],
                    ['id' => 'mixtral-8x7b-32768', 'name' => 'Mixtral 8x7B', 'context' => 32768, 'tier' => 'free'],
                    ['id' => 'gemma2-9b-it', 'name' => 'Gemma 2 9B', 'context' => 8192, 'tier' => 'free'],
                ],
                'default_limits' => ['rpm' => 30, 'rpd' => 14400, 'tokens' => 500000],
            ],
            'cohere' => [
                'name' => 'Cohere',
                'icon' => 'cohere',
                'color' => '#39594d',
                'base_url' => 'https://api.cohere.ai/v1',
                'key_prefix' => '',
                'is_paid' => true,
                'models' => [
                    ['id' => 'command-r-plus', 'name' => 'Command R+', 'context' => 128000, 'tier' => 'premium'],
                    ['id' => 'command-r', 'name' => 'Command R', 'context' => 128000, 'tier' => 'standard'],
                    ['id' => 'command-light', 'name' => 'Command Light', 'context' => 4096, 'tier' => 'budget'],
                ],
                'default_limits' => ['rpm' => 20, 'rpd' => 1000, 'tokens' => 500000],
            ],
            'custom' => [
                'name' => 'Custom / Self-hosted',
                'icon' => 'custom',
                'color' => '#8b5cf6',
                'base_url' => '',
                'key_prefix' => '',
                'is_paid' => false,
                'models' => [],
                'default_limits' => ['rpm' => null, 'rpd' => null, 'tokens' => null],
            ],
        ];
    }
}
