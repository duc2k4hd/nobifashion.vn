<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    use HasFactory;

    protected $table = 'redirects';

    protected $fillable = [
        'old_url',
        'old_url_hash',
        'new_url',
        'status_code',
        'is_active',
        'hits',
        'last_accessed_at',
        'note',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_active' => 'boolean',
        'hits' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect) {
            $normalizedOld = self::normalizePath($redirect->old_url);
            $redirect->old_url_hash = md5($normalizedOld);

            if (empty($redirect->status_code)) {
                $redirect->status_code = 301;
            }
        });

        static::saved(function (Redirect $redirect) {
            self::clearRedirectCache($redirect->old_url);
        });

        static::deleted(function (Redirect $redirect) {
            self::clearRedirectCache($redirect->old_url);
        });
    }

    /**
     * Chuẩn hóa URL/Path về một định dạng thống nhất để so khớp chính xác:
     * - Loại bỏ scheme (http/https) và domain
     * - Chuyển chữ thường
     * - Bỏ trailing slash
     * - Đảm bảo bắt đầu bằng dấu gạch chéo '/'
     * Ví dụ:
     *   "https://nobifashion.vn/blog/ao-so-mi/" -> "/blog/ao-so-mi"
     *   "blog/ao-so-mi" -> "/blog/ao-so-mi"
     *   "ao-so-mi" -> "/blog/ao-so-mi" (nếu là slug bài viết)
     */
    public static function normalizePath(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '/';
        }

        // Nếu là URL đầy đủ có host
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? $url;

        // Bỏ ký tự xuống dòng, khoảng trắng
        $path = trim($path);

        // Đảm bảo có dấu '/' ở đầu
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Bỏ trailing slash (trừ khi chỉ là '/')
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return mb_strtolower($path, 'UTF-8');
    }

    /**
     * Tạo hash MD5 từ URL/Path
     */
    public static function hashUrl(string $url): string
    {
        return md5(self::normalizePath($url));
    }

    /**
     * Xóa cache liên quan đến redirect này
     */
    public static function clearRedirectCache(?string $url = null): void
    {
        if ($url) {
            $hash = self::hashUrl($url);
            Cache::forget("redirect:lookup:{$hash}");
            Cache::forget("redirect:raw:" . md5(trim($url)));
        }
        Cache::forget('redirects:count:total');
        Cache::forget('redirects:count:active');
    }

    /**
     * Scope lọc chuyển hướng đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
