<?php

namespace App\Services\AiProviders;

use App\Models\AiProviderConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAiCompatibleClient implements AiProviderClient
{
    public function send(AiProviderConfig $config, array $payload): array
    {
        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            throw new \RuntimeException('Clé API manquante.');
        }

        $baseUrl = rtrim((string) ($config->base_url ?: 'https://api.openai.com/v1'), '/');
        $url = $baseUrl . '/chat/completions';

        /** @var Response $response */
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post($url, [
                'model' => $payload['model'] ?? $config->default_model,
                'messages' => $payload['messages'] ?? [],
                'temperature' => $payload['temperature'] ?? 0.2,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException($response->json('error.message', 'Erreur provider'));
        }

        $raw = $response->json();
        $content = data_get($raw, 'choices.0.message.content', '');
        $model = $raw['model'] ?? ($payload['model'] ?? null);
        $usage = $raw['usage']['total_tokens'] ?? null;

        return [
            'content' => is_string($content) ? $content : '',
            'raw' => $raw,
            'usage_tokens' => $usage,
            'model' => $model,
        ];
    }
}
