<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTask extends Model
{
    protected $fillable = [
        'user_id',
        'provider_config_id',
        'status',
        'intent',
        'context',
        'tasks',
        'raw_response',
        'error_message',
        'model',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'tasks' => 'array',
        'raw_response' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerConfig(): BelongsTo
    {
        return $this->belongsTo(AiProviderConfig::class, 'provider_config_id');
    }
}
