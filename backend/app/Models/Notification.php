<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Notification
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'is_read',
        'read_at',
        'severity',
        'data',
        'dedup_key',
        'dismissed_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'data' => 'array'
    ];
    public $timestamps = false;
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNotDismissed($query)
    {
        return $query->whereNull('dismissed_at');
    }

    public function scopeActive($query)
    {
        return $query->unread()->notDismissed();
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    // Méthodes
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function dismiss(): void
    {
        $this->update([
            'dismissed_at' => now()
        ]);
    }

    public function isDismissed(): bool
    {
        return $this->dismissed_at !== null;
    }
}