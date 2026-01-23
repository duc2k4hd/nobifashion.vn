<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FlashSaleService
{
    /**
     * Lấy giá Flash Sale cho một variant
     * 
     * @param int $productId ID sản phẩm
     * @param int|null $variantId ID variant (optional)
     * @return array|null ['price' => float, 'flash_sale_item_id' => int, 'is_unified' => bool] hoặc null
     */
    public function getPriceForVariant(int $productId, ?int $variantId = null): ?array
    {
        $flashSaleItem = FlashSaleItem::where('product_id', $productId)
            ->where('is_active', true)
            ->whereHas('flashSale', function ($query) {
                $query->where('is_active', true)
                    ->where('status', 'active')
                    ->where('start_time', '<=', now())
                    ->where('end_time', '>=', now());
            })
            ->first();

        if (!$flashSaleItem) {
            return null;
        }

        // Nếu có unified_price → dùng giá đồng nhất
        if ($flashSaleItem->unified_price !== null) {
            return [
                'price' => (float) $flashSaleItem->unified_price,
                'flash_sale_item_id' => $flashSaleItem->id,
                'is_unified' => true,
                'flash_sale_id' => $flashSaleItem->flash_sale_id,
                'original_price' => $variantId 
                    ? (ProductVariant::find($variantId)?->price ?? 0)
                    : (Product::find($productId)?->price ?? 0),
            ];
        }

        // Nếu không có unified_price → kiểm tra variant cụ thể
        if ($variantId) {
            $variantItem = FlashSaleItem::where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->where('is_active', true)
                ->whereHas('flashSale', function ($query) {
                    $query->where('is_active', true)
                        ->where('status', 'active')
                        ->where('start_time', '<=', now())
                        ->where('end_time', '>=', now());
                })
                ->first();

            if ($variantItem) {
                return [
                    'price' => (float) $variantItem->sale_price,
                    'flash_sale_item_id' => $variantItem->id,
                    'is_unified' => false,
                    'flash_sale_id' => $variantItem->flash_sale_id,
                    'original_price' => ProductVariant::find($variantId)?->price ?? 0,
                ];
            }
        }

        // Fallback: dùng sale_price của flash_sale_item (nếu có)
        if ($flashSaleItem->sale_price) {
            return [
                'price' => (float) $flashSaleItem->sale_price,
                'flash_sale_item_id' => $flashSaleItem->id,
                'is_unified' => false,
                'flash_sale_id' => $flashSaleItem->flash_sale_id,
                'original_price' => $variantId 
                    ? (ProductVariant::find($variantId)?->price ?? 0)
                    : (Product::find($productId)?->price ?? 0),
            ];
        }

        return null;
    }

    /**
     * Kiểm tra Flash Sale còn active không
     */
    public function isFlashSaleActive(int $flashSaleId): bool
    {
        $flashSale = FlashSale::find($flashSaleId);
        
        if (!$flashSale) {
            return false;
        }

        return $flashSale->isActive();
    }

    /**
     * Validate và cập nhật giá trong cart
     * Kiểm tra TẤT CẢ items, tự động áp dụng Flash Sale nếu có, hoặc về giá gốc nếu Flash Sale đã kết thúc
     */
    public function validateCartPrices(Cart $cart): Cart
    {
        return DB::transaction(function () use ($cart) {
            $updated = false;

            foreach ($cart->items()->where('status', 'active')->get() as $cartItem) {
                // Kiểm tra sản phẩm này có đang trong Flash Sale không
                $flashSalePrice = $this->getPriceForVariant(
                    $cartItem->product_id,
                    $cartItem->product_variant_id
                );

                if ($flashSalePrice) {
                    // CÓ Flash Sale → Áp dụng giá Flash Sale
                    $correctPrice = $flashSalePrice['price'];
                    $priceDiff = abs($cartItem->price - $correctPrice);
                    
                    // Kiểm tra xem có cần cập nhật không
                    if ($priceDiff > 0.01 || !$cartItem->is_flash_sale || $cartItem->flash_sale_item_id != $flashSalePrice['flash_sale_item_id']) {
                        // Giá sai hoặc chưa được đánh dấu là Flash Sale → cập nhật
                        $cartItem->update([
                            'price' => $correctPrice,
                            'total_price' => $cartItem->quantity * $correctPrice,
                            'is_flash_sale' => true,
                            'flash_sale_item_id' => $flashSalePrice['flash_sale_item_id'],
                        ]);
                        $updated = true;
                    }
                } else {
                    // KHÔNG có Flash Sale → Về giá gốc (nếu đang là Flash Sale)
                    if ($cartItem->is_flash_sale) {
                        $this->revertToOriginalPrice($cartItem);
                        $updated = true;
                    } else {
                        // Đã là giá gốc rồi → Kiểm tra giá có đúng không
                        $variant = $cartItem->variant;
                        $product = $cartItem->product;
                        
                        $expectedPrice = $variant
                            ? ($variant->sale_price ?? $variant->price)
                            : ($product->sale_price ?? $product->price);
                        
                        $priceDiff = abs($cartItem->price - $expectedPrice);
                        if ($priceDiff > 0.01) {
                            // Giá không đúng → cập nhật về giá gốc
                            $cartItem->update([
                                'price' => $expectedPrice,
                                'total_price' => $cartItem->quantity * $expectedPrice,
                            ]);
                            $updated = true;
                        }
                    }
                }
            }

            if ($updated) {
                $cart->refresh();
                $cart->updateTotals();
            }

            return $cart->fresh();
        });
    }

    /**
     * Chuyển về giá gốc của variant
     */
    protected function revertToOriginalPrice(CartItem $cartItem): void
    {
        $variant = $cartItem->variant;
        $product = $cartItem->product;

        $originalPrice = $variant
            ? ($variant->sale_price ?? $variant->price)
            : ($product->sale_price ?? $product->price);

        $cartItem->update([
            'price' => $originalPrice,
            'total_price' => $cartItem->quantity * $originalPrice,
            'is_flash_sale' => false,
            'flash_sale_item_id' => null,
        ]);
    }

    /**
     * Lấy tất cả sản phẩm đang Flash Sale với giá đồng nhất
     */
    public function getActiveFlashSalesWithUnifiedPrice(): array
    {
        return FlashSaleItem::where('is_active', true)
            ->whereNotNull('unified_price')
            ->whereHas('flashSale', function ($query) {
                $query->where('is_active', true)
                    ->where('status', 'active')
                    ->where('start_time', '<=', now())
                    ->where('end_time', '>=', now());
            })
            ->with(['product', 'flashSale'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $firstItem = $items->first();
                return [
                    'product_id' => $firstItem->product_id,
                    'unified_price' => $firstItem->unified_price,
                    'flash_sale_id' => $firstItem->flash_sale_id,
                    'flash_sale_item_id' => $firstItem->id,
                    'flash_sale' => $firstItem->flashSale,
                    'product' => $firstItem->product,
                    'end_time' => $firstItem->flashSale->end_time,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Kiểm tra variant có đang trong Flash Sale không
     */
    public function isVariantInFlashSale(int $productId, ?int $variantId = null): bool
    {
        return $this->getPriceForVariant($productId, $variantId) !== null;
    }
}

