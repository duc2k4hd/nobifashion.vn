<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReelComment extends Model
{
    use HasFactory;

    protected $table = 'reel_comments';

    protected $fillable = [
        'reel_id',
        'user_id',
        'parent_id',
        'author_name',
        'content',
        'likes_count',
        'status',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReelComment::class, 'parent_id')
            ->where('status', 'approved')
            ->oldest('id');
    }
}

