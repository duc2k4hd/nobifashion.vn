<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'code',
        'account_id',
        'session_id',
        'total_price',
        'total_quantity',
        'status',
    ];

    protected $casts = [
        'total_price'    => 'decimal:2',
        'total_quantity' => 'integer',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function addItem($productId, $variantId = null, int $quantity = 1, float $price = 0)
    {
        $item = $this->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->total_price = $item->quantity * $item->price;
            $item->save();
        } else {
            $item = $this->items()->create([
                'product_id'        => $productId,
                'product_variant_id'=> $variantId,
                'quantity'          => $quantity,
                'price'             => $price,
                'total_price'       => $quantity * $price,
            ]);
        }

        $this->updateTotals();
        return $item;
    }

    public function removeItem($itemId)
    {
        $item = $this->items()->find($itemId);
        if ($item) {
            $item->delete();
            $this->updateTotals();
        }
    }

    public function clear()
    {
        $this->items()->delete();
        $this->update(['total_price' => 0, 'total_quantity' => 0]);
    }

    public function updateTotals()
    {
        // Chỉ tính các items active (status = 'active')
        $this->total_price = $this->items()->where('status', 'active')->sum('total_price');
        $this->total_quantity = $this->items()->where('status', 'active')->sum('quantity');
        $this->save();
    }

    // ------------------------------
    // View compatibility helpers
    // ------------------------------

    public function getCartItemsAttribute()
    {
        return $this->items()->active()->with(['variant.primaryVariantImage', 'variant.product.primaryImage', 'product'])->get();
    }
}
