<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentModerationLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'comment_moderation_logs';

    protected $fillable = [
        'anonymous_user_id',
        'comment_id',
        'rule_id',
        'reason_code',
        'flagged_content',
        'moderation_result',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function anonymousUser(): BelongsTo
    {
        return $this->belongsTo(AnonymousUser::class, 'anonymous_user_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ReelComment::class, 'comment_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommentModerationRule::class, 'rule_id');
    }
}

