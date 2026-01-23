<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FlashSaleItem;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'uuid',
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'total_price',
        'is_flash_sale',
        'flash_sale_item_id',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'price'       => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_flash_sale' => 'boolean',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function flashSaleItem()
    {
        return $this->belongsTo(FlashSaleItem::class, 'flash_sale_item_id');
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function updateTotal()
    {
        $this->total_price = $this->quantity * $this->price;
        $this->save();
    }

    public function increaseQuantity(int $qty = 1)
    {
        $this->quantity += $qty;
        $this->updateTotal();
    }

    public function decreaseQuantity(int $qty = 1)
    {
        $this->quantity = max(0, $this->quantity - $qty);
        $this->updateTotal();
    }
}
