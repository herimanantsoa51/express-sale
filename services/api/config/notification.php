<?php

// config/notifications.php

return [
    /*
    |--------------------------------------------------------------------------
    | Intervalle de génération automatique
    |--------------------------------------------------------------------------
    |
    | Intervalle en MINUTES entre chaque génération de notifications
    | Recommandé : 30-60 minutes pour ne pas surcharger
    |
    */
    'generation_interval' => env('NOTIFICATION_GENERATION_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Méthode de génération
    |--------------------------------------------------------------------------
    |
    | Options:
    | - 'middleware' : Génération via middleware sur API calls (recommandé)
    | - 'cron' : Génération via scheduler Laravel
    | - 'manual' : Seulement génération manuelle
    |
    */
    'generation_method' => env('NOTIFICATION_GENERATION_METHOD', 'middleware'),

    /*
    |--------------------------------------------------------------------------
    | Routes surveillées pour génération
    |--------------------------------------------------------------------------
    |
    | Liste des routes (patterns) qui déclenchent la génération
    | null = toutes les routes API
    |
    */
    'watched_routes' => [
        'api/notifications*',
        'api/dashboard*',
        'api/sales*',
        'api/products*',
        // Ajoutez les routes principales de votre app
    ],

    /*
    |--------------------------------------------------------------------------
    | Génération seulement pour admins
    |--------------------------------------------------------------------------
    |
    | Si true, génère seulement quand un admin fait une requête
    |
    */
    'admin_only_generation' => env('NOTIFICATION_ADMIN_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Intervalles par défaut (jours)
    |--------------------------------------------------------------------------
    |
    | Nombre de jours entre rappels par type de notification
    |
    */
    'default_intervals' => [
        'stock_low' => 1,
        'stock_out' => 1,
        'reservation_expiring' => 2,
        'credit_due' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seuils de notifications
    |--------------------------------------------------------------------------
    |
    | Jours avant expiration pour notifier
    |
    */
    'thresholds' => [
        'reservation_expiring_days' => 3, // Notifier 3 jours avant expiration
        'credit_due_days' => 7,           // Notifier 7 jours avant échéance
    ],

    /*
    |--------------------------------------------------------------------------
    | Nettoyage automatique
    |--------------------------------------------------------------------------
    |
    | Supprimer automatiquement les notifications anciennes
    |
    */
    'auto_cleanup' => [
        'enabled' => true,
        'retention_days' => 90, // Garder 90 jours
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Optimisations de performance
    |
    */
    'performance' => [
        // Utiliser queues pour génération async
        'use_queues' => env('NOTIFICATION_USE_QUEUES', false),

        // Timeout max pour génération (secondes)
        'max_execution_time' => 10,

        // Batch size pour traitement
        'batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour throttling
    |
    */
    'cache' => [
        'driver' => env('NOTIFICATION_CACHE_DRIVER', 'redis'), // redis ou file
        'prefix' => 'notifications:',
    ],
];
