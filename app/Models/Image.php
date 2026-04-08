<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $table = 'images';

    protected $fillable = [
        'name',
        'product_id',
        'entity_type',
        'entity_id',
        'role',
        'title',
        'notes',
        'alt',
        'url',
        'path',
        'extension',
        'mime_type',
        'size',
        'width',
        'height',
        'context',
        'file_modified_at',
        'thumbnail_url',
        'medium_url',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'entity_id' => 'integer',
        'is_primary' => 'boolean',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_modified_at' => 'datetime',
    ];

    /** Scope lọc theo context (post / product) */
    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /** Trả về mảng dimensions hoặc null */
    public function getDimensionsAttribute(): ?array
    {
        if ($this->width && $this->height) {
            return ['width' => $this->width, 'height' => $this->height];
        }
        return null;
    }

    /**
     * Accessor cho URL: Luôn đảm bảo trả về URL tuyệt đối
     */
    public function getUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return $value;
    }

    public function getNameAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        $path = $this->attributes['path'] ?? $this->attributes['url'] ?? null;
        return $path ? basename((string) $path) : null;
    }

    /**
     * Quan hệ: Ảnh thuộc về 1 sản phẩm
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
