<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_canonical',
        'tag_ids',
        'excerpt',
        'content',
        'thumbnail',
        'thumbnail_alt_text',
        'status',
        'is_featured',
        'views',
        'account_id',
        'category_id',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'tag_ids' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'excerpt_text',
    ];

    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphMany
    {
        return $this->morphMany(Tag::class, 'entity');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithStatus($query, ?string $status)
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->status === 'pending' && $this->published_at && $this->published_at->isFuture();
    }

    public function getExcerptTextAttribute(): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return Str::limit(strip_tags((string) $this->content), 220);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function attachTags(array $tagIds): void
    {
        // Method này giữ lại để backward compatibility
        // Nhưng thực tế tags sẽ được sync qua PostService::syncTags()
        $this->tag_ids = array_values(array_unique($tagIds));
        $this->save();
    }

    /**
     * Lấy tag IDs từ relationship
     */
    public function getTagIdsAttribute(): array
    {
        // Nếu có tag_ids trong database (backward compatibility), dùng nó
        if (!empty($this->attributes['tag_ids'])) {
            $decoded = json_decode($this->attributes['tag_ids'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Nếu không, lấy từ relationship
        return $this->tags()->pluck('id')->toArray();
    }
}
