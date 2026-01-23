<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Exception;

class InventoryService
{
    /**
     * Lấy tổng số lượng đang được giữ chỗ (đơn chưa giao/cancel) của một sản phẩm/biến thể.
     */
    public function getReservedQuantity(Product $product, ?ProductVariant $variant = null, ?int $excludingOrderId = null): int
    {
        $query = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->where(function ($statusQuery) {
                        $statusQuery->whereNull('delivery_status')
                            ->orWhere('delivery_status', '!=', 'delivered');
                    });
            });

        if ($variant) {
            $query->where('product_variant_id', $variant->id);
        } else {
            $query->whereNull('product_variant_id');
        }

        if ($excludingOrderId) {
            $query->where('order_id', '!=', $excludingOrderId);
        }

        return (int) $query->sum('quantity');
    }

    /**
     * Lấy số lượng có thể bán (tồn kho thực - số lượng đang giữ chỗ).
     */
    public function getAvailableStock(Product $product, ?ProductVariant $variant = null, ?int $excludingOrderId = null): int
    {
        $physicalStock = $variant
            ? (int) ($variant->stock_quantity ?? 0)
            : (int) ($product->stock_quantity ?? 0);

        $reserved = $this->getReservedQuantity($product, $variant, $excludingOrderId);

        return max(0, $physicalStock - $reserved);
    }

    /**
     * Đảm bảo đủ tồn kho khả dụng cho số lượng yêu cầu.
     *
     * @throws Exception
     */
    public function ensureSufficientStock(
        Product $product,
        ?ProductVariant $variant,
        int $requiredQuantity,
        ?int $excludingOrderId = null
    ): void {
        $available = $this->getAvailableStock($product, $variant, $excludingOrderId);

        if ($available < $requiredQuantity) {
            $variantLabel = $variant
                ? ($variant->name ?? $variant->sku ?? 'Biến thể')
                : null;

            $productLabel = $variantLabel
                ? "{$product->name} ({$variantLabel})"
                : $product->name;

            throw new Exception("Sản phẩm '{$productLabel}' chỉ còn {$available} sản phẩm khả dụng.");
        }
    }
}

