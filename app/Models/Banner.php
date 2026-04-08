<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PDO;
use App\Services\Media\ImageRegistryService;

class Banner extends Model
{
    use HasFactory;

    const POSITION_HOME = 'home';
    const POSITION_SHOP = 'shop';

    protected $table = 'banners';

    protected $fillable = [
        'title',
        'image_desktop',
        'image_mobile',
        'link',
        'description',
        'position',
        'taget',
        'start_at',
        'end_at',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
        'order' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(function ($banner) {
            try {
                $registry = app(ImageRegistryService::class);
                $registry->syncEntityImage(
                    entityType: 'banner',
                    entityId: (int) $banner->id,
                    role: 'desktop',
                    storedPath: $banner->image_desktop,
                    meta: [
                        'title' => $banner->title,
                        'alt' => $banner->title,
                        'description' => $banner->description,
                        'context' => 'banner',
                    ]
                );
                $registry->syncEntityImage(
                    entityType: 'banner',
                    entityId: (int) $banner->id,
                    role: 'mobile',
                    storedPath: $banner->image_mobile,
                    meta: [
                        'title' => $banner->title,
                        'alt' => $banner->title,
                        'description' => $banner->description,
                        'context' => 'banner',
                    ]
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        });

        static::deleted(function ($banner) {
            try {
                $registry = app(ImageRegistryService::class);
                $registry->syncEntityImage('banner', (int) $banner->id, 'desktop', null);
                $registry->syncEntityImage('banner', (int) $banner->id, 'mobile', null);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeHome($query)
    {
        return $query->where('position', self::POSITION_HOME);
    }

    public function scopeShop($query)
    {
        return $query->where('position', self::POSITION_SHOP);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    /**
     * Lấy nhãn hiển thị của position
     */
    public function getPositionLabel(): string
    {
        $positions = config('banners.positions', []);
        return $positions[$this->position] ?? $this->position;
    }

    /**
     * Lấy danh sách tất cả positions có sẵn
     */
    public static function getAvailablePositions(): array
    {
        return config('banners.positions', []);
    }

    public function isActive(): bool
    {
        $now = now();
        if ($this->start_at && $this->start_at > $now) {
            return false;
        }
        if ($this->end_at && $this->end_at < $now) {
            return false;
        }
        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Sắp xếp theo order (ưu tiên), sau đó theo các tiêu chí khác
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')
            ->orderBy('start_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Lấy order tiếp theo cho một position cụ thể
     */
    public static function getNextOrderForPosition(string $position): int
    {
        $maxOrder = static::where('position', $position)->max('order') ?? -1;
        return $maxOrder + 1;
    }


    public function scopeImage(string $device = 'desktop'): string
    {
        return $device === 'mobile' ? $this->image_mobile : $this->image_desktop;
    }
}
