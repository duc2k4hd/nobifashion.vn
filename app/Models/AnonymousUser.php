<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnonymousUser extends Model
{
    use HasFactory;

    protected $table = 'anonymous_users';

    protected $fillable = [
        'token_hash',
        'public_id',
        'nickname',
        'avatar_key',
        'avatar_color',
        'status',
        'is_banned',
        'ban_reason',
        'banned_until',
        'last_seen_at',
    ];

    protected $casts = [
        'is_banned' => 'boolean',
        'banned_until' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function likes(): HasMany
    {
        return $this->hasMany(ReelLike::class, 'anonymous_user_id');
    }

    public function saves(): HasMany
    {
        return $this->hasMany(ReelSave::class, 'anonymous_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReelComment::class, 'anonymous_user_id');
    }

    public function commentLikes(): HasMany
    {
        return $this->hasMany(ReelCommentLike::class, 'anonymous_user_id');
    }

    /**
     * Kiểm tra người dùng có đang bị khóa hay không
     */
    public function isCurrentlyBanned(): bool
    {
        if (!$this->is_banned && $this->status !== 'banned') {
            return false;
        }

        if ($this->banned_until && $this->banned_until->isPast()) {
            $this->update([
                'is_banned' => false,
                'status' => 'active',
                'ban_reason' => null,
                'banned_until' => null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Trả về định dạng công khai an toàn cho Client
     */
    public function toPublicArray(): array
    {
        return [
            'public_id' => $this->public_id,
            'nickname' => $this->nickname,
            'avatar_key' => $this->avatar_key,
            'avatar_color' => $this->avatar_color,
            'status' => $this->status,
            'is_banned' => $this->isCurrentlyBanned(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

