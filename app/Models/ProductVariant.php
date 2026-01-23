<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'sale_price',
        'stock_quantity',
        'attributes', // JSON: {color: "Red", size: "L"}
        'is_active',
    ];

    protected $casts = [
        'product_id'     => 'integer',
        'price'          => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'stock_quantity' => 'integer',
        'attributes'     => 'array',
        'is_active'      => 'boolean',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function primaryVariantImage()
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'product_variant_id');
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function getFinalPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function reduceStock(int $qty = 1): void
    {
        if ($this->stock_quantity >= $qty) {
            $this->decrement('stock_quantity', $qty);
        }
    }

    public function increaseStock(int $qty = 1): void
    {
        $this->increment('stock_quantity', $qty);
    }

    // ------------------------------
    // Biến thể & thuộc tính
    // ------------------------------

    /**
     * Chuẩn hoá mảng thuộc tính: key viết thường, trim, sort theo key.
     */
    public static function normalizeAttributesArray(array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $key => $value) {
            $k = is_string($key) ? trim(mb_strtolower($key)) : $key;
            $normalized[$k] = is_string($value) ? trim((string) $value) : $value;
        }
        ksort($normalized);
        return $normalized;
    }

    /**
     * Lấy giá trị thuộc tính theo key của biến thể (không phân biệt hoa thường key).
     */
    public function getVariantAttributeValue(string $key)
    {
        $key = trim(mb_strtolower($key));
        $attributes = static::normalizeAttributesArray((array) $this->getAttribute('attributes'));
        return $attributes[$key] ?? null;
    }

    /**
     * Kiểm tra biến thể có khớp với lựa chọn thuộc tính đưa vào không.
     * - strictKeys=false: chỉ cần các key tồn tại trong $selection đều khớp; có thể còn key khác.
     * - strictKeys=true: tập key phải khớp hoàn toàn 1-1 với biến thể.
     */
    public function matchesAttributes(array $selection, bool $strictKeys = false): bool
    {
        $variantAttrs = static::normalizeAttributesArray((array) $this->getAttribute('attributes'));
        $selectedAttrs = static::normalizeAttributesArray($selection);

        if ($strictKeys && array_keys($variantAttrs) !== array_keys($selectedAttrs)) {
            return false;
        }

        foreach ($selectedAttrs as $key => $value) {
            if (!array_key_exists($key, $variantAttrs)) {
                return false;
            }
            // So sánh theo string để tránh lệch kiểu
            if ((string) $variantAttrs[$key] !== (string) $value) {
                return false;
            }
        }
        return true;
    }
}
