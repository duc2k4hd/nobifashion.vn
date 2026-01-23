<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PDO;

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
