<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
/**
 * VERSION OPTIMISÉE : Seulement si l'utilisateur est admin
 */
class AdminNotificationsMiddleware
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // Générer seulement si l'utilisateur est admin
        if ($user && $user->isAdmin()) {
            $this->generateIfNeeded();
        }

        return $next($request);
    }

    private function generateIfNeeded(): void
    {
        $cacheKey = 'notifications:last_gen';
        
        // Toutes les 30 minutes
        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, true, 1800); // 30 min
            
            try {
                $this->notificationService->generateAllNotifications();
            } catch (\Exception $e) {
                Log::error('Notification generation failed', ['error' => $e->getMessage()]);
            }
        }
    }
}