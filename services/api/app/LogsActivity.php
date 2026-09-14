<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        // Log la création
        static::created(function ($model) {
            $model->logActivity('created');
        });

        // Log la mise à jour
        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        // Log la suppression
        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity(string $action, array $customData = [])
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $modelName = class_basename($this);
        $description = $this->generateDescription($action, $modelName);

        $data = [
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Ajouter les valeurs selon l'action
        if ($action === 'created') {
            $data['new_values'] = $this->getLoggableAttributes();
        } elseif ($action === 'updated') {
            $data['old_values'] = $this->getOriginal();
            $data['new_values'] = $this->getChanges();
        } elseif ($action === 'deleted') {
            $data['old_values'] = $this->getLoggableAttributes();
        }

        // Merge avec données personnalisées
        $data = array_merge($data, $customData);

        ActivityLog::create($data);
    }

    protected function generateDescription(string $action, string $modelName): string
    {
        $modelNameFr = $this->getModelNameInFrench($modelName);
        $identifier = $this->getIdentifier();

        return match ($action) {
            'created' => "a créé {$modelNameFr} {$identifier}",
            'updated' => "a modifié {$modelNameFr} {$identifier}",
            'deleted' => "a supprimé {$modelNameFr} {$identifier}",
            default => "a effectué une action sur {$modelNameFr} {$identifier}",
        };
    }

    protected function getModelNameInFrench(string $modelName): string
    {
        // Personnaliser selon vos modèles
        return match ($modelName) {
            'Product' => 'le produit',
            'Category' => 'la catégorie',
            'Customer' => 'le client',
            'Sale' => 'la vente',
            'StockReceipt' => 'le réapprovisionnement',
            'Account' => 'le compte',
            'User' => "l'utilisateur",
            default => Str::lower($modelName),
        };
    }

    protected function getIdentifier(): string
    {
        // Retourne un identifiant lisible
        return $this->name ?? $this->title ?? $this->reference ?? "#{$this->id}";
    }

    protected function getLoggableAttributes(): array
    {
        // Retourner seulement les attributs pertinents (exclure timestamps, etc.)
        $excludedAttributes = ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];

        return collect($this->getAttributes())
            ->except($excludedAttributes)
            ->toArray();
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }
}
