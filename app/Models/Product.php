<?php

namespace App\Models;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = ['sku', 'name', 'slug', 'description', 'short_description', 'price', 'sale_price', 'cost_price', 'stock_quantity', 'meta_title', 'meta_description', 'meta_keywords', 'meta_canonical', 'primary_category_id', 'category_ids', 'tag_ids', 'is_featured', 'locked_by', 'locked_at', 'has_variants', 'created_by', 'is_active'];

    protected $casts = [
        'category_ids' => 'array',
        'tag_ids' => 'array',
        'is_featured' => 'boolean',
        'has_variants' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
        'meta_keywords' => 'array',
        'locked_at' => 'datetime',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    /**
     * Danh mục chính (có index, tối ưu query).
     */
    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    public function cartItems($cart_id) {
        return $this->belongsTo(CartItem::class, 'product_id')->where('cart_id', $cart_id);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'locked_by');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphMany
    {
        return $this->morphMany(Tag::class, 'entity');
    }

    public function averageRating()
    {
        return $this->comments()->avg('rating');
    }

    /**
     * Danh mục phụ (qua JSON category_ids).
     */
    public function extraCategories()
    {
        return Category::whereIn('id', $this->category_ids ?? [])->get();
    }

    public function scopeWithAnyCategory($query, $categoryIds)
    {
        return $query->whereIn('category_ids', (array) $categoryIds);
    }

    /**
     * Tất cả danh mục (gộp primary + extra).
     */
    public function allCategories()
    {
        $categories = collect();
        if ($this->primaryCategory) {
            $categories->push($this->primaryCategory);
        }
        if ($this->category_ids) {
            $extra = Category::whereIn('id', $this->category_ids)->get();
            $categories = $categories->merge($extra);
        }
        return $categories->unique('id');
    }

    public function scopeInCategory($query, $categoryIds)
    {
        // Ép về mảng để hỗ trợ cả int và array
        $ids = is_array($categoryIds) ? $categoryIds : [$categoryIds];

        return $query->where(function ($q) use ($ids) {
            // 1️⃣ Lọc theo primary_category_id
            $q->whereIn('primary_category_id', $ids);

            // 2️⃣ Lọc theo JSON category_ids (kiểu ["49","9",...])
            $q->orWhere(function ($q2) use ($ids) {
                foreach ($ids as $id) {
                    $stringId = (string) $id;
                    $q2->orWhereRaw('JSON_CONTAINS(category_ids, ?)', ['"' . $stringId . '"']);
                }
            });
        });
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Trả về danh sách key thuộc tính có ở bất kỳ biến thể nào của sản phẩm.
     */
    public function getVariantAttributeKeys(): array
    {
        $keys = collect();
        foreach ($this->variants as $variant) {
            $attrs = $variant->attributes ?? [];
            foreach (array_keys($attrs) as $key) {
                $keys->push(mb_strtolower($key));
            }
        }
        return $keys->unique()->values()->all();
    }

    /**
     * Trả về map key -> các giá trị có thể chọn dựa trên các biến thể hiện có và filter đã chọn.
     * - selected: mảng đã chọn một phần, dùng để loại các giá trị không dẫn đến biến thể hợp lệ.
     */
    public function getVariantAttributeOptions(array $selected = []): array
    {
        $selected = ProductVariant::normalizeAttributesArray($selected);
        $options = [];

        $validVariants = $this->variants()
            ->active()
            ->inStock()
            ->get()
            ->filter(function ($variant) use ($selected) {
                return $variant->matchesAttributes($selected, false);
            });

        foreach ($validVariants as $variant) {
            $attrs = ProductVariant::normalizeAttributesArray($variant->attributes ?? []);
            foreach ($attrs as $key => $value) {
                $options[$key] = $options[$key] ?? [];
                $options[$key][(string) $value] = true;
            }
        }

        // Chuyển sang danh sách giá trị
        foreach ($options as $key => $map) {
            $options[$key] = array_values(array_keys($map));
        }

        return $options;
    }

    /**
     * Tìm biến thể phù hợp với lựa chọn thuộc tính. Có thể không cần đủ tất cả key.
     * Ưu tiên biến thể còn hàng, active. Nếu nhiều, chọn theo orderBy id.
     */
    public function resolveVariantByAttributes(array $selected): ?ProductVariant
    {
        $selected = ProductVariant::normalizeAttributesArray($selected);
        $variants = $this->variants()->active()->inStock()->get();

        // Lọc exact matches trước
        $exact = $variants->first(function ($variant) use ($selected) {
            return $variant->matchesAttributes($selected, true);
        });
        if ($exact) {
            return $exact;
        }

        // Nếu không exact, cho phép partial match (tập con các key)
        return $variants->first(function ($variant) use ($selected) {
            return $variant->matchesAttributes($selected, false);
        });
    }

    public function scopeColorFilter($query, $colors)
    {
        $colorList = is_array($colors) ? $colors : explode(',', $colors);

        return $query->whereHas('variants', function ($q) use ($colorList) {
            foreach ($colorList as $color) {
                $q->orWhereJsonContains('attributes->color', $color);
            }
        });
    }

    public function scopeSizeFilter($query, $sizes)
    {
        $sizeList = is_array($sizes) ? $sizes : explode(',', $sizes);

        return $query->whereHas('variants', function ($q) use ($sizeList) {
            foreach ($sizeList as $size) {
                $q->orWhereJsonContains('attributes->size', $size);
            }
        });
    }
    
    public function scopePriceFilter($query, $minPrice = null, $maxPrice = null)
    {
        // Ép kiểu cho chắc chắn (tránh lỗi chuỗi rỗng)
        $minPrice = is_numeric($minPrice) ? (float) $minPrice : null;
        $maxPrice = is_numeric($maxPrice) ? (float) $maxPrice : null;

        return $query->when($minPrice !== null || $maxPrice !== null, function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice !== null && $maxPrice !== null) {
                // Cả 2 có giá trị → lọc trong khoảng
                $q->whereRaw('COALESCE(sale_price, price) BETWEEN ? AND ?', [$minPrice, $maxPrice]);
            } elseif ($minPrice !== null) {
                // Chỉ có min → lớn hơn hoặc bằng
                $q->whereRaw('COALESCE(sale_price, price) >= ?', [$minPrice]);
            } elseif ($maxPrice !== null) {
                // Chỉ có max → nhỏ hơn hoặc bằng
                $q->whereRaw('COALESCE(sale_price, price) <= ?', [$maxPrice]);
            }
        });
    }

    /**
     * Lấy giá cuối cùng của sản phẩm, ưu tiên biến thể nếu có selected.
     */
    public function getPriceForSelection(array $selected = []): float
    {
        $variant = $this->resolveVariantByAttributes($selected);
        $basePrice = (float) ($variant ? $variant->sale_price ?? $variant->price : $this->sale_price ?? $this->price);

        // Ưu tiên giá Flash Sale nếu sản phẩm đang trong flash sale và có giá thấp hơn
        $currentFlashSaleItem = $this->currentFlashSaleItem()->first();
        if ($currentFlashSaleItem && $currentFlashSaleItem->is_active) {
            $fsPrice = (float) $currentFlashSaleItem->sale_price;
            if ($fsPrice > 0 && $fsPrice < $basePrice) {
                return $fsPrice;
            }
        }
        return $basePrice;
    }

    /**
     * Kiểm tra stock có đủ cho lựa chọn thuộc tính và số lượng.
     */
    public function hasStockForSelection(array $selected = [], int $quantity = 1): bool
    {
        $variant = $this->resolveVariantByAttributes($selected);
        if ($this->has_variants) {
            return $variant ? $variant->stock_quantity >= $quantity : false;
        }
        return $this->stock_quantity >= $quantity;
    }

    /**
     * Thêm vào giỏ hàng theo lựa chọn thuộc tính. Trả về CartItem hoặc null nếu không hợp lệ.
     */
    public function addSelectionToCart(Cart $cart, array $selected = [], int $quantity = 1)
    {
        $variantId = null;
        $price = $this->getPriceForSelection($selected);

        if ($this->has_variants) {
            $variant = $this->resolveVariantByAttributes($selected);
            if (!$variant) {
                return null;
            }
            if ($variant->stock_quantity < $quantity) {
                return null;
            }
            $variantId = $variant->id;
        } else {
            if ($this->stock_quantity < $quantity) {
                return null;
            }
        }

        return $cart->addItem($this->id, $variantId, $quantity, $price);
    }

    public function faqs()
    {
        return $this->hasMany(ProductFaq::class);
    }

    public function howTos()
    {
        return $this->hasMany(ProductHowTo::class);
    }

    public function creator()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeActiveStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function getFinalPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function inCategory($categoryId): bool
    {
        if ($this->primary_category_id === $categoryId) {
            return true;
        }
        return in_array($categoryId, $this->category_ids ?? []);
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'product_id')->orderBy('order', 'asc');
    }

    public function primaryImage()
    {
        return $this->hasOne(Image::class, 'product_id')->where('is_primary', true);
    }

    public function getFrameAttribute()
    {
        // Logic để lấy frame, ví dụ:
        if ($this->is_featured) {
            return 'frame-free-ship-hot.png';
        }
        if ($this->sale_price && $this->sale_price < $this->price) {
            return 'frame-price-sale.png';
        }
        return 'frame-free-ship-hot.png';
    }

    public function getLabelAttribute()
    {
        if ($this->is_featured) {
            return 'Nổi bật';
        }
        if ($this->sale_price && $this->sale_price < $this->price) {
            return 'Giảm giá';
        }
        return 'Bán chạy ' . date('Y') . '';
    }

    /**
     * Lấy tag IDs từ relationship
     */
    public function getTagIdsAttribute(): array
    {
        // Nếu có tag_ids trong database (backward compatibility), dùng nó
        if (!empty($this->attributes['tag_ids'])) {
            $decoded = json_decode($this->attributes['tag_ids'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Nếu không, lấy từ relationship
        return $this->tags()->pluck('id')->toArray();
    }

    // Quan hệ tới flash_sale_items
    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class, 'product_id');
    }

    // Quan hệ gián tiếp tới FlashSale
    public function flashSales()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_items', 'product_id', 'flash_sale_id')->withPivot(['original_price', 'sale_price', 'stock', 'sold', 'max_per_user', 'is_active']);
    }

    // Flash Sale hiện tại (nếu có)
    public function currentFlashSale()
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_items', 'product_id', 'flash_sale_id')
            ->withPivot(['original_price', 'sale_price', 'stock', 'sold', 'max_per_user', 'is_active'])
            ->where('flash_sales.is_active', 1)
            ->whereRaw('flash_sales.start_time <= NOW()')
            ->whereRaw('flash_sales.end_time >= NOW()')
            ->where('flash_sale_items.is_active', 1)
            ->limit(1);
    }

    /**
     * Kiểm tra có Flash Sale hiện tại không (dùng cùng điều kiện với currentFlashSale)
     */
    public function hasCurrentFlashSale(): bool
    {
        return $this->currentFlashSale()->exists();
    }

    // Flash Sale Item hiện tại (nếu có)
    public function currentFlashSaleItem()
    {
        return $this->hasOne(FlashSaleItem::class, 'product_id')
            ->whereHas('flashSale', function ($query) {
                $query->where('is_active', 1)->where('status', 'active')->where('start_time', '<=', now())->where('end_time', '>=', now());
            })
            ->where('is_active', 1)
            ->latest('id');
    }

    // Kiểm tra sản phẩm có đang trong flash sale không
    public function isInFlashSale(): bool
    {
        return $this->flashSaleItems()
            ->where('is_active', 1)
            ->whereHas('flashSale', function ($q) {
                $q->where('is_active', 1)->whereRaw('start_time <= NOW()')->whereRaw('end_time >= NOW()');
            })
            ->exists();
    }

    // Lấy giá flash sale hiện tại
    public function getFlashSalePriceAttribute()
    {
        $flashSaleItem = $this->currentFlashSaleItem;
        return $flashSaleItem ? $flashSaleItem->sale_price : null;
    }

    // Lấy giá gốc trong flash sale
    public function getFlashSaleOriginalPriceAttribute()
    {
        $flashSaleItem = $this->currentFlashSaleItem;
        return $flashSaleItem ? $flashSaleItem->original_price : null;
    }

    // Lấy thông tin flash sale hiện tại
    public function getCurrentFlashSaleInfoAttribute()
    {
        $flashSaleItem = $this->currentFlashSaleItem;
        if (!$flashSaleItem) {
            return null;
        }

        return [
            'flash_sale' => $flashSaleItem->flashSale,
            'sale_price' => $flashSaleItem->sale_price,
            'original_price' => $flashSaleItem->original_price,
            'stock' => $flashSaleItem->stock,
            'sold' => $flashSaleItem->sold,
            'remaining' => $flashSaleItem->stock - $flashSaleItem->sold,
            'max_per_user' => $flashSaleItem->max_per_user,
            'discount_percent' => $flashSaleItem->original_price > 0 ? round((($flashSaleItem->original_price - $flashSaleItem->sale_price) / $flashSaleItem->original_price) * 100) : 0,
        ];
    }

    // Scope: Sản phẩm đang trong flash sale
    public function scopeInFlashSale($query)
    {
        return $query->whereHas('currentFlashSaleItem');
    }

    // Scope: Sản phẩm có flash sale sắp tới
    public function scopeUpcomingFlashSale($query)
    {
        return $query->whereHas('flashSaleItems.flashSale', function ($q) {
            $q->where('is_active', 1)->where('status', 'active')->where('start_time', '>', now());
        });
    }

    public function scopeRelated($query, $product, $limit = 12)
    {
        $currentId = $product->id;

        // ✅ Dùng cùng scope -> không khởi tạo Product:: mới
        $baseQuery = $query->newQuery()
            ->active()
            ->with('primaryImage')
            ->where('id', '!=', $currentId)
            ->where(function ($q) use ($product) {
                $q->where('primary_category_id', $product->primary_category_id)
                ->orWhereJsonContains('category_ids', (int) $product->primary_category_id)
                ->orWhereJsonContains('category_ids', (string) $product->primary_category_id);
            });

        // ✅ Tính số lượng trước & sau
        $beforeLimit = floor($limit / 2);
        $afterLimit = $limit - $beforeLimit;

        // ✅ Lấy sản phẩm trước
        $before = (clone $baseQuery)
            ->where('id', '<', $currentId)
            ->orderBy('id', 'desc')
            ->limit($beforeLimit)
            ->get()
            ->reverse();

        // ✅ Lấy sản phẩm sau
        $after = (clone $baseQuery)
            ->where('id', '>', $currentId)
            ->orderBy('id', 'asc')
            ->limit($afterLimit)
            ->get();

        // ✅ Gộp kết quả
        $related = $before->merge($after);

        // ✅ Nếu chưa đủ thì bù thêm
        if ($related->count() < $limit) {
            $missing = $limit - $related->count();

            if ($before->count() < $beforeLimit) {
                // Thiếu trước → bù thêm từ sau
                $extra = (clone $baseQuery)
                    ->where('id', '>', $currentId)
                    ->orderBy('id', 'asc')
                    ->skip($after->count())
                    ->limit($missing)
                    ->get();
                $related = $related->merge($extra);
            } elseif ($after->count() < $afterLimit) {
                // Thiếu sau → bù thêm từ trước
                $extra = (clone $baseQuery)
                    ->where('id', '<', $currentId)
                    ->orderBy('id', 'desc')
                    ->skip($before->count())
                    ->limit($missing)
                    ->get()
                    ->reverse();
                $related = $extra->merge($related);
            }
        }

        return $related->take($limit);
    }


    public function scopeNew($query)
    {
        $thirtyDaysAgo = now()->subDays(30);
        return $query->where('created_at', '>=', $thirtyDaysAgo);
    }
}
