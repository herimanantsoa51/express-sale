<?php

namespace App\Services\AiProviders;

use App\Models\AiProviderConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CohereClient implements AiProviderClient
{
    public function send(AiProviderConfig $config, array $payload): array
    {
        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            throw new \RuntimeException('Clé API manquante.');
        }

        $baseUrl = rtrim((string) ($config->base_url ?: 'https://api.cohere.ai/v1'), '/');
        $url = $baseUrl . '/chat';

        /** @var Response $response */
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post($url, [
                'model' => $payload['model'] ?? $config->default_model,
                'message' => $payload['prompt'] ?? '',
                'temperature' => $payload['temperature'] ?? 0.2,
                'max_tokens' => $payload['max_tokens'] ?? 512,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Erreur provider Cohere.');
        }

        $raw = $response->json();
        $text = $raw['text'] ?? data_get($raw, 'message.content.0.text', '');
        $usage = $raw['meta']['tokens']['input_tokens'] ?? null;

        return [
            'content' => is_string($text) ? $text : '',
            'raw' => $raw,
            'usage_tokens' => is_numeric($usage) ? (int) $usage : null,
            'model' => $raw['model'] ?? ($payload['model'] ?? null),
        ];
    }
}
