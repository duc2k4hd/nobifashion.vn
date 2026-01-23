<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSaleItem extends Model
{
    use HasFactory;

    protected $table = 'flash_sale_items';

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'product_variant_id',
        'original_price',
        'sale_price',
        'unified_price',
        'original_variant_price',
        'stock',
        'sold',
        'max_per_user',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'unified_price'  => 'decimal:2',
        'original_variant_price' => 'decimal:2',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    // Quan hệ N-1: Flash Sale Item thuộc về 1 Flash Sale
    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    // Quan hệ N-1: Flash Sale Item gắn với 1 sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Quan hệ N-1: Flash Sale Item có thể có variant
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Quan hệ 1-N: Lịch sử thay đổi giá
    public function priceLogs()
    {
        return $this->hasMany(FlashSalePriceLog::class, 'flash_sale_item_id');
    }

    // Kiểm tra item có còn hàng không
    public function isAvailable(): bool
    {
        return $this->is_active && ($this->stock > $this->sold);
    }

    // Lấy số lượng còn lại
    public function getRemainingAttribute()
    {
        return max(0, $this->stock - $this->sold);
    }

    // Kiểm tra có thể mua thêm không (theo giới hạn user)
    public function canBuyMore($quantity = 1, $userId = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($this->max_per_user && $userId) {
            // TODO: Cần implement logic kiểm tra user đã mua bao nhiêu
            // Có thể cần thêm bảng order_items hoặc flash_sale_purchases
        }

        return $this->remaining >= $quantity;
    }

    // Lấy phần trăm đã bán
    public function getSoldPercentageAttribute()
    {
        if ($this->stock == 0) {
            return 0;
        }
        return round(($this->sold / $this->stock) * 100, 2);
    }

    // Lấy phần trăm còn lại
    public function getRemainingPercentageAttribute()
    {
        return 100 - $this->sold_percentage;
    }

    // Lấy thông tin giảm giá
    public function getDiscountInfoAttribute()
    {
        if (!$this->original_price || $this->original_price <= 0) {
            return null;
        }

        $discountAmount = $this->original_price - $this->sale_price;
        $discountPercent = round(($discountAmount / $this->original_price) * 100, 2);

        return [
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'original_price' => $this->original_price,
            'sale_price' => $this->sale_price
        ];
    }

    // Scope: Items còn hàng
    public function scopeAvailable($query)
    {
        return $query->where('is_active', 1)
                    ->whereRaw('stock > sold');
    }

    // Scope: Items đang active trong flash sale
    public function scopeActive($query)
    {
        return $query->where('is_active', 1)
                    ->whereHas('flashSale', function ($q) {
                        $q->where('is_active', 1)
                          ->where('status', 'active')
                          ->where('start_time', '<=', now())
                          ->where('end_time', '>=', now());
                    });
    }

    // Scope: Items sắp bắt đầu
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', 1)
                    ->whereHas('flashSale', function ($q) {
                        $q->where('is_active', 1)
                          ->where('status', 'active')
                          ->where('start_time', '>', now());
                    });
    }
}
