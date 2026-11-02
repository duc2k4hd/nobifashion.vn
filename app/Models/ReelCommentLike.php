<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelCommentLike extends Model
{
    use HasFactory;

    protected $table = 'reel_comment_likes';

    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'anonymous_user_id',
        'user_id',
        'created_at',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ReelComment::class, 'comment_id');
    }

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }
}

