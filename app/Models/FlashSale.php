<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FlashSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flash_sales';

    protected $fillable = [
        'title',
        'description',
        'banner',
        'tag',
        'start_time',
        'end_time',
        'status',
        'is_active',
        'is_locked',
        'max_per_user',
        'display_limit',
        'views',
        'created_by',
        'product_add_mode',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'is_active'  => 'boolean',
        'is_locked'  => 'boolean',
        'views'      => 'integer',
    ];

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'flash_sale_items',
            'flash_sale_id',
            'product_id'
        )->withPivot([
            'original_price',
            'sale_price',
            'stock',
            'sold',
            'max_per_user',
            'is_active'
        ]);
    }

    // Quan hệ với Account tạo
    public function creator()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    // Quan hệ 1-N: Một Flash Sale có nhiều sản phẩm
    public function items()
    {
        return $this->hasMany(FlashSaleItem::class, 'flash_sale_id');
    }

    // Sản phẩm đang active trong flash sale
    public function activeItems()
    {
        return $this->hasMany(FlashSaleItem::class, 'flash_sale_id')
            ->where('is_active', 1);
    }

    // Sản phẩm còn hàng trong flash sale
    public function availableItems()
    {
        return $this->hasMany(FlashSaleItem::class, 'flash_sale_id')
            ->where('is_active', 1)
            ->whereRaw('stock > sold');
    }

    // Lấy sản phẩm đang flash sale với thông tin đầy đủ
    public function getActiveProductsAttribute()
    {
        return $this->products()
            ->wherePivot('is_active', 1)
            ->get();
    }

    // Kiểm tra flash sale có đang diễn ra không
    public function isActive(): bool
    {
        return $this->is_active 
            && $this->status === 'active'
            && $this->start_time <= now()
            && $this->end_time >= now();
    }

    // Kiểm tra flash sale đã kết thúc chưa
    public function isExpired(): bool
    {
        return $this->end_time < now();
    }

    // Kiểm tra flash sale sắp bắt đầu
    public function isUpcoming(): bool
    {
        return $this->start_time > now();
    }

    // Lấy thời gian còn lại (giây)
    public function getRemainingTimeAttribute()
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        if ($this->isUpcoming()) {
            return $this->start_time->diffInSeconds(now());
        }
        
        return $this->end_time->diffInSeconds(now());
    }

    // Lấy tổng số sản phẩm trong flash sale
    public function getTotalProductsAttribute()
    {
        return $this->activeItems()->count();
    }

    // Lấy tổng số đã bán
    public function getTotalSoldAttribute()
    {
        return $this->activeItems()->sum('sold');
    }

    // Lấy tổng stock còn lại
    public function getTotalRemainingAttribute()
    {
        return $this->activeItems()->selectRaw('SUM(stock - sold) as remaining')->value('remaining');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1)
                     ->where('start_time', '<=', now())
                     ->where('end_time', '>=', now());
    }

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }


    /**
     * Scope lấy flash sale sắp tới
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
                     ->orderBy('start_time', 'asc');
    }

    /**
     * Scope lấy flash sale hiện tại hoặc sắp tới gần nhất
     */
    public function scopeCurrentOrNext($query)
    {
        return $query->active()->orWhere(function ($q) {
            $q->where('start_time', '>', now());
        })->orderByRaw("
            CASE 
                WHEN start_time <= NOW() AND end_time >= NOW() THEN 0 
                ELSE 1 
            END, start_time ASC
        ");
    }

    /**
     * Kiểm tra có thể chỉnh sửa không
     */
    public function canEdit(): bool
    {
        // Cho phép chỉnh sửa nếu:
        // - Status = draft
        // - Status = expired
        // - Status = active nhưng chưa bắt đầu (scheduled)
        
        if ($this->status === 'active' && $this->isActive()) {
            return false; // Đang chạy → không cho sửa
        }
        
        return true;
    }

    /**
     * Kiểm tra có đang bị khóa không
     */
    public function isLocked(): bool
    {
        return $this->is_locked || ($this->status === 'active' && $this->isActive());
    }

    /**
     * Tự động lock khi đang chạy
     */
    public function autoLock(): void
    {
        if ($this->status === 'active' && $this->isActive() && !$this->is_locked) {
            $this->update(['is_locked' => true]);
        }
    }
}
