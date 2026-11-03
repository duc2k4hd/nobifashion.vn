<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reels';

    protected $fillable = [
        'external_id',
        'slug',
        'title',
        'caption',
        'video_url',
        'poster_url',
        'thumbnail_url',
        'duration',
        'width',
        'height',
        'aspect_ratio',
        'file_size',
        'mime_type',
        'playback_type',
        'video_provider',
        'audio_title',
        'author_name',
        'author_handle',
        'author_avatar',
        'author_verified',
        'brand_id',
        'category',
        'hashtags',
        'view_count',
        'like_count',
        'comment_count',
        'share_count',
        'save_count',
        'seo_title',
        'seo_description',
        'canonical_url',
        'status',
        'is_featured',
        'order_priority',
        'published_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'author_verified' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
        'save_count' => 'integer',
        'order_priority' => 'integer',
    ];

    // ==========================================
    // Scopes tối ưu truy vấn hiệu năng cao
    // ==========================================

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeByCategory($query, ?string $category = null)
    {
        if (!empty($category) && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeTrending($query)
    {
        return $query->published()
            ->orderByDesc('like_count')
            ->orderByDesc('view_count');
    }

    // ==========================================
    // Quan hệ CSDL
    // ==========================================

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'reel_products', 'reel_id', 'product_id')
            ->withPivot('order')
            ->orderByPivot('order', 'asc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReelComment::class, 'reel_id')
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->latest('id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReelLike::class, 'reel_id');
    }

    public function saves(): HasMany
    {
        return $this->hasMany(ReelSave::class, 'reel_id');
    }

    public function watchLogs(): HasMany
    {
        return $this->hasMany(ReelWatchLog::class, 'reel_id');
    }

    // ==========================================
    // Accessors & Formatters cho Frontend
    // ==========================================

    /**
     * Accessor tự động chuyển đổi URL video sang CDN hiện tại siêu tốc
     */
    public function getVideoUrlAttribute($value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        $cdnUrl = rtrim(env('CLOUDFLARE_R2_PUBLIC_URL', 'https://media.nobifashion.vn'), '/');

        // Nếu video vẫn mang domain r2.dev cũ, tự động đổi sang CDN mới
        if (str_contains($value, '.r2.dev/')) {
            $parts = explode('.r2.dev/', $value, 2);
            if (isset($parts[1])) {
                return $cdnUrl . '/' . ltrim($parts[1], '/');
            }
        }

        return $value;
    }

    public function toFeedArray(?string $clientIp = null, ?int $userId = null): array
    {
        // 1. Kiểm tra Brand liên kết: nếu có gắn hãng thì lấy tên và logo từ cột logo bảng brands
        $brand = $this->relationLoaded('brand') ? $this->brand : $this->brand()->first();

        if ($brand && !empty($brand->logo)) {
            $authorName = $brand->name;
            $authorHandle = '@' . $brand->slug;
            $logoName = trim($brand->logo);

            if (filter_var($logoName, FILTER_VALIDATE_URL)) {
                $authorAvatar = $logoName;
            } elseif (str_starts_with($logoName, '/')) {
                $authorAvatar = $logoName;
            } elseif (str_starts_with($logoName, 'clients/')) {
                $authorAvatar = '/' . $logoName;
            } else {
                $authorAvatar = '/clients/assets/img/brands/' . $logoName;
            }
        } else {
            // 2. Nếu không gắn hãng: Mặc định là Nobi Fashion, dùng key site_favicon trong bảng settings
            $authorName = 'Nobi Fashion';
            $authorHandle = '@nobifashion';

            // Cache nhẹ hoặc truy vấn key site_favicon trong bảng settings
            $faviconFile = \Illuminate\Support\Facades\Cache::remember('nobi_site_favicon_file', 3600, function () {
                return \App\Models\Setting::where('key', 'site_favicon')->value('value');
            });

            if (!empty($faviconFile)) {
                $favTrim = trim($faviconFile);
                if (filter_var($favTrim, FILTER_VALIDATE_URL)) {
                    $authorAvatar = $favTrim;
                } elseif (str_starts_with($favTrim, '/')) {
                    $authorAvatar = $favTrim;
                } elseif (str_starts_with($favTrim, 'clients/')) {
                    $authorAvatar = '/' . $favTrim;
                } else {
                    $authorAvatar = '/clients/assets/img/business/' . $favTrim;
                }
            } else {
                $authorAvatar = '/clients/assets/img/business/nobifashion-logo.png';
            }
        }

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'caption' => $this->caption ?? $this->title,
            'videoUrl' => $this->video_url,
            'posterUrl' => $this->poster_url ?? asset('clients/assets/img/clothes/no-image.webp'),
            'duration' => $this->duration,
            'aspectRatio' => $this->aspect_ratio,
            'audioTitle' => $this->audio_title ?? ('Âm thanh gốc — ' . $authorName),
            'category' => $this->category,
            'hashtags' => $this->hashtags ?? ['#NobiFashion', '#Reels'],
            'createdAt' => $this->published_at ? $this->published_at->diffForHumans() : ($this->created_at ? $this->created_at->diffForHumans() : 'Vừa xong'),
            'timestamp' => $this->published_at ? $this->published_at->timestamp : ($this->created_at ? $this->created_at->timestamp : time()),
            'likesCount' => (int) $this->like_count,
            'commentsCount' => (int) $this->comment_count,
            'sharesCount' => (int) $this->share_count,
            'savesCount' => (int) $this->save_count,
            'isLiked' => false,
            'isSaved' => false,
            'isFollowing' => false,
            'brand' => $brand ? [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $authorAvatar,
            ] : null,
            'author' => [
                'id' => $brand ? 'brand_' . $brand->id : 'brand_nobifashion',
                'name' => $authorName,
                'handle' => $authorHandle,
                'avatar' => $authorAvatar,
                'verified' => (bool) ($this->author_verified ?? true),
            ],
            'products' => $this->products->map(function ($p) {
                return [
                    'id' => (string) $p->id,
                    'name' => $p->name,
                    'price' => (float) ($p->sale_price ?? $p->price),
                    'originalPrice' => $p->sale_price ? (float) $p->price : null,
                    'currency' => '₫',
                    'image' => $p->primaryImage ? asset('clients/assets/img/clothes/' . $p->primaryImage->url) : asset('clients/assets/img/clothes/no-image.webp'),
                    'url' => route('client.product.detail', ['slug' => $p->slug]),
                    'description' => $p->short_description ?? 'Sản phẩm thời trang cao cấp Nobi Fashion',
                ];
            })->values()->all(),
            'comments' => $this->comments()->whereNull('parent_id')->with('replies.replies.replies')->latest('id')->get()->map(function ($c) {
                return $this->formatCommentHierarchy($c);
            })->values()->all(),
        ];
    }

    protected function formatCommentHierarchy($c): array
    {
        return [
            'id' => (string) $c->id,
            'author' => $c->author_name ?? 'Khách hàng Nobi',
            'initials' => mb_strtoupper(mb_substr($c->author_name ?? 'N', 0, 1)),
            'text' => $c->content,
            'time' => $c->created_at ? $c->created_at->diffForHumans() : 'Vừa xong',
            'likes' => (int) $c->likes_count,
            'isLiked' => false,
            'isAuthor' => in_array($c->author_name, ['Coolmate', 'Nobi Fashion']),
            'isVerified' => in_array($c->author_name, ['Khoai Lang Thang', 'Coolmate', 'Nobi Fashion']),
            'replies' => $c->replies ? $c->replies->map(fn($r) => $this->formatCommentHierarchy($r))->values()->all() : [],
        ];
    }
}

