<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'usage_count',
        'entity_id',
        'entity_type',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'usage_count' => 'integer',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    /**
     * Tag có thể gắn cho nhiều entity khác nhau (Post, Product, ...)
     */
    public function entity()
    {
        return $this->morphTo();
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeByEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (isset($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('slug', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['usage_count_min'])) {
            $query->where('usage_count', '>=', $filters['usage_count_min']);
        }

        if (isset($filters['usage_count_max'])) {
            $query->where('usage_count', '<=', $filters['usage_count_max']);
        }

        if (isset($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query;
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Tạo slug từ name
     */
    public static function generateSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Lấy tên entity (product/post name)
     */
    public function getEntityNameAttribute(): ?string
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        try {
            $entity = $this->entity;
            if ($entity) {
                return $entity->name ?? $entity->title ?? null;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Lấy URL của entity
     */
    public function getEntityUrlAttribute(): ?string
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        try {
            $entity = $this->entity;
            if ($entity) {
                $entityType = $this->entity_type;
                if ($entityType === \App\Models\Product::class || $entityType === 'product') {
                    return route('client.product.show', $entity->slug ?? $entity->id);
                } elseif ($entityType === \App\Models\Post::class || $entityType === 'post') {
                    return route('client.blog.show', $entity->slug ?? $entity->id);
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Accessor cho status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-secondary">Inactive</span>';
    }

    /**
     * Accessor cho entity type label
     */
    public function getEntityTypeLabelAttribute(): string
    {
        $types = [
            \App\Models\Product::class => 'Sản phẩm',
            'product' => 'Sản phẩm',
            \App\Models\Post::class => 'Bài viết',
            'post' => 'Bài viết',
            'category' => 'Danh mục',
        ];

        return $types[$this->entity_type] ?? $this->entity_type;
    }
}
