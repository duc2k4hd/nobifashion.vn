<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $table = 'images';

    protected $fillable = [
        'product_id',
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
        if (empty($value)) {
            return $this->path ? asset($this->path) : null;
        }

        // Nếu là URL tuyệt đối (bắt đầu bằng http hoặc https)
        if (str_starts_with($value, 'http')) {
            $host = parse_url($value, PHP_URL_HOST);
            $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
            
            $isLocal = in_array($host, ['localhost', '127.0.0.1']);
            $isInternal = ($host === $currentHost);
            
            // Nếu là domain nội bộ hoặc localhost, trích xuất path và sinh lại URL chuẩn
            if ($isLocal || $isInternal) {
                $path = parse_url($value, PHP_URL_PATH);
                return asset(ltrim($path ?? '', '/'));
            }
            // Ảnh từ nguồn bên ngoài khác
            return $value;
        }

        return asset($value);
    }

    /**
     * Quan hệ: Ảnh thuộc về 1 sản phẩm
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
