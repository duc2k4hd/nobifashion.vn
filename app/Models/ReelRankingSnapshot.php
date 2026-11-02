<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelRankingSnapshot extends Model
{
    protected $table = 'reel_ranking_snapshots';

    protected $fillable = [
        'version',
        'total_videos',
        'trending_ids',
        'newest_ids',
        'status',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'total_videos' => 'integer',
        'trending_ids' => 'array',
        'newest_ids' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

