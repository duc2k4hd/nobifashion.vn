<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FlashSaleItem;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'uuid',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'total_price',
        'status',
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

    public function cart()
    {
        return $this->belongsTo(Cart::class);
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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

    // ------------------------------
    // View compatibility accessors
    // ------------------------------

    public function getProductVariantsAttribute()
    {
        return $this->variant ?: new ProductVariant();
    }

    public function getStockAttribute()
    {
        return (int) ($this->variant->stock_quantity ?? $this->product->stock_quantity ?? 0);
    }
}
