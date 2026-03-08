<?php

namespace App\Services\AiProviders;

use App\Models\AiProviderConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoogleClient implements AiProviderClient
{
    public function send(AiProviderConfig $config, array $payload): array
    {
        $apiKey = $config->getDecryptedApiKey();
        if (!$apiKey) {
            throw new \RuntimeException('Clé API manquante.');
        }

        $model = $payload['model'] ?? $config->default_model;
        if (!$model) {
            throw new \RuntimeException('Modèle Google manquant.');
        }

        $baseUrl = rtrim((string) ($config->base_url ?: 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $url = $baseUrl . "/models/{$model}:generateContent?key={$apiKey}";

        $parts = [
            ['text' => $payload['prompt'] ?? ''],
        ];

        /** @var Response $response */
        $response = Http::timeout(30)->post($url, [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature' => $payload['temperature'] ?? 0.2,
                'maxOutputTokens' => $payload['max_tokens'] ?? 512,
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException($response->json('error.message', 'Erreur provider'));
        }

        $raw = $response->json();
        $content = data_get($raw, 'candidates.0.content.parts', []);
        $text = collect($content)->pluck('text')->implode("\n");
        $usage = data_get($raw, 'usageMetadata.totalTokenCount');

        return [
            'content' => is_string($text) ? $text : '',
            'raw' => $raw,
            'usage_tokens' => is_numeric($usage) ? (int) $usage : null,
            'model' => $model,
        ];
    }
}
