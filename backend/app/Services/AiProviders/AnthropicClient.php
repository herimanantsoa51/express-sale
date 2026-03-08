<?php

namespace App\Services\AiProviders;

use App\Models\AiProviderConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AnthropicClient implements AiProviderClient
{
    public function send(AiProviderConfig $config, array $payload): array
    {
        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            throw new \RuntimeException('Clé API manquante.');
        }

        $baseUrl = rtrim((string) ($config->base_url ?: 'https://api.anthropic.com/v1'), '/');
        $url = $baseUrl . '/messages';

        /** @var Response $response */
        $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(30)
            ->post($url, [
                'model' => $payload['model'] ?? $config->default_model,
                'max_tokens' => $payload['max_tokens'] ?? 512,
                'messages' => $payload['messages'] ?? [],
                'temperature' => $payload['temperature'] ?? 0.2,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException($response->json('error.message', 'Erreur provider'));
        }

        $raw = $response->json();
        $contentParts = $raw['content'] ?? [];
        $content = collect($contentParts)->pluck('text')->implode("\n");
        $model = $raw['model'] ?? ($payload['model'] ?? null);
        $usage = null;
        if (isset($raw['usage']['input_tokens']) || isset($raw['usage']['output_tokens'])) {
            $usage = (int) ($raw['usage']['input_tokens'] ?? 0) + (int) ($raw['usage']['output_tokens'] ?? 0);
        }

        return [
            'content' => is_string($content) ? $content : '',
            'raw' => $raw,
            'usage_tokens' => $usage,
            'model' => $model,
        ];
    }
}
