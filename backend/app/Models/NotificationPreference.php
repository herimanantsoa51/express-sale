<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Modèle NotificationPreference
 */
class NotificationPreference extends Model
{
    const TYPE_STOCK_LOW = 'stock_low';
    const TYPE_STOCK_OUT = 'stock_out';
    const TYPE_RESERVATION_EXPIRING = 'reservation_expiring';
    const TYPE_CREDIT_DUE = 'credit_due';
    const TYPE_PLANNED_EXPENSE_DUE = 'planned_expense_due';

    protected $fillable = [
        'user_id',
        'notification_type',
        'reminder_interval_days',
        'enabled'
    ];

    protected $casts = [
        'reminder_interval_days' => 'integer',
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    // Méthodes statiques pour récupérer les préférences
    public static function getDefaultIntervals(): array
    {
        return [
            self::TYPE_STOCK_LOW => 1,
            self::TYPE_STOCK_OUT => 1,
            self::TYPE_RESERVATION_EXPIRING => 2,
            self::TYPE_CREDIT_DUE => 7,
            self::TYPE_PLANNED_EXPENSE_DUE => 3 
        ];
    }

    public static function getOrCreateForUser(int $userId, string $type): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $type
            ],
            [
                'reminder_interval_days' => self::getDefaultIntervals()[$type] ?? 1,
                'enabled' => true
            ]
        );
    }
}