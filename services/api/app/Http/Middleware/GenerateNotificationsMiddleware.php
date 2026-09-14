<?php

namespace App\Http\Middleware;

use App\Services\NotificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Middleware qui génère les notifications automatiquement
 * lors d'appels API, avec throttling pour éviter surcharge
 */
class GenerateNotificationsMiddleware
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // Exécuter la génération AVANT de traiter la requête (non-bloquant)
        $this->generateNotificationsIfNeeded();

        return $next($request);
    }

    /**
     * Génère les notifications si l'intervalle est écoulé
     */
    private function generateNotificationsIfNeeded(): void
    {
        // Clé de cache pour le throttling
        $cacheKey = 'notifications:last_generation';

        // Intervalle en minutes (ajustable)
        $intervalMinutes = config('notifications.generation_interval', 60);

        // Vérifier si on doit générer
        if ($this->shouldGenerate($cacheKey, $intervalMinutes)) {
            // Générer en arrière-plan pour ne pas bloquer la requête
            $this->generateInBackground();

            // Marquer comme généré
            Cache::put($cacheKey, now(), now()->addMinutes($intervalMinutes));
        }
    }

    /**
     * Vérifie si on doit générer les notifications
     */
    private function shouldGenerate(string $cacheKey, int $intervalMinutes): bool
    {
        $lastGeneration = Cache::get($cacheKey);

        if (! $lastGeneration) {
            return true;
        }

        // Vérifier si l'intervalle est écoulé
        return now()->diffInMinutes($lastGeneration) >= $intervalMinutes;
    }

    /**
     * Génère les notifications sans bloquer la requête
     */
    private function generateInBackground(): void
    {
        try {
            // Option 1 : Génération rapide synchrone (recommandé si rapide)
            $this->notificationService->generateAllNotifications();

            // Option 2 : Si vous avez des queues (plus robuste)
            // dispatch(new GenerateNotificationsJob())->onQueue('notifications');

        } catch (\Exception $e) {
            // Logger l'erreur sans interrompre la requête
            Log::error('Erreur génération notifications middleware', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
