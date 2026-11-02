<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelFeedCycle extends Model
{
    protected $table = 'reel_feed_cycles';

    protected $fillable = [
        'anonymous_user_id',
        'user_id',
        'snapshot_version',
        'random_seed',
        'current_index',
        'total_delivered',
        'total_videos',
        'sequence',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'snapshot_version' => 'integer',
        'random_seed' => 'integer',
        'current_index' => 'integer',
        'total_delivered' => 'integer',
        'total_videos' => 'integer',
        'sequence' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

