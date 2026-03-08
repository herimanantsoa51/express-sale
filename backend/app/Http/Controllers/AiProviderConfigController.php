<?php

namespace App\Http\Controllers;

use App\Models\AiProviderConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AiProviderConfigController extends Controller
{
    /**
     * GET /api/ai-providers — Liste des configs AI de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        $configs = AiProviderConfig::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('provider')
            ->get()
            ->map(fn($c) => $this->formatConfig($c));

        return response()->json([
            'configs' => $configs,
            'providers' => AiProviderConfig::getProviderDefinitions(),
        ]);
    }

    /**
     * POST /api/ai-providers — Créer une config AI
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|max:50',
            'label' => 'nullable|string|max:100',
            'api_key' => 'nullable|string|max:500',
            'base_url' => 'nullable|url|max:500',
            'default_model' => 'nullable|string|max:100',
            'available_models' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'rate_limit_rpm' => 'nullable|integer|min:0',
            'rate_limit_rpd' => 'nullable|integer|min:0',
            'daily_token_limit' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $request->user()->id;

        // Si c'est le provider par défaut, enlever le flag des autres
        if (!empty($data['is_default'])) {
            AiProviderConfig::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        // Appliquer les limites par défaut si non spécifiées
        $definitions = AiProviderConfig::getProviderDefinitions();
        if (isset($definitions[$data['provider']])) {
            $def = $definitions[$data['provider']];
            $data['base_url'] = $data['base_url'] ?? $def['base_url'];
            $data['rate_limit_rpm'] = $data['rate_limit_rpm'] ?? $def['default_limits']['rpm'];
            $data['rate_limit_rpd'] = $data['rate_limit_rpd'] ?? $def['default_limits']['rpd'];
            $data['daily_token_limit'] = $data['daily_token_limit'] ?? $def['default_limits']['tokens'];
        }

        $config = AiProviderConfig::create($data);

        return response()->json([
            'message' => 'Configuration AI créée',
            'config' => $this->formatConfig($config->fresh()),
        ], 201);
    }

    /**
     * GET /api/ai-providers/{id} — Détail d'une config
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'config' => $this->formatConfig($config),
        ]);
    }

    /**
     * PUT /api/ai-providers/{id} — Modifier une config
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'label' => 'nullable|string|max:100',
            'api_key' => 'nullable|string|max:500',
            'base_url' => 'nullable|url|max:500',
            'default_model' => 'nullable|string|max:100',
            'available_models' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'rate_limit_rpm' => 'nullable|integer|min:0',
            'rate_limit_rpd' => 'nullable|integer|min:0',
            'daily_token_limit' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Si on met is_default, enlever le flag des autres
        if (!empty($data['is_default'])) {
            AiProviderConfig::where('user_id', $request->user()->id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        // Ne pas écraser api_key si non envoyée
        if (!array_key_exists('api_key', $data) || $data['api_key'] === null) {
            unset($data['api_key']);
        }

        $config->update($data);

        return response()->json([
            'message' => 'Configuration AI mise à jour',
            'config' => $this->formatConfig($config->fresh()),
        ]);
    }

    /**
     * DELETE /api/ai-providers/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $config->delete();

        return response()->json(['message' => 'Configuration AI supprimée']);
    }

    /**
     * POST /api/ai-providers/{id}/test — Tester la connexion à un provider
     */
    public function testConnection(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Clé API non configurée',
            ], 400);
        }

        try {
            $result = $this->performTestRequest($config->provider, $apiKey, $config->base_url, $config->default_model);
            if (!empty($result['models']) && is_array($result['models'])) {
                $this->updateModels($config, $result['models']);
            }
            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie !',
                'details' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de connexion : ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/ai-providers/{id}/refresh-models — Récupérer les modèles depuis l'API
     */
    public function refreshModels(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Clé API non configurée',
            ], 400);
        }

        try {
            $models = $this->fetchModels($config);
            if (empty($models)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun modèle retourné par le provider.',
                ], 400);
            }

            $this->updateModels($config, $models);

            return response()->json([
                'success' => true,
                'message' => 'Modèles mis à jour',
                'config' => $this->formatConfig($config->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec récupération modèles : ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/ai-providers/{id}/set-default — Définir comme provider par défaut
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        AiProviderConfig::where('user_id', $request->user()->id)
            ->update(['is_default' => false]);

        $config->update(['is_default' => true]);

        return response()->json([
            'message' => 'Provider par défaut mis à jour',
            'config' => $this->formatConfig($config->fresh()),
        ]);
    }

    /**
     * POST /api/ai-providers/{id}/reset-usage — Réinitialiser les compteurs
     */
    public function resetUsage(Request $request, int $id): JsonResponse
    {
        $config = AiProviderConfig::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $config->resetDailyUsage();

        return response()->json([
            'message' => 'Compteurs réinitialisés',
            'config' => $this->formatConfig($config->fresh()),
        ]);
    }

    /**
     * GET /api/ai-providers/definitions — Retourne les définitions de tous les providers connus
     */
    public function definitions(): JsonResponse
    {
        return response()->json([
            'providers' => AiProviderConfig::getProviderDefinitions(),
        ]);
    }

    // ── Privé ──

    private function formatConfig(AiProviderConfig $config): array
    {
        $definitions = AiProviderConfig::getProviderDefinitions();
        $providerDef = $definitions[$config->provider] ?? null;

        return [
            'id' => $config->id,
            'provider' => $config->provider,
            'provider_name' => $providerDef['name'] ?? ucfirst($config->provider),
            'provider_color' => $providerDef['color'] ?? '#6b7280',
            'provider_icon' => $providerDef['icon'] ?? 'custom',
            'is_paid' => $providerDef['is_paid'] ?? false,
            'label' => $config->label,
            'has_key' => $config->has_key,
            'masked_key' => $config->masked_key,
            'base_url' => $config->base_url,
            'default_model' => $config->default_model,
            'available_models' => $config->available_models ?? ($providerDef['models'] ?? []),
            'is_active' => $config->is_active,
            'is_default' => $config->is_default,
            'rate_limit_rpm' => $config->rate_limit_rpm,
            'rate_limit_rpd' => $config->rate_limit_rpd,
            'daily_token_limit' => $config->daily_token_limit,
            'tokens_used_today' => $config->tokens_used_today,
            'requests_today' => $config->requests_today,
            'usage_percentage' => $config->usage_percentage,
            'requests_percentage' => $config->requests_percentage,
            'is_rate_limited' => $config->isRateLimited(),
            'last_used_at' => $config->last_used_at?->toISOString(),
            'tokens_reset_at' => $config->tokens_reset_at?->toISOString(),
            'metadata' => $config->metadata,
            'created_at' => $config->created_at?->toISOString(),
            'updated_at' => $config->updated_at?->toISOString(),
        ];
    }

    private function performTestRequest(string $provider, string $apiKey, ?string $baseUrl, ?string $model): array
    {
        $timeout = 15;

        switch ($provider) {
            case 'openai':
            case 'groq':
                $defaultUrl = $provider === 'groq'
                    ? 'https://api.groq.com/openai/v1'
                    : 'https://api.openai.com/v1';
                $url = ($baseUrl ?: $defaultUrl) . '/models';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($url);
                if ($response->successful()) {
                    $models = collect($response->json('data', []))->pluck('id')->filter()->values()->toArray();
                    return [
                        'models_found' => count($models),
                        'sample' => collect($models)->take(5)->toArray(),
                        'models' => $this->normalizeModelsList($models),
                    ];
                }
                throw new \Exception($response->json('error.message', 'Erreur inconnue'));

            case 'github':
                $url = 'https://models.inference.ai.azure.com/chat/completions';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->post($url, [
                    'model' => $model ?? 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'Say "ok" in one word.']],
                    'max_tokens' => 5,
                ]);
                if ($response->successful()) {
                    return ['status' => 'connected', 'model_tested' => $model ?? 'gpt-4o-mini'];
                }
                throw new \Exception($response->json('error.message', 'Erreur GitHub Models'));

            case 'anthropic':
                $url = ($baseUrl ?: 'https://api.anthropic.com') . '/v1/messages';
                /** @var Response $response */
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->timeout($timeout)->post($url, [
                    'model' => $model ?? 'claude-3-5-haiku-20241022',
                    'max_tokens' => 5,
                    'messages' => [['role' => 'user', 'content' => 'Say ok.']],
                ]);
                if ($response->successful()) {
                    return ['status' => 'connected', 'model_tested' => $model ?? 'claude-3-5-haiku-20241022'];
                }
                throw new \Exception($response->json('error.message', 'Erreur Anthropic'));

            case 'google':
                $testModel = $model ?? 'gemini-1.5-flash';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$testModel}:generateContent?key={$apiKey}";
                /** @var Response $response */
                $response = Http::timeout($timeout)->post($url, [
                    'contents' => [['parts' => [['text' => 'Say ok.']]]],
                    'generationConfig' => ['maxOutputTokens' => 5],
                ]);
                if ($response->successful()) {
                    return ['status' => 'connected', 'model_tested' => $testModel];
                }
                throw new \Exception($response->json('error.message', 'Erreur Google'));

            case 'mistral':
                $url = ($baseUrl ?: 'https://api.mistral.ai/v1') . '/models';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($url);
                if ($response->successful()) {
                    $models = collect($response->json('data', []))->pluck('id')->filter()->values()->toArray();
                    return [
                        'models_found' => count($models),
                        'sample' => collect($models)->take(5)->toArray(),
                        'models' => $this->normalizeModelsList($models),
                    ];
                }
                throw new \Exception($response->json('message', 'Erreur Mistral'));

            case 'cohere':
                $url = ($baseUrl ?: 'https://api.cohere.ai/v1') . '/models';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($url);
                if ($response->successful()) {
                    $models = collect($response->json('models', []))
                        ->pluck('name')
                        ->filter()
                        ->values()
                        ->toArray();
                    return [
                        'status' => 'connected',
                        'models' => $this->normalizeModelsList($models),
                    ];
                }
                throw new \Exception('Erreur Cohere : ' . $response->status());

            default:
                // Custom provider - test basique
                if (!$baseUrl) {
                    throw new \Exception('Base URL requise pour les providers custom');
                }
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($baseUrl . '/models');
                if ($response->successful()) {
                    $models = collect($response->json('data', []))->pluck('id')->filter()->values()->toArray();
                    return [
                        'status' => 'connected',
                        'models' => $this->normalizeModelsList($models),
                    ];
                }
                throw new \Exception('Erreur : HTTP ' . $response->status());
        }
    }

    private function fetchModels(AiProviderConfig $config): array
    {
        $timeout = 20;
        $provider = $config->provider;
        $apiKey = $config->getDecryptedApiKey();
        $baseUrl = $config->base_url;

        switch ($provider) {
            case 'openai':
            case 'groq':
            case 'mistral':
            case 'custom': {
                $defaultUrl = match ($provider) {
                    'groq' => 'https://api.groq.com/openai/v1',
                    'mistral' => 'https://api.mistral.ai/v1',
                    'openai' => 'https://api.openai.com/v1',
                    default => null,
                };
                $url = rtrim((string) ($baseUrl ?: $defaultUrl), '/') . '/models';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($url);
                if (!$response->successful()) {
                    throw new \Exception($response->json('error.message', 'Erreur inconnue'));
                }
                $models = collect($response->json('data', []))->pluck('id')->filter()->values()->toArray();
                return $this->normalizeModelsList($models);
            }
            case 'cohere': {
                $url = rtrim((string) ($baseUrl ?: 'https://api.cohere.ai/v1'), '/') . '/models';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($url);
                if (!$response->successful()) {
                    throw new \Exception('Erreur Cohere : ' . $response->status());
                }
                $models = collect($response->json('models', []))->pluck('name')->filter()->values()->toArray();
                return $this->normalizeModelsList($models);
            }
            case 'anthropic': {
                $url = rtrim((string) ($baseUrl ?: 'https://api.anthropic.com/v1'), '/') . '/models';
                /** @var Response $response */
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])->timeout($timeout)->get($url);
                if (!$response->successful()) {
                    throw new \Exception($response->json('error.message', 'Erreur Anthropic'));
                }
                $models = collect($response->json('data', []))->pluck('id')->filter()->values()->toArray();
                return $this->normalizeModelsList($models);
            }
            case 'google': {
                $url = rtrim((string) ($baseUrl ?: 'https://generativelanguage.googleapis.com/v1beta'), '/') . "/models?key={$apiKey}";
                /** @var Response $response */
                $response = Http::timeout($timeout)->get($url);
                if (!$response->successful()) {
                    throw new \Exception($response->json('error.message', 'Erreur Google'));
                }
                $models = collect($response->json('models', []))
                    ->map(function ($m) {
                        $name = $m['name'] ?? '';
                        $id = $name ? last(explode('/', $name)) : null;
                        return [
                            'id' => $id,
                            'name' => $m['displayName'] ?? $id,
                        ];
                    })
                    ->filter(fn ($m) => !empty($m['id']))
                    ->values()
                    ->toArray();
                return $models;
            }
            case 'github': {
                $url = rtrim((string) ($baseUrl ?: 'https://models.inference.ai.azure.com'), '/') . '/models';
                /** @var Response $response */
                $response = Http::withToken($apiKey)->timeout($timeout)->get($url);
                if ($response->successful()) {
                    $models = collect($response->json('data', []))->pluck('id')->filter()->values()->toArray();
                    return $this->normalizeModelsList($models);
                }
                // Fallback: retourner une liste vide pour forcer l'utilisateur à choisir un provider compatible
                return [];
            }
            default:
                return [];
        }
    }

    private function normalizeModelsList(array $models): array
    {
        $normalized = [];
        foreach ($models as $model) {
            if (is_array($model)) {
                $id = $model['id'] ?? $model['name'] ?? null;
                if (!$id) {
                    continue;
                }
                $normalized[] = [
                    'id' => $id,
                    'name' => $model['name'] ?? $id,
                ];
                continue;
            }
            if (is_string($model)) {
                $normalized[] = ['id' => $model, 'name' => $model];
            }
        }
        return $normalized;
    }

    private function updateModels(AiProviderConfig $config, array $models): void
    {
        $config->available_models = $models;
        if ($config->default_model) {
            $ids = collect($models)->pluck('id')->filter()->values()->toArray();
            if (!in_array($config->default_model, $ids, true)) {
                $config->default_model = $ids[0] ?? $config->default_model;
            }
        } else {
            $config->default_model = $models[0]['id'] ?? null;
        }
        $config->save();
    }
}
