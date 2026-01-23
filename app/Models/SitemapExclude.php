<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitemapExclude extends Model
{
    use HasFactory;

    protected $table = 'sitemap_excludes';

    protected $fillable = [
        'type',
        'value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: chỉ lấy các bản ghi đang active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if URL should be excluded
     */
    public static function shouldExclude(string $url): bool
    {
        $excludes = static::where('is_active', true)->get();

        foreach ($excludes as $exclude) {
            if ($exclude->type === 'url') {
                if (str_contains($url, $exclude->value)) {
                    return true;
                }
            } elseif ($exclude->type === 'pattern') {
                if (preg_match($exclude->value, $url)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if entity ID should be excluded
     */
    public static function shouldExcludeId(string $type, int $id): bool
    {
        return static::where('is_active', true)
            ->where('type', $type)
            ->where('value', (string) $id)
            ->exists();
    }
}
