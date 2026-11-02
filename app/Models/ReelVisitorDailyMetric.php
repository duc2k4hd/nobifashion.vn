<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelVisitorDailyMetric extends Model
{
    protected $table = 'reel_visitor_daily_metrics';

    protected $fillable = [
        'metric_date',
        'dimension_type',
        'dimension_value',
        'sessions_count',
        'total_watch_time_seconds',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'sessions_count' => 'integer',
        'total_watch_time_seconds' => 'float',
    ];
}

