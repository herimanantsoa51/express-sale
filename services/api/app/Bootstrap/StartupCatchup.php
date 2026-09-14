<?php

namespace App\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StartupCatchup
{
    public function bootstrap(Application $app)
    {
        // Vérifier si c'est un vrai démarrage (pas une commande artisan)
        if (php_sapi_name() !== 'cli') {
            return;
        }

        // Vérifier si un rattrapage est nécessaire
        $lastCatchup = Cache::get('last_startup_catchup');
        $needsCatchup = ! $lastCatchup || now()->diffInHours($lastCatchup) > 6;

        if ($needsCatchup) {
            Log::info('🔧 Exécution du rattrapage au démarrage');

            // Exécuter le rattrapage silencieusement
            try {
                Artisan::call('tasks:catchup', [
                    '--days' => 1,
                    '--quiet' => true,
                ]);

                Cache::put('last_startup_catchup', now(), now()->addDays(1));
                Log::info('✅ Rattrapage au démarrage terminé avec succès');
            } catch (\Exception $e) {
                Log::error('❌ Erreur lors du rattrapage au démarrage: '.$e->getMessage());
            }
        }
    }
}
