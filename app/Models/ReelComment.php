<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReelComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reel_comments';

    protected $fillable = [
        'reel_id',
        'anonymous_user_id',
        'user_id',
        'parent_id',
        'author_name',
        'content',
        'comment_type',
        'sticker_id',
        'likes_count',
        'replies_count',
        'status',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'replies_count' => 'integer',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }

    public function sticker(): BelongsTo
    {
        return $this->belongsTo(CommentSticker::class, 'sticker_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ReelComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReelComment::class, 'parent_id')
            ->where('status', 'approved')
            ->with(['anonymousUser', 'sticker'])
            ->oldest('id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReelCommentLike::class, 'comment_id');
    }
}
