<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelDailyMetric extends Model
{
    protected $table = 'reel_daily_metrics';

    protected $fillable = [
        'metric_date',
        'reel_id',
        'total_views',
        'completed_views',
        'total_watch_time_seconds',
        'avg_completion_rate',
        'total_loops',
        'total_likes',
        'total_comments',
        'total_shares',
        'total_saves',
        'total_product_clicks',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'total_views' => 'integer',
        'completed_views' => 'integer',
        'total_watch_time_seconds' => 'float',
        'avg_completion_rate' => 'float',
        'total_loops' => 'integer',
        'total_likes' => 'integer',
        'total_comments' => 'integer',
        'total_shares' => 'integer',
        'total_saves' => 'integer',
        'total_product_clicks' => 'integer',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }
}

