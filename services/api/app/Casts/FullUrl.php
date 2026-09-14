<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class FullUrl implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (! $value) {
            return null;
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            $baseUrl = $this->getBaseUrl();

            return rtrim($baseUrl, '/').'/storage/'.ltrim($value, '/');
        }

        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    private function getBaseUrl()
    {
        if (app()->bound('request') && request() !== null) {
            $request = request();
            $scheme = $request->getScheme();
            $host = $request->getHost();

            // 👇 Utilisez SERVER_PORT au lieu de getPort()
            $port = $request->server('SERVER_PORT') ?: $request->getPort();

            $url = "{$scheme}://{$host}";
            if ($port && ! in_array($port, [80, 443])) {
                $url .= ":{$port}";
            }

            return $url;
        }

        return config('app.url', 'http://localhost:8000');
    }
}
