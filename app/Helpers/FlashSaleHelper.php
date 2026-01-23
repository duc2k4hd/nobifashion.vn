<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;

class FlashSaleHelper
{
    /**
     * Lấy tất cả sản phẩm đang trong flash sale
     */
    public static function getActiveFlashSaleProducts()
    {
        return Product::inFlashSale()
            ->with(['currentFlashSaleItem.flashSale'])
            ->get();
    }

    /**
     * Lấy flash sale hiện tại
     */
    public static function getCurrentFlashSale()
    {
        return FlashSale::where('is_active', 1)
            ->where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with(['activeItems.product'])
            ->first();
    }

    /**
     * Kiểm tra sản phẩm có trong flash sale không
     */
    public static function isProductInFlashSale($productId)
    {
        $product = Product::find($productId);
        return $product ? $product->isInFlashSale() : false;
    }

    /**
     * Lấy thông tin flash sale của sản phẩm
     */
    public static function getProductFlashSaleInfo($productId)
    {
        $product = Product::find($productId);
        return $product ? $product->current_flash_sale_info : null;
    }

    /**
     * Lấy giá flash sale của sản phẩm
     */
    public static function getProductFlashSalePrice($productId)
    {
        $product = Product::find($productId);
        return $product ? $product->flash_sale_price : null;
    }

    /**
     * Lấy danh sách sản phẩm flash sale với thông tin chi tiết
     */
    public static function getFlashSaleProductsWithDetails()
    {
        return Product::inFlashSale()
            ->with(['currentFlashSaleItem.flashSale'])
            ->get()
            ->map(function ($product) {
                return [
                    'product' => $product,
                    'flash_sale_info' => $product->current_flash_sale_info,
                    'is_in_flash_sale' => $product->isInFlashSale(),
                    'flash_sale_price' => $product->flash_sale_price,
                    'original_price' => $product->flash_sale_original_price,
                ];
            });
    }

    /**
     * Lấy flash sale items của một sản phẩm
     */
    public static function getProductFlashSaleItems($productId)
    {
        return FlashSaleItem::where('product_id', $productId)
            ->with(['flashSale', 'product'])
            ->get();
    }

    /**
     * Lấy flash sale items hiện tại của một sản phẩm
     */
    public static function getProductCurrentFlashSaleItem($productId)
    {
        return FlashSaleItem::where('product_id', $productId)
            ->active()
            ->with(['flashSale', 'product'])
            ->first();
    }

    /**
     * Lấy tất cả flash sale đang diễn ra
     */
    public static function getActiveFlashSales()
    {
        return FlashSale::where('is_active', 1)
            ->where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with(['activeItems.product'])
            ->get();
    }

    /**
     * Lấy flash sale sắp tới
     */
    public static function getUpcomingFlashSales()
    {
        return FlashSale::where('is_active', 1)
            ->where('status', 'active')
            ->where('start_time', '>', now())
            ->with(['items.product'])
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Kiểm tra flash sale có đang diễn ra không
     */
    public static function isFlashSaleActive($flashSaleId)
    {
        $flashSale = FlashSale::find($flashSaleId);
        return $flashSale ? $flashSale->isActive() : false;
    }

    /**
     * Lấy thống kê flash sale
     */
    public static function getFlashSaleStats($flashSaleId)
    {
        $flashSale = FlashSale::find($flashSaleId);
        if (!$flashSale) {
            return null;
        }

        return [
            'total_products' => $flashSale->total_products,
            'total_sold' => $flashSale->total_sold,
            'total_remaining' => $flashSale->total_remaining,
            'remaining_time' => $flashSale->remaining_time,
            'is_active' => $flashSale->isActive(),
            'is_expired' => $flashSale->isExpired(),
            'is_upcoming' => $flashSale->isUpcoming(),
        ];
    }
}
