<?php

namespace App\Support;

class AiRouteManifest
{
    public static function getManifest(): array
    {
        return [
            'pages' => self::getFrontendRoutes(),
            'api' => self::getApiRoutes(),
        ];
    }

    private static function getFrontendRoutes(): array
    {
        $routes = [];
        $path = base_path('../frontend/src/App.jsx');
        if (!is_file($path)) {
            return $routes;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return $routes;
        }

        preg_match_all('/<Route\\s+path=[\"\\\']([^\"\\\']+)[\"\\\']/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $route) {
                $routes[] = $route;
            }
        }

        $routes = array_values(array_unique($routes));
        sort($routes);
        return $routes;
    }

    private static function getApiRoutes(): array
    {
        $routes = [];
        $path = base_path('routes/api.php');
        if (!is_file($path)) {
            return $routes;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return $routes;
        }

        preg_match_all('/Route::(get|post|put|patch|delete)\\(\\s*[\"\\\']([^\"\\\']+)[\"\\\']/', $content, $matches);
        if (!empty($matches[2])) {
            foreach ($matches[2] as $route) {
                $routes[] = $route;
            }
        }

        $routes = array_values(array_unique($routes));
        sort($routes);
        return $routes;
    }
}
