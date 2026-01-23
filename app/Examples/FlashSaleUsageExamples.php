<?php

namespace App\Examples;

use App\Models\Product;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Helpers\FlashSaleHelper;

/**
 * Ví dụ sử dụng Flash Sale Models
 * 
 * File này minh họa cách sử dụng các model đã được cải thiện
 * để làm việc với Flash Sale trong dự án Laravel
 */
class FlashSaleUsageExamples
{
    /**
     * Ví dụ 1: Kiểm tra sản phẩm có trong flash sale không
     */
    public function checkProductInFlashSale($productId)
    {
        $product = Product::find($productId);
        
        // Cách 1: Sử dụng method
        if ($product->isInFlashSale()) {
            echo "Sản phẩm đang trong flash sale!";
        }
        
        // Cách 2: Sử dụng helper
        if (FlashSaleHelper::isProductInFlashSale($productId)) {
            echo "Sản phẩm đang trong flash sale!";
        }
        
        return $product->isInFlashSale();
    }

    /**
     * Ví dụ 2: Lấy thông tin flash sale của sản phẩm
     */
    public function getProductFlashSaleInfo($productId)
    {
        $product = Product::find($productId);
        
        if ($product->isInFlashSale()) {
            $flashSaleInfo = $product->current_flash_sale_info;
            
            echo "Flash Sale: " . $flashSaleInfo['flash_sale']->title;
            echo "Giá gốc: " . number_format($flashSaleInfo['original_price']) . " VNĐ";
            echo "Giá sale: " . number_format($flashSaleInfo['sale_price']) . " VNĐ";
            echo "Giảm: " . $flashSaleInfo['discount_percent'] . "%";
            echo "Còn lại: " . $flashSaleInfo['remaining'] . " sản phẩm";
        }
        
        return $product->current_flash_sale_info;
    }

    /**
     * Ví dụ 3: Lấy giá flash sale của sản phẩm
     */
    public function getProductPrice($productId)
    {
        $product = Product::find($productId);
        
        // Lấy giá cuối cùng (ưu tiên flash sale)
        $finalPrice = $product->final_price;
        
        // Lấy giá flash sale nếu có
        $flashSalePrice = $product->flash_sale_price;
        
        if ($flashSalePrice) {
            echo "Giá flash sale: " . number_format($flashSalePrice) . " VNĐ";
            echo "Giá gốc: " . number_format($product->flash_sale_original_price) . " VNĐ";
        } else {
            echo "Giá thường: " . number_format($finalPrice) . " VNĐ";
        }
        
        return $flashSalePrice ?: $finalPrice;
    }

    /**
     * Ví dụ 4: Lấy tất cả sản phẩm đang flash sale
     */
    public function getAllFlashSaleProducts()
    {
        // Cách 1: Sử dụng scope
        $products = Product::inFlashSale()
            ->with(['currentFlashSaleItem.flashSale'])
            ->get();
        
        // Cách 2: Sử dụng helper
        $products = FlashSaleHelper::getActiveFlashSaleProducts();
        
        foreach ($products as $product) {
            echo "Sản phẩm: " . $product->name;
            echo "Giá flash sale: " . number_format($product->flash_sale_price) . " VNĐ";
            echo "Giảm: " . $product->current_flash_sale_info['discount_percent'] . "%";
        }
        
        return $products;
    }

    /**
     * Ví dụ 5: Lấy flash sale hiện tại
     */
    public function getCurrentFlashSale()
    {
        $flashSale = FlashSaleHelper::getCurrentFlashSale();
        
        if ($flashSale) {
            echo "Flash Sale: " . $flashSale->title;
            echo "Thời gian còn lại: " . $flashSale->remaining_time . " giây";
            echo "Tổng sản phẩm: " . $flashSale->total_products;
            echo "Đã bán: " . $flashSale->total_sold;
            echo "Còn lại: " . $flashSale->total_remaining;
        }
        
        return $flashSale;
    }

    /**
     * Ví dụ 6: Lấy flash sale items của sản phẩm
     */
    public function getProductFlashSaleItems($productId)
    {
        // Cách 1: Từ model Product
        $product = Product::find($productId);
        $flashSaleItems = $product->flashSaleItems()
            ->with(['flashSale'])
            ->get();
        
        // Cách 2: Sử dụng helper
        $flashSaleItems = FlashSaleHelper::getProductFlashSaleItems($productId);
        
        // Cách 3: Lấy item hiện tại
        $currentItem = FlashSaleHelper::getProductCurrentFlashSaleItem($productId);
        
        return [
            'all_items' => $flashSaleItems,
            'current_item' => $currentItem
        ];
    }

    /**
     * Ví dụ 7: Kiểm tra trạng thái flash sale
     */
    public function checkFlashSaleStatus($flashSaleId)
    {
        $flashSale = FlashSale::find($flashSaleId);
        
        if ($flashSale->isActive()) {
            echo "Flash sale đang diễn ra";
        } elseif ($flashSale->isExpired()) {
            echo "Flash sale đã kết thúc";
        } elseif ($flashSale->isUpcoming()) {
            echo "Flash sale sắp bắt đầu";
        }
        
        return [
            'is_active' => $flashSale->isActive(),
            'is_expired' => $flashSale->isExpired(),
            'is_upcoming' => $flashSale->isUpcoming(),
            'remaining_time' => $flashSale->remaining_time
        ];
    }

    /**
     * Ví dụ 8: Lấy thống kê flash sale
     */
    public function getFlashSaleStatistics($flashSaleId)
    {
        $stats = FlashSaleHelper::getFlashSaleStats($flashSaleId);
        
        echo "Tổng sản phẩm: " . $stats['total_products'];
        echo "Đã bán: " . $stats['total_sold'];
        echo "Còn lại: " . $stats['total_remaining'];
        echo "Thời gian còn lại: " . $stats['remaining_time'] . " giây";
        
        return $stats;
    }

    /**
     * Ví dụ 9: Query phức tạp với flash sale
     */
    public function complexFlashSaleQueries()
    {
        // Lấy sản phẩm flash sale với giá giảm > 50%
        $products = Product::inFlashSale()
            ->with(['currentFlashSaleItem'])
            ->get()
            ->filter(function ($product) {
                $info = $product->current_flash_sale_info;
                return $info && $info['discount_percent'] > 50;
            });
        
        // Lấy flash sale sắp kết thúc (còn < 1 giờ)
        $endingSoon = FlashSale::where('is_active', 1)
            ->where('status', 'active')
            ->where('end_time', '<=', now()->addHour())
            ->where('end_time', '>', now())
            ->get();
        
        // Lấy sản phẩm flash sale còn ít hàng (< 10 sản phẩm)
        $lowStock = Product::inFlashSale()
            ->with(['currentFlashSaleItem'])
            ->get()
            ->filter(function ($product) {
                $info = $product->current_flash_sale_info;
                return $info && $info['remaining'] < 10;
            });
        
        return [
            'high_discount' => $products,
            'ending_soon' => $endingSoon,
            'low_stock' => $lowStock
        ];
    }

    /**
     * Ví dụ 10: Sử dụng trong Controller
     */
    public function controllerExample($productId)
    {
        $product = Product::find($productId);
        
        $data = [
            'product' => $product,
            'is_in_flash_sale' => $product->isInFlashSale(),
            'flash_sale_info' => $product->current_flash_sale_info,
            'final_price' => $product->final_price,
            'flash_sale_price' => $product->flash_sale_price,
        ];
        
        // Trong view có thể sử dụng:
        // @if($product->isInFlashSale())
        //     <div class="flash-sale-badge">
        //         Flash Sale: {{ $product->current_flash_sale_info['discount_percent'] }}% OFF
        //     </div>
        //     <div class="price">
        //         <span class="original-price">{{ number_format($product->flash_sale_original_price) }} VNĐ</span>
        //         <span class="sale-price">{{ number_format($product->flash_sale_price) }} VNĐ</span>
        //     </div>
        // @else
        //     <div class="price">{{ number_format($product->final_price) }} VNĐ</div>
        // @endif
        
        return $data;
    }
}
