<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FlashSaleService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public function __construct(
        protected FlashSaleService $flashSaleService,
        protected InventoryService $inventoryService
    ) {
    }
    /**
     * Get or create cart for user or guest
     * Tự động validate giá Flash Sale mỗi khi lấy cart
     */
    public function getOrCreateCart(?int $accountId = null, ?string $sessionId = null): Cart
    {
        if ($accountId) {
            $cart = Cart::where('account_id', $accountId)
                ->where('status', 'active')
                ->first();

            if ($cart) {
                // Validate và cập nhật giá Flash Sale
                $this->flashSaleService->validateCartPrices($cart);
                return $cart->fresh();
            }

            return Cart::create([
                'account_id' => $accountId,
                'code' => $this->generateCartCode(),
                'total_price' => 0,
                'total_quantity' => 0,
                'status' => 'active',
            ]);
        }

        if ($sessionId) {
            $cart = Cart::where('session_id', $sessionId)
                ->where('status', 'active')
                ->latest()
                ->first();

            if ($cart) {
                // Validate và cập nhật giá Flash Sale
                $this->flashSaleService->validateCartPrices($cart);
                return $cart->fresh();
            }

            return Cart::create([
                'session_id' => $sessionId,
                'code' => $this->generateCartCode(),
                'total_price' => 0,
                'total_quantity' => 0,
                'status' => 'active',
            ]);
        }

        throw new \InvalidArgumentException('Either account_id or session_id must be provided');
    }

    /**
     * Merge guest cart to user cart when login
     */
    public function mergeGuestCartToUser(string $sessionId, int $accountId): Cart
    {
        return DB::transaction(function () use ($sessionId, $accountId) {
            $guestCart = Cart::where('session_id', $sessionId)
                ->where('status', 'active')
                ->with('items')
                ->first();

            $userCart = Cart::where('account_id', $accountId)
                ->where('status', 'active')
                ->with('items')
                ->first();

            if (!$guestCart || $guestCart->items->isEmpty()) {
                return $userCart ?? $this->getOrCreateCart($accountId);
            }

            if (!$userCart) {
                $userCart = $this->getOrCreateCart($accountId);
            }

            // Validate giá Flash Sale cho cả 2 cart trước khi merge
            $this->flashSaleService->validateCartPrices($guestCart);
            $this->flashSaleService->validateCartPrices($userCart);
            $guestCart->refresh();
            $userCart->refresh();

            // Merge items
            foreach ($guestCart->items()->where('status', 'active')->with(['product', 'variant'])->get() as $guestItem) {
                if (!$guestItem->product) {
                    continue;
                }

                // Validate giá Flash Sale cho item trước khi merge
                $flashSalePrice = $this->flashSaleService->getPriceForVariant(
                    $guestItem->product_id,
                    $guestItem->product_variant_id
                );
                
                // Cập nhật giá nếu có Flash Sale
                if ($flashSalePrice) {
                    $guestItem->update([
                        'price' => $flashSalePrice['price'],
                        'total_price' => $guestItem->quantity * $flashSalePrice['price'],
                        'is_flash_sale' => true,
                        'flash_sale_item_id' => $flashSalePrice['flash_sale_item_id'],
                    ]);
                }
                
                $existingItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->where('status', 'active')
                    ->with(['product', 'variant'])
                    ->first();

                if ($existingItem) {
                    // Merge quantity - Validate giá Flash Sale trước
                    $flashSalePriceForExisting = $this->flashSaleService->getPriceForVariant(
                        $existingItem->product_id,
                        $existingItem->product_variant_id
                    );
                    
                    $correctPrice = $flashSalePriceForExisting 
                        ? $flashSalePriceForExisting['price']
                        : ($existingItem->variant 
                            ? ($existingItem->variant->sale_price ?? $existingItem->variant->price)
                            : ($existingItem->product->sale_price ?? $existingItem->product->price));
                    
                    $newQuantity = $existingItem->quantity + $guestItem->quantity;
                    $this->inventoryService->ensureSufficientStock(
                        $existingItem->product,
                        $existingItem->variant,
                        $newQuantity
                    );

                    $existingItem->update([
                        'quantity' => $newQuantity,
                        'price' => $correctPrice,
                        'total_price' => $newQuantity * $correctPrice,
                        'is_flash_sale' => $flashSalePriceForExisting ? true : false,
                        'flash_sale_item_id' => $flashSalePriceForExisting['flash_sale_item_id'] ?? null,
                    ]);
                } else {
                    // Move item to user cart (validate tồn kho trước)
                    $this->inventoryService->ensureSufficientStock(
                        $guestItem->product,
                        $guestItem->variant,
                        $guestItem->quantity
                    );

                    $guestItem->update([
                        'cart_id' => $userCart->id,
                    ]);
                }
            }

            // Delete guest cart
            $guestCart->delete();

            // Validate lại sau khi merge
            $this->flashSaleService->validateCartPrices($userCart);
            
            // Recalculate user cart
            $this->recalculateTotals($userCart);

            return $userCart->fresh(['items']);
        });
    }

    /**
     * Add item to cart
     */
    public function addItem(Cart $cart, int $productId, ?int $variantId = null, int $quantity = 1, ?float $price = null): CartItem
    {
        return DB::transaction(function () use ($cart, $productId, $variantId, $quantity, $price) {
            $product = Product::findOrFail($productId);
            $variant = null;

            if (!$product->is_active) {
                throw new \Exception('Sản phẩm không còn hoạt động.');
            }

            // Get price from variant or product
            if ($variantId) {
                $variant = ProductVariant::findOrFail($variantId);
                // So sánh sau khi cast về int để tránh lỗi kiểu dữ liệu (string vs int)
                if ((int) $variant->product_id !== (int) $productId) {
                    throw new \Exception('Biến thể không thuộc sản phẩm này.');
                }
                if (!$variant->status) {
                    throw new \Exception('Biến thể không còn hoạt động.');
                }
                $basePrice = $price ?? ($variant->sale_price ?? $variant->price ?? $product->sale_price ?? $product->price);
            } else {
                $basePrice = $price ?? ($product->sale_price ?? $product->price);
            }

            // Kiểm tra Flash Sale với logic đồng giá
            $flashSalePrice = $this->flashSaleService->getPriceForVariant($productId, $variantId);
            
            if ($flashSalePrice) {
                // Dùng giá Flash Sale (ưu tiên unified_price nếu có)
                $finalPrice = $flashSalePrice['price'];
                $flashSaleItemId = $flashSalePrice['flash_sale_item_id'];
                $isFlashSale = true;
            } else {
                // Không có Flash Sale → dùng giá gốc
                $finalPrice = $basePrice;
                $flashSaleItemId = null;
                $isFlashSale = false;
            }

            // Check if item already exists
            $existingItemQuery = $cart->items()
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->where('status', 'active');

            if ($isFlashSale) {
                $existingItemQuery->where('is_flash_sale', true)
                    ->where('flash_sale_item_id', $flashSaleItemId);
            } else {
                $existingItemQuery->where(function ($q) {
                    $q->where('is_flash_sale', false)
                        ->orWhereNull('is_flash_sale');
                })->whereNull('flash_sale_item_id');
            }

            $existingItem = $existingItemQuery->first();

            $targetQuantity = $existingItem
                ? $existingItem->quantity + $quantity
                : $quantity;

            $this->inventoryService->ensureSufficientStock($product, $variant ?? null, $targetQuantity);

            if ($existingItem) {
                $existingItem->quantity = $targetQuantity;
                $existingItem->price = $finalPrice;
                $existingItem->total_price = $targetQuantity * $finalPrice;
                $existingItem->save();

                $this->recalculateTotals($cart);

                return $existingItem->fresh();
            }

            // Create new item
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'uuid' => Str::uuid()->toString(),
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $finalPrice,
                'total_price' => $quantity * $finalPrice,
                'status' => 'active',
                'is_flash_sale' => $isFlashSale,
                'flash_sale_item_id' => $flashSaleItemId,
            ]);

            $this->recalculateTotals($cart);

            return $item;
        });
    }

    /**
     * Update item quantity
     * Tự động validate giá Flash Sale trước khi update
     */
    public function updateItemQuantity(CartItem $item, int $newQuantity): CartItem
    {
        return DB::transaction(function () use ($item, $newQuantity) {
            if ($newQuantity <= 0) {
                $this->removeItem($item, false);
                return $item;
            }

            $item->loadMissing(['product', 'variant']);
            $this->inventoryService->ensureSufficientStock(
                $item->product,
                $item->variant,
                $newQuantity
            );

            // Validate giá Flash Sale trước khi update quantity
            $flashSalePrice = $this->flashSaleService->getPriceForVariant(
                $item->product_id,
                $item->product_variant_id
            );
            
            // Lấy giá đúng (Flash Sale hoặc giá gốc)
            $correctPrice = $flashSalePrice 
                ? $flashSalePrice['price']
                : ($item->variant 
                    ? ($item->variant->sale_price ?? $item->variant->price)
                    : ($item->product->sale_price ?? $item->product->price));

            $item->update([
                'quantity' => $newQuantity,
                'price' => $correctPrice,
                'total_price' => $newQuantity * $correctPrice,
                'is_flash_sale' => $flashSalePrice ? true : false,
                'flash_sale_item_id' => $flashSalePrice['flash_sale_item_id'] ?? null,
            ]);

            $this->recalculateTotals($item->cart);

            return $item->fresh();
        });
    }

    /**
     * Remove item from cart
     */
    public function removeItem(CartItem $item, bool $softDelete = true): bool
    {
        return DB::transaction(function () use ($item, $softDelete) {
            $cart = $item->cart;

            if ($softDelete) {
                $item->update(['status' => 'removed']);
            } else {
                $item->delete();
            }

            $this->recalculateTotals($cart);

            return true;
        });
    }

    /**
     * Clear all items from cart
     */
    public function clearCart(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            $cart->items()->delete();
            $cart->update([
                'total_price' => 0,
                'total_quantity' => 0,
            ]);
        });
    }

    /**
     * Recalculate cart totals
     */
    public function recalculateTotals(Cart $cart): Cart
    {
        $totals = $cart->items()
            ->where('status', 'active')
            ->selectRaw('SUM(total_price) as total_price, SUM(quantity) as total_quantity')
            ->first();

        $cart->update([
            'total_price' => $totals->total_price ?? 0,
            'total_quantity' => $totals->total_quantity ?? 0,
        ]);

        return $cart->fresh();
    }

    /**
     * Validate cart before checkout
     * @param bool $skipPriceCheck Skip price validation (useful for admin creating orders)
     */
    public function validateCart(Cart $cart, bool $skipPriceCheck = false): array
    {
        $errors = [];

        if ($cart->items()->where('status', 'active')->count() === 0) {
            $errors[] = 'Giỏ hàng trống.';
            return $errors;
        }

        // Validate và cập nhật giá Flash Sale trước
        if (!$skipPriceCheck) {
            $this->flashSaleService->validateCartPrices($cart);
            $cart->refresh();
        }

        foreach ($cart->items()->where('status', 'active')->with(['product', 'variant'])->get() as $item) {
            // Check product active
            if (!$item->product || !$item->product->is_active) {
                $errors[] = "Sản phẩm '{$item->product->name}' không còn hoạt động.";
                continue;
            }

            // Check variant active
            if ($item->variant && (!$item->variant->status || (int) $item->variant->product_id !== (int) $item->product_id)) {
                $errors[] = "Biến thể của sản phẩm '{$item->product->name}' không hợp lệ.";
                continue;
            }

            // Check stock dựa trên tồn khả dụng
            $available = $this->inventoryService->getAvailableStock($item->product, $item->variant);
            if ($available < $item->quantity) {
                $errors[] = "Sản phẩm '{$item->product->name}' chỉ còn {$available} sản phẩm khả dụng (bạn đã chọn {$item->quantity}).";
            }

            // Check price changed (skip if admin is creating order)
            if (!$skipPriceCheck) {
                // Lấy giá đúng từ Flash Sale hoặc variant/product
                $flashSalePrice = $this->flashSaleService->getPriceForVariant(
                    $item->product_id,
                    $item->product_variant_id
                );

                $currentPrice = $flashSalePrice
                    ? $flashSalePrice['price']
                    : ($item->variant
                        ? ($item->variant->sale_price ?? $item->variant->price ?? $item->product->sale_price ?? $item->product->price)
                        : ($item->product->sale_price ?? $item->product->price));

                if (abs($currentPrice - $item->price) > 0.01) {
                    $errors[] = "Giá sản phẩm '{$item->product->name}' đã thay đổi. Vui lòng cập nhật giỏ hàng.";
                }
            }
        }

        return $errors;
    }

    /**
     * Mark cart as ordered
     */
    public function markAsOrdered(Cart $cart, ?string $orderCode = null): Cart
    {
        // Chỉ update status, không đổi code của cart
        $cart->update([
            'status' => 'ordered',
        ]);

        return $cart->fresh();
    }

    /**
     * Mark abandoned carts
     */
    public function markAbandonedCarts(int $daysInactive = 7): int
    {
        $cutoffDate = now()->subDays($daysInactive);

        return Cart::where('status', 'active')
            ->where('updated_at', '<', $cutoffDate)
            ->update(['status' => 'abandoned']);
    }

    /**
     * Generate unique cart code
     */
    private function generateCartCode(): string
    {
        do {
            $code = 'CART-' . time() . '-' . Str::random(6);
        } while (Cart::where('code', $code)->exists());

        return $code;
    }

}

