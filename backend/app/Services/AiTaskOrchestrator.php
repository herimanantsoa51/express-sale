<?php

namespace App\Services;

use App\Models\AiProviderConfig;
use App\Models\AiTask;
use App\Services\AiProviders\AiProviderClient;
use App\Services\AiProviders\AnthropicClient;
use App\Services\AiProviders\CohereClient;
use App\Services\AiProviders\GoogleClient;
use App\Services\AiProviders\OpenAiCompatibleClient;
use App\Support\AiTaskSchema;
use App\Support\AiRouteManifest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class AiTaskOrchestrator
{
    public function process(AiTask $task, AiProviderConfig $config): AiTask
    {
        $task->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $payload = $this->buildPayload($task->intent, $task->context ?? [], $config);

        $result = $this->callExternalAiService($task, $config, $payload);
        if (!$result) {
            $client = $this->resolveClient($config->provider);
            $result = $client->send($config, $payload);
        }

        $tasks = $this->normalizeTasks($result['content'] ?? '');

        $task->update([
            'status' => 'completed',
            'tasks' => $tasks,
            'raw_response' => $result['raw'] ?? null,
            'model' => $result['model'] ?? null,
            'completed_at' => now(),
        ]);

        if (!empty($result['usage_tokens'])) {
            $config->incrementUsage((int) $result['usage_tokens']);
        } else {
            $config->incrementUsage();
        }

        return $task;
    }

    private function resolveClient(string $provider): AiProviderClient
    {
        return match ($provider) {
            'anthropic' => new AnthropicClient(),
            'google' => new GoogleClient(),
            'cohere' => new CohereClient(),
            default => new OpenAiCompatibleClient(),
        };
    }

    private function buildPayload(string $intent, array $context, AiProviderConfig $config): array
    {
        $schemaText = AiTaskSchema::schemaText();
        $manifest = AiRouteManifest::getManifest();
        $system = [
            'role' => 'system',
            'content' => "Tu es un planificateur de tâches.\n{$schemaText}\nTu as accès à ces routes de l'application (pages et API). Utilise-les pour les redirections ou actions. Si l'utilisateur demande d'aller sur une page, retourne une tâche ui_action redirect avec payload {\"path\":\"/chemin\"}.\nRoutes:\n" . json_encode($manifest, JSON_UNESCAPED_UNICODE) . "\nRéponds uniquement en JSON. Pas de markdown, pas de texte libre.",
        ];

        $user = [
            'role' => 'user',
            'content' => "Intent: {$intent}\nContext: " . json_encode($context, JSON_UNESCAPED_UNICODE),
        ];

        $model = $config->default_model;

        return [
            'model' => $model,
            'messages' => [$system, $user],
            'temperature' => 0.2,
            'max_tokens' => 800,
            'prompt' => "Intent: {$intent}\nContext: " . json_encode($context, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function callExternalAiService(AiTask $task, AiProviderConfig $config, array $payload): ?array
    {
        $base = config('services.ai_service.url');
        if (!$base) {
            return null;
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post(rtrim($base, '/') . '/tasks', [
                'intent' => $task->intent,
                'context' => $task->context ?? [],
                'provider' => [
                    'provider' => $config->provider,
                    'api_key' => $config->getDecryptedApiKey(),
                    'base_url' => $config->base_url,
                    'model' => $config->default_model,
                ],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return [
                'content' => $data['content'] ?? json_encode(['tasks' => $data['tasks'] ?? []]),
                'raw' => $data['raw'] ?? null,
                'usage_tokens' => $data['usage_tokens'] ?? null,
                'model' => $data['model'] ?? $config->default_model,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeTasks(string $content): array
    {
        $json = trim($content);
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```[a-zA-Z]*\\n|```$/', '', $json);
            $json = trim($json);
        }

        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['tasks']) || !is_array($data['tasks'])) {
            return [[
                'type' => 'advisor',
                'action' => 'message',
                'payload' => ['text' => $content],
            ]];
        }

        $tasks = [];
        foreach ($data['tasks'] as $task) {
            if (!is_array($task)) {
                continue;
            }
            $type = $task['type'] ?? null;
            $action = $task['action'] ?? null;
            $payload = $task['payload'] ?? [];
            if (!$type || !$action) {
                continue;
            }
            if (!AiTaskSchema::isAllowed($type, $action)) {
                continue;
            }
            $tasks[] = [
                'type' => $type,
                'action' => $action,
                'payload' => is_array($payload) ? $payload : [],
            ];
        }

        if ($tasks === []) {
            return [[
                'type' => 'advisor',
                'action' => 'message',
                'payload' => ['text' => $content],
            ]];
        }

        return $tasks;
    }
}
