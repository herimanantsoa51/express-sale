<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class SetDynamicUrl
{
    public function handle($request, Closure $next)
    {
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        
        $baseUrl = "{$scheme}://{$host}";
        if ($port && !in_array($port, [80, 443])) {
            $baseUrl .= ":{$port}";
        }
        
        // Met à jour la config pour cette requête
        Config::set('app.url', $baseUrl);
        URL::forceRootUrl($baseUrl);
        
        return $next($request);
    }
}