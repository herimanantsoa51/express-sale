<?php

namespace App\Services\AiProviders;

use App\Models\AiProviderConfig;

interface AiProviderClient
{
    /**
     * @return array{content:string, raw:array, usage_tokens:int|null, model:string|null}
     */
    public function send(AiProviderConfig $config, array $payload): array;
}
