<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log une activité (version complète)
     * 
     * @param string $action Action effectuée
     * @param string $description Description de l'action
     * @param array $metadata Données supplémentaires
     * @param string $status Statut (success, failed, error)
     * @param string|null $frontendPath Chemin frontend pour navigation
     * @param int|null $userId ID de l'utilisateur (si null, utilise Auth::user())
     */
    public static function log(
        string $action, 
        string $description, 
        array $metadata = [],
        string $status = 'success',
        ?string $frontendPath = null,
        ?int $userId = null
    ) {
        // Si userId n'est pas fourni, on essaie de récupérer l'utilisateur connecté
        if ($userId === null) {
            $user = Auth::user();
            $userId = $user ? $user->id : null;
        }
        
        // Si toujours pas d'utilisateur et que c'est une action qui devrait en avoir un, on skip
        // MAIS pour les logs de connexion/déconnexion, on accepte qu'il n'y ait pas d'user_id
        if ($userId === null && !in_array($action, ['login_failed', 'login_success', 'logout'])) {
            // Pas d'utilisateur authentifié et ce n'est pas une action d'auth
            return null;
        }

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'status' => $status,
            'model_type' => $metadata['model_type'] ?? null,
            'model_id' => $metadata['model_id'] ?? null,
            'description' => $description,
            'metadata' => $metadata['metadata'] ?? null,
            'frontend_path' => $frontendPath,
            'error_message' => $metadata['error_message'] ?? null,
            'error_trace' => $metadata['error_trace'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log un succès
     */
    public static function success(
        string $action, 
        string $description, 
        array $metadata = [],
        ?string $frontendPath = null,
        ?int $userId = null
    ) {
        return self::log($action, $description, $metadata, 'success', $frontendPath, $userId);
    }

    /**
     * Log un échec
     */
    public static function failed(
        string $action, 
        string $description, 
        array $metadata = [],
        ?string $frontendPath = null,
        ?int $userId = null
    ) {
        return self::log($action, $description, $metadata, 'failed', $frontendPath, $userId);
    }

    /**
     * Log une erreur avec exception
     */
    public static function error(
        string $action, 
        string $description, 
        \Exception $exception, 
        array $metadata = [],
        ?int $userId = null
    ) {
        $metadata['error_message'] = $exception->getMessage();
        $metadata['error_trace'] = $exception->getTraceAsString();
        
        return self::log($action, $description, $metadata, 'error', null, $userId);
    }

    /**
     * Créer un log directement avec ActivityLog::create (pour plus de contrôle)
     * Utile pour les cas où on veut bypass la logique du helper
     */
    public static function createDirect(array $data)
    {
        $defaults = [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
        ];

        return ActivityLog::create(array_merge($defaults, $data));
    }
}