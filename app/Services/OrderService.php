<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\FlashSaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    protected CartService $cartService;
    protected GHNService $ghnService;
    protected InventoryService $inventoryService;

    public function __construct(
        CartService $cartService,
        GHNService $ghnService,
        InventoryService $inventoryService
    ) {
        $this->cartService = $cartService;
        $this->ghnService = $ghnService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Tạo đơn hàng từ giỏ hàng
     */
    public function createOrderFromCart(\App\Models\Cart $cart, array $orderData): Order
    {
        return DB::transaction(function () use ($cart, $orderData) {
            // Validate cart (skip price check for admin-created orders)
            $errors = $this->cartService->validateCart($cart, skipPriceCheck: true);
            if (!empty($errors)) {
                throw new \Exception(implode(' ', $errors));
            }

            // Check cart status
            if ($cart->status !== 'active') {
                throw new \Exception('Giỏ hàng không ở trạng thái hoạt động.');
            }

            // Generate order code
            $orderCode = $this->generateOrderCode();

            // Calculate totals
            $totalPrice = $cart->total_price;
            $shippingFee = $orderData['shipping_fee'] ?? 0;
            $tax = $orderData['tax'] ?? 0;
            $discount = $orderData['discount'] ?? 0;
            $voucherDiscount = $orderData['voucher_discount'] ?? 0;
            $finalPrice = $totalPrice + $shippingFee + $tax - $discount - $voucherDiscount;

            // Create order
            $order = Order::create([
                'code' => $orderCode,
                'account_id' => $cart->account_id,
                'session_id' => $cart->session_id,
                'total_price' => $totalPrice,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'discount' => $discount,
                'voucher_id' => $orderData['voucher_id'] ?? null,
                'voucher_discount' => $voucherDiscount,
                'voucher_code' => $orderData['voucher_code'] ?? null,
                'final_price' => max(0, $finalPrice),
                'receiver_name' => $orderData['receiver_name'],
                'receiver_phone' => $orderData['receiver_phone'],
                'receiver_email' => $orderData['receiver_email'] ?? null,
                'shipping_address' => $orderData['shipping_address'],
                'shipping_province_id' => $orderData['shipping_province_id'],
                'shipping_district_id' => $orderData['shipping_district_id'],
                'shipping_ward_id' => $orderData['shipping_ward_id'],
                'payment_method' => $orderData['payment_method'] ?? 'cod',
                'payment_status' => $orderData['payment_status'] ?? 'pending',
                'shipping_partner' => $orderData['shipping_partner'] ?? 'viettelpost',
                'delivery_status' => 'pending',
                'status' => $orderData['status'] ?? 'pending',
                'customer_note' => $orderData['customer_note'] ?? null,
                'admin_note' => $orderData['admin_note'] ?? null,
            ]);

            // Create order items from cart items
            $cartItems = $cart->items()->where('status', 'active')->with(['product', 'variant'])->get();
            
            $orderHasFlashSaleItems = false;
            $flashSaleAdjustments = [];
            $inventoryRequests = [];
            $orderItemPayloads = [];
            
            foreach ($cartItems as $cartItem) {
                if (!$cartItem->product) {
                    throw new \Exception('Một sản phẩm trong giỏ hàng đã không còn tồn tại.');
                }

                $key = $cartItem->product_variant_id
                    ? 'variant_' . $cartItem->product_variant_id
                    : 'product_' . $cartItem->product_id;

                if (!isset($inventoryRequests[$key])) {
                    $inventoryRequests[$key] = [
                        'product' => $cartItem->product,
                        'variant' => $cartItem->variant,
                        'quantity' => 0,
                    ];
                }
                $inventoryRequests[$key]['quantity'] += $cartItem->quantity;

                $flashSaleContext = $this->determineFlashSaleContext(
                    $cartItem->product_id,
                    $cartItem->flash_sale_item_id,
                    $cartItem->is_flash_sale
                );

                if ($flashSaleContext['is_flash_sale']) {
                    $orderHasFlashSaleItems = true;
                    $flashSaleAdjustments[] = [
                        'flash_sale_item_id' => $flashSaleContext['flash_sale_item_id'],
                        'quantity' => $cartItem->quantity,
                    ];
                }

                $orderItemPayloads[] = [
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total_price' => $cartItem->total_price,
                    'flash_sale_context' => $flashSaleContext,
                ];
            }

            foreach ($inventoryRequests as $request) {
                $this->inventoryService->ensureSufficientStock(
                    $request['product'],
                    $request['variant'],
                    $request['quantity']
                );
            }

            if (!empty($flashSaleAdjustments)) {
                $this->applyFlashSaleAdjustments($flashSaleAdjustments, 'increment');
            }

            foreach ($orderItemPayloads as $payload) {
                OrderItem::create([
                    'uuid' => Str::uuid()->toString(),
                    'order_id' => $order->id,
                    'product_id' => $payload['product_id'],
                    'product_variant_id' => $payload['product_variant_id'],
                    'quantity' => $payload['quantity'],
                    'price' => $payload['price'],
                    'total_price' => $payload['total_price'],
                    'is_flash_sale' => $payload['flash_sale_context']['is_flash_sale'],
                    'flash_sale_item_id' => $payload['flash_sale_context']['flash_sale_item_id'],
                ]);
            }

            // Mark cart as ordered
            $this->cartService->markAsOrdered($cart, $orderCode);

            $order->update([
                'is_flash_sale' => $orderHasFlashSaleItems,
            ]);

            return $order->fresh(['items']);
        });
    }

    /**
     * Tạo đơn hàng thủ công
     */
    public function createOrder(array $data, array $items): Order
    {
        return DB::transaction(function () use ($data, $items) {
            // Generate order code
            $code = $this->generateOrderCode();

            // Calculate total_price from items
            $totalPrice = 0;
            foreach ($items as $item) {
                $itemTotal = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                $totalPrice += $itemTotal;
            }

            // Calculate final_price
            $shippingFee = $data['shipping_fee'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $discount = $data['discount'] ?? 0;
            $voucherDiscount = $data['voucher_discount'] ?? 0;
            $finalPrice = $totalPrice + $shippingFee + $tax - $discount - $voucherDiscount;

            // Create order
            $order = Order::create([
                'code' => $code,
                'account_id' => $data['account_id'] ?? null,
                'session_id' => $data['session_id'] ?? null,
                'total_price' => $totalPrice,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'discount' => $discount,
                'voucher_id' => $data['voucher_id'] ?? null,
                'voucher_discount' => $voucherDiscount,
                'voucher_code' => $data['voucher_code'] ?? null,
                'final_price' => max(0, $finalPrice),
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'receiver_email' => $data['receiver_email'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'shipping_province_id' => $data['shipping_province_id'],
                'shipping_district_id' => $data['shipping_district_id'],
                'shipping_ward_id' => $data['shipping_ward_id'],
                'payment_method' => $data['payment_method'] ?? 'cod',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'transaction_code' => $data['transaction_code'] ?? null,
                'shipping_partner' => $data['shipping_partner'] ?? 'viettelpost',
                'shipping_tracking_code' => $data['shipping_tracking_code'] ?? null,
                'delivery_status' => $data['delivery_status'] ?? 'pending',
                'status' => $data['status'] ?? 'pending',
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            // Create order items
            $orderHasFlashSaleItems = false;
            $flashSaleAdjustments = [];
            $inventoryRequests = [];
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = null;

                if (!empty($item['product_variant_id'])) {
                    $variant = ProductVariant::findOrFail($item['product_variant_id']);
                    // So sánh sau khi cast về int để tránh lỗi kiểu dữ liệu (string vs int)
                    if ((int) $variant->product_id !== (int) $product->id) {
                        throw new \Exception('Biến thể không thuộc sản phẩm đã chọn.');
                    }
                }

                $key = $variant ? 'variant_' . $variant->id : 'product_' . $product->id;
                if (!isset($inventoryRequests[$key])) {
                    $inventoryRequests[$key] = [
                        'product' => $product,
                        'variant' => $variant,
                        'quantity' => 0,
                    ];
                }
                $inventoryRequests[$key]['quantity'] += $item['quantity'];

                $flashSaleContext = $this->determineFlashSaleContext(
                    $item['product_id'],
                    $item['flash_sale_item_id'] ?? null,
                    $item['is_flash_sale'] ?? null
                );

                if ($flashSaleContext['is_flash_sale']) {
                    $orderHasFlashSaleItems = true;
                    $flashSaleAdjustments[] = [
                        'flash_sale_item_id' => $flashSaleContext['flash_sale_item_id'],
                        'quantity' => $item['quantity'],
                    ];
                }

                $preparedItems[] = [
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'flash_sale_context' => $flashSaleContext,
                ];
            }

            foreach ($inventoryRequests as $request) {
                $this->inventoryService->ensureSufficientStock(
                    $request['product'],
                    $request['variant'],
                    $request['quantity']
                );
            }

            if (!empty($flashSaleAdjustments)) {
                $this->applyFlashSaleAdjustments($flashSaleAdjustments, 'increment');
            }

            foreach ($preparedItems as $payload) {
                OrderItem::create([
                    'uuid' => Str::uuid()->toString(),
                    'order_id' => $order->id,
                    'product_id' => $payload['product_id'],
                    'product_variant_id' => $payload['product_variant_id'],
                    'quantity' => $payload['quantity'],
                    'price' => $payload['price'],
                    'total_price' => $payload['quantity'] * $payload['price'],
                    'is_flash_sale' => $payload['flash_sale_context']['is_flash_sale'],
                    'flash_sale_item_id' => $payload['flash_sale_context']['flash_sale_item_id'],
                ]);
            }

            $order->update([
                'is_flash_sale' => $orderHasFlashSaleItems,
            ]);

            return $order->fresh(['items']);
        });
    }

    /**
     * Cập nhật đơn hàng
     */
    public function updateOrder(Order $order, array $data, ?array $items = null): Order
    {
        return DB::transaction(function () use ($order, $data, $items) {
            $order->loadMissing('items');
            // Check if order can be edited
            if (in_array($order->status, ['completed', 'cancelled'])) {
                throw new \Exception('Không thể sửa đơn hàng đã hoàn thành hoặc đã hủy.');
            }

            // Update order fields
            $order->update(array_filter($data, function ($key) {
                return !in_array($key, ['items']);
            }, ARRAY_FILTER_USE_KEY));

            // Update items if provided
            if ($items !== null) {
                $orderHasFlashSaleItems = false;
                $newFlashSaleAdjustments = [];
                $inventoryRequests = [];
                $preparedItems = [];

                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $variant = null;
                    if (!empty($item['product_variant_id'])) {
                        $variant = ProductVariant::findOrFail($item['product_variant_id']);
                        if ($variant->product_id !== $product->id) {
                            throw new \Exception('Biến thể không thuộc sản phẩm đã chọn.');
                        }
                    }

                    $key = $variant ? 'variant_' . $variant->id : 'product_' . $product->id;
                    if (!isset($inventoryRequests[$key])) {
                        $inventoryRequests[$key] = [
                            'product' => $product,
                            'variant' => $variant,
                            'quantity' => 0,
                        ];
                    }
                    $inventoryRequests[$key]['quantity'] += $item['quantity'];

                    $flashSaleContext = $this->determineFlashSaleContext(
                        $item['product_id'],
                        $item['flash_sale_item_id'] ?? null,
                        $item['is_flash_sale'] ?? null
                    );

                    if ($flashSaleContext['is_flash_sale']) {
                        $orderHasFlashSaleItems = true;
                        $newFlashSaleAdjustments[] = [
                            'flash_sale_item_id' => $flashSaleContext['flash_sale_item_id'],
                            'quantity' => $item['quantity'],
                        ];
                    }

                    $preparedItems[] = [
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'flash_sale_context' => $flashSaleContext,
                    ];
                }

                foreach ($inventoryRequests as $request) {
                    $this->inventoryService->ensureSufficientStock(
                        $request['product'],
                        $request['variant'],
                        $request['quantity'],
                        $order->id
                    );
                }

                // Delete existing items + revert flash sale sold counts
                $existingAdjustments = $this->buildFlashSaleAdjustmentsFromItems($order->items);
                if (!empty($existingAdjustments)) {
                    $this->applyFlashSaleAdjustments($existingAdjustments, 'decrement');
                }
                $order->items()->delete();

                foreach ($preparedItems as $payload) {
                    OrderItem::create([
                        'uuid' => Str::uuid()->toString(),
                        'order_id' => $order->id,
                        'product_id' => $payload['product_id'],
                        'product_variant_id' => $payload['product_variant_id'],
                        'quantity' => $payload['quantity'],
                        'price' => $payload['price'],
                        'total_price' => $payload['quantity'] * $payload['price'],
                        'is_flash_sale' => $payload['flash_sale_context']['is_flash_sale'],
                        'flash_sale_item_id' => $payload['flash_sale_context']['flash_sale_item_id'],
                    ]);
                }

                if (!empty($newFlashSaleAdjustments)) {
                    $this->applyFlashSaleAdjustments($newFlashSaleAdjustments, 'increment');
                }

                $order->update([
                    'is_flash_sale' => $orderHasFlashSaleItems,
                ]);
            }

            // Recalculate totals
            $this->recalculateOrderTotals($order);

            return $order->fresh(['items']);
        });
    }

    /**
     * Tính lại tổng tiền đơn hàng
     */
    public function recalculateOrderTotals(Order $order): Order
    {
        // Calculate total_price from items
        $totalPrice = $order->items()->sum(DB::raw('quantity * price'));

        // Update items total_price
        $order->items()->each(function ($item) {
            $item->update([
                'total_price' => $item->quantity * $item->price,
            ]);
        });

        // Calculate final_price
        $finalPrice = $totalPrice 
            + ($order->shipping_fee ?? 0)
            + ($order->tax ?? 0)
            - ($order->discount ?? 0)
            - ($order->voucher_discount ?? 0);

        // Update order
        $order->update([
            'total_price' => $totalPrice,
            'final_price' => max(0, $finalPrice),
            'is_flash_sale' => $order->items()->where('is_flash_sale', true)->exists(),
        ]);

        return $order->fresh();
    }

    protected function determineFlashSaleContext(int $productId, ?int $flashSaleItemId = null, $isFlashSale = null): array
    {
        $flashSaleItem = null;

        if ($flashSaleItemId) {
            $flashSaleItem = FlashSaleItem::find($flashSaleItemId);
        } elseif ($isFlashSale === null || $isFlashSale) {
            $flashSaleItem = $this->resolveActiveFlashSaleItem($productId);
        }

        return [
            'is_flash_sale' => (bool) $flashSaleItem,
            'flash_sale_item_id' => $flashSaleItem?->id,
        ];
    }

    protected function resolveActiveFlashSaleItem(int $productId): ?FlashSaleItem
    {
        return FlashSaleItem::where('product_id', $productId)
            ->where('is_active', true)
            ->whereHas('flashSale', function ($query) {
                $query->where('is_active', true)
                    ->where('status', 'active')
                    ->where('start_time', '<=', now())
                    ->where('end_time', '>=', now());
            })
            ->first();
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateOrderStatus(Order $order, string $status, ?string $paymentStatus = null, ?string $deliveryStatus = null): Order
    {
        $updates = ['status' => $status];

        if ($paymentStatus !== null) {
            $updates['payment_status'] = $paymentStatus;
        }

        if ($deliveryStatus !== null) {
            $updates['delivery_status'] = $deliveryStatus;
        }

        // Logic constraints
        if ($status === 'completed') {
            $updates['delivery_status'] = 'delivered';
        }

        $order->update($updates);

        return $order->fresh();
    }

    /**
     * Hủy đơn hàng
     */
    public function cancelOrder(Order $order, ?string $note = null, bool $restoreStock = true): Order
    {
        return DB::transaction(function () use ($order, $note, $restoreStock) {
            // Check if can cancel
            if (!$order->canCancel()) {
                throw new \Exception('Đơn hàng này không thể hủy.');
            }

            // Cancel on GHN if needed before local changes
            if ($order->shipping_partner === 'ghn' && $order->shipping_tracking_code) {
                $result = $this->ghnService->cancelOrder($order, $note);
                if (!$result['success']) {
                    throw new \Exception('GHN: ' . ($result['error'] ?? 'Không thể hủy vận đơn.'));
                }
            }

            if ($restoreStock && $order->delivery_status === 'delivered') {
                $this->handleOrderDeliveryReverted($order);
            }

            $order->loadMissing('items');
            $flashSaleAdjustments = $this->buildFlashSaleAdjustmentsFromItems($order->items);
            if (!empty($flashSaleAdjustments)) {
                $this->applyFlashSaleAdjustments($flashSaleAdjustments, 'decrement');
            }

            // Update order
            $order->update([
                'status' => 'cancelled',
                'delivery_status' => 'cancelled',
                'admin_note' => $note ? ($order->admin_note ? $order->admin_note . "\n" . $note : $note) : $order->admin_note,
            ]);

            return $order->fresh();
        });
    }

    /**
     * Hoàn thành đơn hàng
     */
    public function completeOrder(Order $order): Order
    {
        $order->update([
            'status' => 'completed',
            'delivery_status' => 'delivered',
        ]);

        return $order->fresh();
    }

    /**
     * Hoàn trả stock
     */
    public function restoreStock(Order $order): void
    {
        $this->handleOrderDeliveryReverted($order);
    }

    protected function buildFlashSaleAdjustmentsFromItems($items): array
    {
        $adjustments = [];

        foreach ($items as $item) {
            if (!$item->flash_sale_item_id || $item->quantity <= 0) {
                continue;
            }

            $adjustments[] = [
                'flash_sale_item_id' => $item->flash_sale_item_id,
                'quantity' => $item->quantity,
            ];
        }

        return $adjustments;
    }

    protected function applyFlashSaleAdjustments(array $rawAdjustments, string $direction = 'increment'): void
    {
        $grouped = $this->normalizeFlashSaleAdjustments($rawAdjustments);

        if (empty($grouped)) {
            return;
        }

        $items = FlashSaleItem::whereIn('id', array_keys($grouped))->get();

        foreach ($items as $item) {
            $quantity = $grouped[$item->id];

            if ($direction === 'decrement') {
                $newSold = max(0, $item->sold - $quantity);
                if ($newSold !== $item->sold) {
                    $item->update(['sold' => $newSold]);
                }
            } else {
                $item->increment('sold', $quantity);
            }
        }
    }

    protected function normalizeFlashSaleAdjustments(array $rawAdjustments): array
    {
        $grouped = [];

        foreach ($rawAdjustments as $adjustment) {
            $itemId = isset($adjustment['flash_sale_item_id'])
                ? (int) $adjustment['flash_sale_item_id']
                : 0;
            $quantity = isset($adjustment['quantity'])
                ? (int) $adjustment['quantity']
                : 0;

            if ($itemId <= 0 || $quantity <= 0) {
                continue;
            }

            $grouped[$itemId] = ($grouped[$itemId] ?? 0) + $quantity;
        }

        return $grouped;
    }

    /**
     * Generate unique order code
     */
    public function generateOrderCode(): string
    {
        do {
            $code = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::where('code', $code)->exists());

        return $code;
    }

    public function handleOrderDelivered(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->decrement('stock_quantity', $item->quantity);
                } else {
                    Product::where('id', $item->product_id)
                        ->decrement('stock_quantity', $item->quantity);
                }
            }
        });
    }

    public function handleOrderDeliveryReverted(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->increment('stock_quantity', $item->quantity);
                } else {
                    Product::where('id', $item->product_id)
                        ->increment('stock_quantity', $item->quantity);
                }
            }
        });
    }
}
