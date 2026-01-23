<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'comments';

    protected $fillable = [
        'account_id',
        'parent_id',
        'guest_name',
        'guest_email',
        'content',
        'rating',
        'is_approved',
        'is_reported',
        'reports_count',
        'ip_address',
        'user_agent',
        'commentable_id',
        'commentable_type',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_reported' => 'boolean',
        'rating' => 'integer',
    ];

    protected $attributes = [
        'is_approved' => false,
        'is_reported' => false,
        'reports_count' => 0,
    ];

    public static function typeOptions(): array
    {
        return [
            'post' => Post::class,
            'product' => Product::class,
        ];
    }

    public static function typeLabel(string $alias): string
    {
        return match ($alias) {
            'post' => 'Bài viết',
            'product' => 'Sản phẩm',
            default => ucfirst($alias),
        };
    }

    // ------------------------------
    // Quan hệ
    // ------------------------------

    /**
     * Người viết comment
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Entity mà comment gắn vào (Product, Post, v.v.)
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Comment cha (nếu là reply).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Các reply trực tiếp.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->approved()
            ->orderBy('created_at', 'asc');
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeForCommentable($query, string $type, int $id)
    {
        return $query->where('commentable_type', $type)
            ->where('commentable_id', $id);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    public function reject(): void
    {
        $this->update(['is_approved' => false]);
    }

    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }
}
