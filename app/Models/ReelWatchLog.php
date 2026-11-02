<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelWatchLog extends Model
{
    protected $table = 'reel_watch_logs';

    protected $fillable = [
        'session_token',
        'reel_id',
        'account_id',
        'anonymous_user_id',
        'watch_time_seconds',
        'video_duration',
        'completion_rate',
        'is_completed',
        'loop_count',
        'is_muted',
        'playback_rate',
        'interacted_like',
        'interacted_comment',
        'interacted_share',
        'interacted_save',
        'interacted_product_click',
        'ip_address',
        'device_type',
    ];

    protected $casts = [
        'watch_time_seconds' => 'float',
        'video_duration' => 'float',
        'completion_rate' => 'float',
        'is_completed' => 'boolean',
        'loop_count' => 'integer',
        'is_muted' => 'boolean',
        'playback_rate' => 'float',
        'interacted_like' => 'boolean',
        'interacted_comment' => 'boolean',
        'interacted_share' => 'boolean',
        'interacted_save' => 'boolean',
        'interacted_product_click' => 'boolean',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReelVisitorSession::class, 'session_token', 'session_token');
    }
}

