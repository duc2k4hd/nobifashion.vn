<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReelVisitorSession extends Model
{
    protected $table = 'reel_visitor_sessions';

    protected $fillable = [
        'session_token',
        'anonymous_user_id',
        'account_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'screen_resolution',
        'referrer_url',
        'landing_page',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'total_reels_viewed',
        'total_watch_time_seconds',
        'first_activity_at',
        'last_activity_at',
    ];

    protected $casts = [
        'first_activity_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'total_reels_viewed' => 'integer',
        'total_watch_time_seconds' => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }

    public function watchLogs(): HasMany
    {
        return $this->hasMany(ReelWatchLog::class, 'session_token', 'session_token');
    }
}

