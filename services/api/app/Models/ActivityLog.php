<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    // app/Models/ActivityLog.php

    protected $fillable = [
        'user_id',
        'action',
        'status',
        'model_type',
        'model_id',
        'description',
        'metadata',
        'frontend_path',
        'error_message',
        'error_trace',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
