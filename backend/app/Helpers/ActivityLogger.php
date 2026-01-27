<?php

namespace App\Helpers;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;


class ActivityLogger
{
    public static function log(
        string $action, 
        string $description, 
        array $metadata = [],
        string $status = 'success',
        ?string $frontendPath = null,
        ?array $relatedLinks = null
    ) {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        return ActivityLog::create([
            'user_id' => $user->id,
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

    public static function success(
        string $action, 
        string $description, 
        array $metadata = [],
        ?string $frontendPath = null,
    ) {
        return self::log($action, $description, $metadata, 'success', $frontendPath);
    }

    public static function failed(
        string $action, 
        string $description, 
        array $metadata = [],
        ?string $frontendPath = null
    ) {
        return self::log($action, $description, $metadata, 'failed', $frontendPath);
    }

    public static function error(
        string $action, 
        string $description, 
        \Exception $exception, 
        array $metadata = []
    ) {
        $metadata['error_message'] = $exception->getMessage();
        $metadata['error_trace'] = $exception->getTraceAsString();
        
        return self::log($action, $description, $metadata, 'error');
    }
}