<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AddressService;
use App\Services\CartService;
use App\Services\FlashSaleService;
use App\Services\OrderService;
use App\Services\PayOSService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    protected $voucherService;
    protected $payOSService;
    protected $cartService;
    protected $orderService;
    protected $flashSaleService;
    protected AddressService $addressService;

    public function __construct(
        VoucherService $voucherService,
        PayOSService $payOSService,
        CartService $cartService,
        OrderService $orderService,
        FlashSaleService $flashSaleService,
        AddressService $addressService
    ) {
        $this->voucherService = $voucherService;
        $this->payOSService = $payOSService;
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->flashSaleService = $flashSaleService;
        $this->addressService = $addressService;
    }

    /**
     * Kiểm tra xem user có đơn hàng pending cần thanh toán không
     */
    private function hasPendingOrder(): ?Order
    {
        $userId = auth('web')->id();
        $sessionId = session()->getId();

        $pendingOrder = Order::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('account_id', $userId);
            } else {
                $query->whereNull('account_id')->where('session_id', $sessionId);
            }
        })
        ->where('status', 'pending')
        ->where('payment_status', 'pending')
        ->whereIn('payment_method', ['bank', 'momo', 'payos', 'bank_transfer'])
        ->latest()
        ->first();

        return $pendingOrder;
    }

    public function addressesEndpoint(Request $request)
    {
        $account = $request->user();

        if (!$account) {
            abort(403);
        }

        return AddressResource::collection(
            $this->addressService->listForAccount($account)
        );
    }

    public function selectAddress(Request $request)
    {
        $account = $request->user();

        if (!$account) {
            abort(403);
        }

        $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
        ]);

        $address = $account->addresses()->findOrFail($request->address_id);

        return response()->json([
            'success' => true,
            'address' => new AddressResource($address),
            'message' => 'Đã cập nhật địa chỉ giao hàng.',
        ]);
    }

    /**
     * Sanitize input data to prevent XSS attacks
     */
    private function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        if (is_string($data)) {
            // Remove HTML tags and encode special characters
            $data = strip_tags($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            // Remove potential script injections
            $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
            $data = preg_replace('/javascript:/i', '', $data);
            $data = preg_replace('/on\w+\s*=/i', '', $data);
            return trim($data);
        }
        
        return $data;
    }

    /**
     * Chuẩn hóa và validate voucher dựa trên dữ liệu đơn hàng
     */
    private function evaluateVoucherForOrder(?string $voucherCode, array $orderItems, float $shippingFee): array
    {
        if (empty($voucherCode)) {
            return [
                'voucher_id' => null,
                'voucher_code' => null,
                'discount' => 0,
            ];
        }

        $userId = auth('web')->id();
        if (!$userId) {
            throw new \Exception('Vui lòng đăng nhập để sử dụng voucher.');
        }

        $orderData = [
            'items' => $orderItems,
            'shipping_fee' => $shippingFee,
        ];

        $result = $this->voucherService->validateAndApplyVoucher($voucherCode, $orderData, $userId);

        if (!$result['success']) {
            throw new \Exception($result['message']);
        }

        return [
            'voucher_id' => $result['voucher']->id ?? null,
            'voucher_code' => $voucherCode,
            'discount' => (float) $result['discount_amount'],
        ];
    }
    public function checkoutCartItem($uuid)
    {
        // Kiểm tra xem có đơn hàng pending cần thanh toán không
        $pendingOrder = $this->hasPendingOrder();
        
        if ($pendingOrder) {
            return redirect()->route('payment.pending')
                ->with('warning', 'Bạn có đơn hàng đang chờ thanh toán. Vui lòng thanh toán hoặc hủy đơn hàng trước khi tạo đơn mới.');
        }

        $cartItem = CartItem::active()->with(['variant', 'product', 'cart'])->where('uuid', $uuid)->first();
        $productNew = Product::active()->with('primaryImage')->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];

        if (!$cartItem) {
            return redirect()->route('client.cart.index')->with('warning', 'Giỏ hàng không tồn tại! Vui lòng thử lại!');
        }

        // Validate và cập nhật giá Flash Sale trước khi checkout
        if ($cartItem->cart) {
            $this->flashSaleService->validateCartPrices($cartItem->cart);
            $cartItem->refresh();
        }

        $addresses = auth('web')->check()
            ? $this->addressService->listForAccount(auth('web')->user())
            : collect();
        $defaultAddress = auth('web')->check()
            ? $this->addressService->getDefaultForAccount(auth('web')->user())
            : null;

        return view('clients.pages.checkout.index', compact('cartItem', 'productNew', 'addresses', 'defaultAddress'));
    }

    public function createCheckoutItem(Request $request)
    {
        // Kiểm tra xem có đơn hàng pending cần thanh toán không
        $pendingOrder = $this->hasPendingOrder();
        
        if ($pendingOrder) {
            return redirect()->route('payment.pending')
                ->with('error', 'Bạn có đơn hàng đang chờ thanh toán. Vui lòng thanh toán hoặc hủy đơn hàng trước khi tạo đơn mới.');
        }

        $validated = $request->validate([
            'productId' => 'required|numeric|exists:products,id',
            'uuid' => 'nullable|string|size:36|uuid',
            'fullname' => 'required|string|min:4|max:100|regex:/^[A-Za-zÀ-ỹ\s\'\.\-]+$/',
            'email' => 'nullable|email|max:150',
            'phone' => ['required', 'regex:/^(0|\+84)\d{9}$/'],
            'address' => 'required|string|min:10|max:255|regex:/^[A-Za-zÀ-ỹ0-9\s\.,\-\/]+$/',

            'provinceId' => 'required|numeric',
            'districtId' => 'required|numeric',
            'wardId' => 'required|string|regex:/^[A-Za-z0-9]+$/',

            'serviceId' => 'required|numeric',
            'serviceTypeId' => 'required|numeric',

            'shipping' => 'required|numeric',
            'shipping_fee' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:1000',
            'total' => 'required|numeric|min:1000',
            'payment' => 'required|in:cod,bank,momo,payos',
            'items' => 'required|string',
            'voucher_code' => 'nullable|string|max:50',
            'voucher_discount' => 'nullable|numeric|min:0',
            'customer_note' => 'nullable|string|max:500|regex:/^[A-Za-zÀ-ỹ0-9\s\.,\-\/!?()]+$/',
        ]);

        // Sanitize all string inputs to prevent XSS
        $validated = $this->sanitizeInput($validated);

        $items = json_decode($validated['items'], true);
        if (!is_array($items) || empty($items)) {
            return back()->withErrors(['items' => 'Dữ liệu sản phẩm không hợp lệ!']);
        }

        // Cache sản phẩm gốc và slug để dùng ổn định trong các redirect
        $baseProduct = Product::active()->find($validated['productId']);
        if (!$baseProduct) {
            return redirect()->route('client.cart.index')->with('error', 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.');
        }
        $productSlug = $baseProduct->slug;

        DB::beginTransaction();
        try {
            $userId = auth('web')->id();
            $sessionId = session()->getId();

            // 🧭 Kiểm tra order pending
            $existingOrder = Order::where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('account_id', $userId);
                } else {
                    $q->whereNull('account_id')->where('session_id', $sessionId);
                }
            })
                ->where('status', 'pending')
                ->first();

            // =============================
            // 🧾 TRƯỜNG HỢP 1: ĐÃ CÓ ORDER PENDING
            // =============================
            if ($existingOrder) {
                foreach ($items as $item) {
                    $product = $baseProduct; // dùng sản phẩm đã cache

                    // Tìm variant
                    $variant = $this->findVariant($product, $item['attributes'] ?? null);

                    // Kiểm tra tồn kho hiện tại
                    $availableStock = (int) ($variant ? $variant->stock_quantity : $product->stock_quantity);
                    $itemQty = (int) ($item['quantity'] ?? 1);
                    $itemQty = max(1, $itemQty);

                    // Tìm sản phẩm trong đơn cũ
                    $existingItem = OrderItem::where('order_id', $existingOrder->id)
                        ->where('product_id', $product->id)
                        ->where('product_variant_id', $variant->id ?? null)
                        ->first();

                    if ($existingItem) {
                        $newQuantity = (int) $existingItem->quantity + $itemQty;

                    if ($newQuantity > $availableStock) {
                        DB::rollBack();
                        if (!empty($validated['uuid'])) {
                            if ($cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first()) {
                                $cartItem->update(['status' => 'removed']);
                            }
                        }
                            return redirect()
                                ->route('client.product.detail', $productSlug)
                                ->with('error', "Không thể cập nhật sản phẩm '{$product->name}' — chỉ còn {$availableStock} sản phẩm trong kho!");
                        }

                        // Cập nhật số lượng
                        $existingItem->update([
                            'quantity' => $newQuantity,
                            'total_price' => $newQuantity * $existingItem->price,
                        ]);
                    } else {
                        if ($itemQty > $availableStock) {
                            DB::rollBack();
                            if (!empty($validated['uuid'])) {
                                if ($cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first()) {
                                    $cartItem->update(['status' => 'removed']);
                                }
                            }
                            return redirect()
                                ->route('client.product.detail', $productSlug)
                                ->with('error', "Sản phẩm '{$product->name}' không đủ hàng tồn (chỉ còn {$availableStock}).");
                        }

                        $flashSaleContext = $this->resolveFlashSaleContext($product, $variant->id ?? null);
                        // Backend override giá: nếu có Flash Sale, dùng giá từ Flash Sale (không tin frontend)
                        $linePrice = $flashSaleContext['is_flash_sale']
                            ? $flashSaleContext['price']  // Override với giá đúng từ backend
                            : $item['price'];
                        $lineTotal = $itemQty * $linePrice;

                        OrderItem::create([
                            'uuid' => Str::uuid(),
                            'order_id' => $existingOrder->id,
                            'product_id' => $product->id,
                            'product_variant_id' => $variant->id ?? null,
                            'quantity' => $itemQty,
                            'price' => $linePrice,
                            'total_price' => $lineTotal,
                            'is_flash_sale' => $flashSaleContext['is_flash_sale'],
                            'flash_sale_item_id' => $flashSaleContext['flash_sale_item_id'],
                        ]);
                    }
                }

                // Tính lại total_price từ order_items (đã validate giá Flash Sale)
                $calculatedTotalPrice = $existingOrder->items()->sum('total_price');
                
                // Validate voucher từ server
                $voucherDiscount = 0;
                $voucherId = null;
                $voucherCode = null;
                if (!empty($validated['voucher_code'])) {
                    try {
                        $orderItemsForVoucher = $existingOrder->items()
                            ->with('product')
                            ->get()
                            ->map(function ($orderItem) {
                                return [
                                    'product_id' => (int) $orderItem->product_id,
                                    'quantity' => (int) $orderItem->quantity,
                                    'price' => (float) $orderItem->price,
                                    'total_price' => (float) $orderItem->total_price,
                                    'category_id' => optional($orderItem->product)->primary_category_id,
                                ];
                            })
                            ->toArray();

                        $voucherMeta = $this->evaluateVoucherForOrder(
                            $validated['voucher_code'],
                            $orderItemsForVoucher,
                            (float) $validated['shipping_fee']
                        );

                        $voucherDiscount = $voucherMeta['discount'];
                        $voucherId = $voucherMeta['voucher_id'];
                        $voucherCode = $voucherMeta['voucher_code'];
                    } catch (\Exception $voucherException) {
                        DB::rollBack();
                        if (!empty($validated['uuid'])) {
                            if ($cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first()) {
                                $cartItem->update(['status' => 'removed']);
                            }
                        }
                        return redirect()->route('client.product.detail', $productSlug)
                            ->with('error', $voucherException->getMessage());
                    }
                }
                
                // Tính final_price: total_price + shipping_fee - voucher_discount
                $calculatedFinalPrice = $calculatedTotalPrice + (float) $validated['shipping_fee'] - $voucherDiscount;

                $existingOrder->update([
                    'total_price' => $calculatedTotalPrice, // Tính lại từ order_items
                    'final_price' => $calculatedFinalPrice, // Tính lại với voucher
                    'shipping_fee' => $validated['shipping_fee'],
                    'receiver_name' => $validated['fullname'],
                    'receiver_phone' => $validated['phone'],
                    'receiver_email' => $validated['email'] ?? null,
                    'shipping_address' => $validated['address'],
                    'customer_note' => $validated['customer_note'] ?? null,
                    'is_flash_sale' => $existingOrder->items()->where('is_flash_sale', true)->exists(),
                    'voucher_id' => $voucherId,
                    'voucher_code' => $voucherCode,
                    'voucher_discount' => $voucherDiscount,
                ]);

                DB::commit();
                if (!empty($validated['uuid'])) {
                    if ($cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first()) {
                        $cartItem->update(['status' => 'removed']);
                    }
                }

                return redirect()->route('client.product.detail', $productSlug)->with('success', 'Đơn hàng đang chờ được cập nhật thêm sản phẩm 🛒');
            }

            // =============================
            // 🧾 TRƯỜNG HỢP 2: TẠO ORDER MỚI
            // =============================
            $orderPaymentMethod = isset($validated['payment']) && in_array($validated['payment'], ['payos', 'bank'])
                ? 'bank_transfer'
                : ($validated['payment'] ?? 'cod');

            // Tạo order tạm với giá từ frontend (sẽ tính lại sau)
            $order = Order::create([
                'code' => $this->orderService->generateOrderCode(),
                'account_id' => $userId,
                'session_id' => $sessionId,
                'total_price' => 0, // Tạm thời, sẽ tính lại sau
                'shipping_fee' => $validated['shipping_fee'],
                'final_price' => 0, // Tạm thời, sẽ tính lại sau
                'voucher_id' => null,
                'voucher_discount' => 0,
                'voucher_code' => null,
                'receiver_name' => $validated['fullname'],
                'receiver_phone' => $validated['phone'],
                'receiver_email' => $validated['email'] ?? null,
                'shipping_address' => $validated['address'],
                'shipping_province_id' => $validated['provinceId'],
                'shipping_district_id' => $validated['districtId'],
                'shipping_ward_id' => $validated['wardId'],
                'payment_method' => $orderPaymentMethod,
                'payment_status' => 'pending',
                'shipping_partner' => 'ghn',
                'status' => 'pending',
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            $orderHasFlashSaleItems = false;
            $needsVoucherValidation = !empty($validated['voucher_code']);
            $orderItemsForVoucher = [];
            foreach ($items as $item) {
                $product = $baseProduct; // dùng sản phẩm đã cache

                $variant = $this->findVariant($product, $item['attributes'] ?? null);
                $availableStock = (int) ($variant ? $variant->stock_quantity : $product->stock_quantity);
                $itemQty = (int) ($item['quantity'] ?? 1);
                $itemQty = max(1, $itemQty);

                if ($itemQty > $availableStock) {
                    DB::rollBack();
                    if (!empty($validated['uuid'])) {
                        if ($cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first()) {
                            $cartItem->update(['status' => 'removed']);
                        }
                    }
                    return redirect()
                        ->route('client.product.detail', $productSlug)
                        ->with('error', "Sản phẩm '{$product->name}' không đủ hàng tồn (chỉ còn {$availableStock}).");
                }

                $flashSaleContext = $this->resolveFlashSaleContext($product, $variant->id ?? null);
                // Backend override giá: nếu có Flash Sale, dùng giá từ Flash Sale (không tin frontend)
                $linePrice = $flashSaleContext['is_flash_sale']
                    ? $flashSaleContext['price']  // Override với giá đúng từ backend
                    : $item['price'];
                $lineTotal = $itemQty * $linePrice;

                if ($flashSaleContext['is_flash_sale']) {
                    $orderHasFlashSaleItems = true;
                }

                if ($needsVoucherValidation) {
                    $orderItemsForVoucher[] = [
                        'product_id' => $product->id,
                        'quantity' => $itemQty,
                        'price' => $linePrice,
                        'total_price' => $lineTotal,
                        'category_id' => $product->primary_category_id,
                    ];
                }

                OrderItem::create([
                    'uuid' => Str::uuid(),
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id ?? null,
                    'quantity' => $itemQty,
                    'price' => $linePrice,
                    'total_price' => $lineTotal,
                    'is_flash_sale' => $flashSaleContext['is_flash_sale'],
                    'flash_sale_item_id' => $flashSaleContext['flash_sale_item_id'],
                ]);
            }

            // Tính lại total_price và final_price từ order_items (đã validate giá Flash Sale)
            $calculatedTotalPrice = $order->items()->sum('total_price');
            
            // Validate voucher từ server
            $voucherDiscount = 0;
            $voucherId = null;
            $voucherCode = null;
            if ($needsVoucherValidation) {
                try {
                    $voucherMeta = $this->evaluateVoucherForOrder(
                        $validated['voucher_code'],
                        $orderItemsForVoucher,
                        (float) $validated['shipping_fee']
                    );
                    $voucherDiscount = $voucherMeta['discount'];
                    $voucherId = $voucherMeta['voucher_id'];
                    $voucherCode = $voucherMeta['voucher_code'];
                } catch (\Exception $voucherException) {
                    DB::rollBack();
                    if (!empty($validated['uuid'])) {
                        if ($cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first()) {
                            $cartItem->update(['status' => 'removed']);
                        }
                    }
                    return redirect()->route('client.product.detail', $productSlug)
                        ->with('error', $voucherException->getMessage());
                }
            }
            
            // Tính final_price: total_price + shipping_fee - voucher_discount
            $calculatedFinalPrice = $calculatedTotalPrice + (float) $validated['shipping_fee'] - $voucherDiscount;

            // Cập nhật order với giá đúng
            $order->update([
                'total_price' => $calculatedTotalPrice, // Tính lại từ order_items
                'final_price' => $calculatedFinalPrice, // Tính lại với voucher
                'is_flash_sale' => $orderHasFlashSaleItems,
                'voucher_id' => $voucherId,
                'voucher_discount' => $voucherDiscount,
                'voucher_code' => $voucherCode,
            ]);

            DB::commit();
            
            // Xóa item trong giỏ hàng sau khi tạo order thành công (nếu có uuid)
            if (!empty($validated['uuid'])) {
                $cartItem = CartItem::active()->where('uuid', $validated['uuid'])->first();
                if ($cartItem) {
                    $cartItem->update(['status' => 'removed']);
                    Log::info('Cart item removed after order creation', [
                        'cart_item_id' => $cartItem->id,
                        'uuid' => $validated['uuid'],
                        'order_id' => $order->id
                    ]);
                } else {
                    Log::warning('Cart item not found for removal', [
                        'uuid' => $validated['uuid'],
                        'order_id' => $order->id
                    ]);
                }
            }

            // Initiate online payment if not COD
            if (in_array($validated['payment'], ['bank','momo','payos'])) {
                try {
                    $result = $this->payOSService->createPaymentLink($order);
                    
                    Log::info('PayOS payment link creation result', [
                        'order_id' => $order->id,
                        'payment_method' => $validated['payment'],
                        'result' => $result
                    ]);
                    
                    if (!($result['success'] ?? false)) {
                        Log::warning('PayOS payment link creation failed', [
                            'order_id' => $order->id,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                        return redirect()->route('client.product.detail', $productSlug)
                            ->with('warning', $result['error'] ?? 'Không thể tạo liên kết thanh toán. Vui lòng thử lại hoặc chọn COD.');
                    }
                    
                    if (empty($result['checkout_url'])) {
                        Log::error('PayOS checkout_url is empty', [
                            'order_id' => $order->id,
                            'result' => $result
                        ]);
                        return redirect()->route('client.product.detail', $productSlug)
                            ->with('warning', 'Không thể tạo liên kết thanh toán. Vui lòng thử lại hoặc chọn COD.');
                    }
                    
                    Log::info('Redirecting to PayOS checkout', [
                        'order_id' => $order->id,
                        'checkout_url' => $result['checkout_url']
                    ]);
                    
                    // Sử dụng header Location trực tiếp để đảm bảo redirect hoạt động
                    return response('', 302)->header('Location', $result['checkout_url']);
                } catch (\Exception $e) {
                    Log::error('Exception when creating PayOS payment link', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return redirect()->route('client.product.detail', $productSlug)
                        ->with('warning', 'Không thể tạo liên kết thanh toán: ' . $e->getMessage() . '. Vui lòng thử lại hoặc chọn COD.');
                }
            }

            return redirect()->route('client.product.detail', $productSlug)->with('success', 'Đặt hàng thành công 🎉');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('createCheckoutItem error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
                'user_id' => auth('web')->id(),
                'session_id' => session()->getId(),
            ]);
            $msg = 'Lỗi khi tạo đơn hàng 😢';
            if (config('app.debug')) {
                $msg .= ' — ' . $e->getMessage();
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    public function checkoutCart(Request $request)
    {
        // Kiểm tra xem có đơn hàng pending cần thanh toán không
        $pendingOrder = $this->hasPendingOrder();
        
        if ($pendingOrder) {
            return redirect()->route('payment.pending')
                ->with('warning', 'Bạn có đơn hàng đang chờ thanh toán. Vui lòng thanh toán hoặc hủy đơn hàng trước khi tạo đơn mới.');
        }

        $userId = auth('web')->id();
        $sessionId = session()->getId();

        // Tìm cart và validate giá Flash Sale (sử dụng CartService để tự động validate)
        $cartService = app(\App\Services\CartService::class);
        $cart = $cartService->getOrCreateCart($userId, $sessionId);
        
        // Validate lại một lần nữa để đảm bảo
        $this->flashSaleService->validateCartPrices($cart);
        $cart->refresh();

        $cartItems = CartItem::active()
            ->with('variant', 'product')
            ->whereHas('cart', function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('account_id', $userId);
                } else {
                    $q->whereNull('account_id')->where('session_id', $sessionId);
                }
            })
            ->get();

        // Debug logging
        Log::info('Checkout cart items check', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'cart_items_count' => $cartItems->count(),
            'cart_items' => $cartItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'status' => $item->status,
                    'product_name' => $item->product->name ?? 'N/A'
                ];
            })
        ]);

        $productNew = Product::active()->with('primaryImage')->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];

        // Tạm thời bỏ qua kiểm tra cart items để test
        // if ($cartItems->isEmpty()) {
        //     \Log::warning('Cart is empty during checkout', [
        //         'user_id' => $userId,
        //         'session_id' => $sessionId
        //     ]);
        //     return redirect()->route('client.cart.index')->with('warning', 'Giỏ hàng của bạn đang trống!');
        // }

        $addresses = auth('web')->check()
            ? $this->addressService->listForAccount(auth('web')->user())
            : collect();
        $defaultAddress = auth('web')->check()
            ? $this->addressService->getDefaultForAccount(auth('web')->user())
            : null;

        return view('clients.pages.checkout.index', [
            'cartItem' => null,
            'cart' => $cart ?? $cartItems->first()->cart ?? null,
            'cartItems' => $cartItems,
            'productNew' => $productNew,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
        ]);
    }

    public function createCheckoutCart(Request $request)
    {
        // Kiểm tra xem có đơn hàng pending cần thanh toán không
        $pendingOrder = $this->hasPendingOrder();
        
        if ($pendingOrder) {
            return redirect()->route('payment.pending')
                ->with('error', 'Bạn có đơn hàng đang chờ thanh toán. Vui lòng thanh toán hoặc hủy đơn hàng trước khi tạo đơn mới.');
        }

        $validated = $request->validate([
            'cartId' => 'nullable|numeric',
            'fullname' => 'required|string|min:4|max:100|regex:/^[A-Za-zÀ-ỹ\s\'\.\-]+$/',
            'email' => 'nullable|email|max:150',
            'phone' => ['required', 'regex:/^(0|\+84)\d{9}$/'],
            'address' => 'required|string|min:10|max:255|regex:/^[A-Za-zÀ-ỹ0-9\s\.,\-\/]+$/',
            'provinceId' => 'required|numeric',
            'districtId' => 'required|numeric',
            'wardId' => 'required|string|regex:/^[A-Za-z0-9]+$/',
            'serviceId' => 'required|numeric',
            'serviceTypeId' => 'required|numeric',
            'shipping' => 'required|numeric',
            'shipping_fee' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:1000',
            'total' => 'required|numeric|min:1000',
            'payment' => 'required|in:cod,bank,momo,payos',
            'voucher_code' => 'nullable|string|max:50|regex:/^[A-Za-z0-9\-_]+$/',
            'voucher_discount' => 'nullable|numeric|min:0',
            'customer_note' => 'nullable|string|max:500|regex:/^[A-Za-zÀ-ỹ0-9\s\.,\-\/!?()]+$/',
        ]);

        // Sanitize all string inputs to prevent XSS
        $validated = $this->sanitizeInput($validated);

        DB::beginTransaction();
        try {
            $userId = auth('web')->id();
            $sessionId = session()->getId();

            // Prefer posted cartId when provided, otherwise derive by session/user
            $cartQuery = CartItem::active()->with(['product', 'variant']);
            if (!empty($validated['cartId'])) {
                $cartQuery->where('cart_id', (int) $validated['cartId']);
            } else {
                $cartQuery->whereHas('cart', function ($q) use ($userId, $sessionId) {
                    if ($userId) {
                        $q->where('account_id', $userId);
                    } else {
                        $q->whereNull('account_id')->where('session_id', $sessionId);
                    }
                });
            }
            $cartItems = $cartQuery->get();
            if ($cartItems->isEmpty()) {
                DB::rollBack();
                return redirect()->route('client.cart.index')->with('warning', 'Giỏ hàng trống, không thể tạo đơn.');
            }

            // Validate và cập nhật giá Flash Sale trước khi tạo order
            $cart = $cartItems->first()->cart;
            if ($cart) {
                $this->flashSaleService->validateCartPrices($cart);
                $cartItems = $cartItems->fresh(); // Refresh để lấy giá mới
            }

            $serverSubtotal = 0;
            $prepared = [];
            foreach ($cartItems as $ci) {
                $product = $ci->product;
                if (!$product) {
                    continue;
                }
                $attrs = [];
                if ($ci->variant) {
                    $attrs = (array) ($ci->variant->attributes ?? []);
                }
                $qty = max(1, (int) $ci->quantity);
                $availableStock = (int) ($ci->variant ? $ci->variant->stock_quantity : $product->stock_quantity);
                if ($qty > $availableStock) {
                    DB::rollBack();
                    return redirect()->route('client.cart.index')->with('error', "Sản phẩm '{$product->name}' không đủ hàng tồn (chỉ còn {$availableStock}).");
                }
                
                // Validate giá Flash Sale: Backend override giá từ cart item
                $flashSaleContext = $this->resolveFlashSaleContext($product, $ci->variant->id ?? null);
                $unitPrice = $flashSaleContext['is_flash_sale']
                    ? $flashSaleContext['price']  // Override với giá đúng từ backend
                    : (float) $ci->price;  // Dùng giá từ cart item nếu không có Flash Sale
                $lineTotal = $qty * $unitPrice;
                $serverSubtotal += $lineTotal;
                $prepared[] = [
                    'product' => $product,
                    'variantId' => $ci->variant->id ?? null,
                    'qty' => $qty,
                    'unitPrice' => $unitPrice,
                    'lineTotal' => $lineTotal,
                    'flashSaleContext' => $flashSaleContext, // Thêm để dùng khi tạo OrderItem
                ];
            }

            if (empty($prepared)) {
                DB::rollBack();
                return redirect()->route('client.cart.index')->with('warning', 'Không có sản phẩm hợp lệ để tạo đơn.');
            }

            $needsVoucherValidation = !empty($validated['voucher_code']);
            $orderItemsForVoucher = [];
            if ($needsVoucherValidation) {
                $orderItemsForVoucher = array_map(function ($item) {
                    return [
                        'product_id' => $item['product']->id,
                        'quantity' => $item['qty'],
                        'price' => $item['unitPrice'],
                        'total_price' => $item['lineTotal'],
                        'category_id' => $item['product']->primary_category_id,
                    ];
                }, $prepared);
            }

            $voucherId = null;
            $voucherDiscount = 0;
            $voucherCode = null;

            if ($needsVoucherValidation) {
                try {
                    $voucherMeta = $this->evaluateVoucherForOrder(
                        $validated['voucher_code'],
                        $orderItemsForVoucher,
                        (float) $validated['shipping_fee']
                    );

                    $voucherId = $voucherMeta['voucher_id'];
                    $voucherCode = $voucherMeta['voucher_code'];
                    $voucherDiscount = $voucherMeta['discount'];
                } catch (\Exception $voucherException) {
                    DB::rollBack();
                    return redirect()->route('client.cart.index')->with('error', $voucherException->getMessage());
                }
            }

            // Tính lại total_price từ prepared items (đã validate giá Flash Sale)
            $calculatedTotalPrice = array_sum(array_column($prepared, 'lineTotal'));
            
            // Tính final_price: total_price + shipping_fee - voucher_discount
            $calculatedFinalPrice = $calculatedTotalPrice + (float) $validated['shipping_fee'] - $voucherDiscount;

            $orderPaymentMethod = $validated['payment'] === 'payos' ? 'bank' : $validated['payment'];
            $order = Order::create([
                'code' => $this->orderService->generateOrderCode(),
                'account_id' => $userId,
                'session_id' => $sessionId,
                'total_price' => $calculatedTotalPrice, // Dùng giá đã tính từ backend, không tin frontend
                'shipping_fee' => (float) $validated['shipping_fee'],
                'voucher_id' => $voucherId,
                'voucher_discount' => $voucherDiscount,
                'voucher_code' => $voucherCode,
                'final_price' => $calculatedFinalPrice, // Tính lại từ backend
                'receiver_name' => $validated['fullname'],
                'receiver_phone' => $validated['phone'],
                'receiver_email' => $validated['email'] ?? null,
                'shipping_address' => $validated['address'],
                'shipping_province_id' => $validated['provinceId'],
                'shipping_district_id' => $validated['districtId'],
                'shipping_ward_id' => $validated['wardId'],
                'payment_method' => $orderPaymentMethod,
                'payment_status' => 'pending',
                'shipping_partner' => 'ghn',
                'status' => 'pending',
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            $orderHasFlashSaleItems = false;
            foreach ($prepared as $p) {
                // Sử dụng flashSaleContext đã tính ở trên (đã override giá)
                $flashSaleContext = $p['flashSaleContext'] ?? $this->resolveFlashSaleContext($p['product'], $p['variantId']);
                $linePrice = $p['unitPrice']; // Đã được validate và override ở trên
                $lineTotal = $p['lineTotal']; // Đã được tính ở trên

                if ($flashSaleContext['is_flash_sale']) {
                    $orderHasFlashSaleItems = true;
                }

                OrderItem::create([
                    'uuid' => Str::uuid(),
                    'order_id' => $order->id,
                    'product_id' => $p['product']->id,
                    'product_variant_id' => $p['variantId'],
                    'quantity' => $p['qty'],
                    'price' => $linePrice,
                    'total_price' => $lineTotal,
                    'is_flash_sale' => $flashSaleContext['is_flash_sale'],
                    'flash_sale_item_id' => $flashSaleContext['flash_sale_item_id'],
                ]);
            }

            if ($orderHasFlashSaleItems) {
                $order->update(['is_flash_sale' => true]);
            }

            // Tìm cart để đánh dấu là ordered
            $cart = null;
            if (!empty($validated['cartId'])) {
                $cart = Cart::find((int) $validated['cartId']);
            } else {
                // Tìm cart theo session/user
                $cartQuery = Cart::where('status', 'active');
                if ($userId) {
                    $cartQuery->where('account_id', $userId);
                } else {
                    $cartQuery->whereNull('account_id')->where('session_id', $sessionId);
                }
                $cart = $cartQuery->latest()->first();
            }
            
            // dọn giỏ hàng theo logic hiện tại: đánh dấu removed
            $removedCount = 0;
            if (!empty($validated['cartId'])) {
                // Xóa theo cartId cụ thể
                $removedCount = CartItem::active()->where('cart_id', (int) $validated['cartId'])->update(['status' => 'removed']);
                Log::info('Cart items removed by cartId', [
                    'cart_id' => $validated['cartId'],
                    'removed_count' => $removedCount,
                    'order_id' => $order->id
                ]);
            } else {
                // Xóa theo session/user nếu không có cartId
                $removedCount = CartItem::active()->whereHas('cart', function ($q) use ($userId, $sessionId) {
                    if ($userId) {
                        $q->where('account_id', $userId);
                    } else {
                        $q->whereNull('account_id')->where('session_id', $sessionId);
                    }
                })->update(['status' => 'removed']);
                Log::info('Cart items removed by session/user', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'removed_count' => $removedCount,
                    'order_id' => $order->id
                ]);
            }
            
            // Đánh dấu cart là ordered sau khi tạo order thành công
            if ($cart) {
                $this->cartService->markAsOrdered($cart, $order->code);
                Log::info('Cart marked as ordered', [
                    'cart_id' => $cart->id,
                    'order_id' => $order->id,
                    'order_code' => $order->code
                ]);
            } else {
                Log::warning('Cart not found to mark as ordered', [
                    'cart_id' => $validated['cartId'] ?? null,
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'order_id' => $order->id
                ]);
            }

            DB::commit();

            // Initiate online payment if not COD
            if (in_array($validated['payment'], ['bank','momo','payos'])) {
                try {
                    $result = $this->payOSService->createPaymentLink($order);
                    
                    Log::info('PayOS payment link creation result (cart)', [
                        'order_id' => $order->id,
                        'payment_method' => $validated['payment'],
                        'result' => $result
                    ]);
                    
                    if (!($result['success'] ?? false)) {
                        Log::warning('PayOS payment link creation failed (cart)', [
                            'order_id' => $order->id,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                        return redirect()->route('client.cart.index')
                            ->with('warning', $result['error'] ?? 'Không thể tạo liên kết thanh toán. Vui lòng thử lại hoặc chọn COD.');
                    }
                    
                    if (empty($result['checkout_url'])) {
                        Log::error('PayOS checkout_url is empty (cart)', [
                            'order_id' => $order->id,
                            'result' => $result
                        ]);
                        return redirect()->route('client.cart.index')
                            ->with('warning', 'Không thể tạo liên kết thanh toán. Vui lòng thử lại hoặc chọn COD.');
                    }
                    
                    Log::info('Redirecting to PayOS checkout (cart)', [
                        'order_id' => $order->id,
                        'checkout_url' => $result['checkout_url']
                    ]);
                    
                    // Sử dụng header Location trực tiếp để đảm bảo redirect hoạt động
                    return response('', 302)->header('Location', $result['checkout_url']);
                } catch (\Exception $e) {
                    Log::error('Exception when creating PayOS payment link (cart)', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return redirect()->route('client.cart.index')
                        ->with('warning', 'Không thể tạo liên kết thanh toán: ' . $e->getMessage() . '. Vui lòng thử lại hoặc chọn COD.');
                }
            }

            return redirect()->route('client.cart.index')->with('success', 'Đặt hàng toàn bộ giỏ thành công 🎉');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('createCheckoutCart error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
                'user_id' => auth('web')->id(),
                'session_id' => session()->getId(),
            ]);
            $msg = 'Lỗi khi tạo đơn từ giỏ hàng 😢';
            if (config('app.debug')) {
                $msg .= ' — ' . $e->getMessage();
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    private function resolveFlashSaleContext(Product $product, ?int $variantId = null): array
    {
        // Sử dụng FlashSaleService để lấy giá (hỗ trợ unified_price)
        $flashSalePrice = $this->flashSaleService->getPriceForVariant($product->id, $variantId);

        if ($flashSalePrice) {
            return [
                'is_flash_sale' => true,
                'flash_sale_item_id' => $flashSalePrice['flash_sale_item_id'],
                'price' => $flashSalePrice['price'],
                'is_unified' => $flashSalePrice['is_unified'] ?? false,
            ];
        }

        return [
            'is_flash_sale' => false,
            'flash_sale_item_id' => null,
            'price' => null,
            'is_unified' => false,
        ];
    }

    private function findVariant($product, $attributeString)
    {
        if (empty($attributeString)) {
            return null;
        }

        $attrs = [];
        foreach (explode(',', $attributeString) as $pair) {
            [$key, $value] = array_map('trim', explode(':', $pair));
            if ($key && $value) {
                $attrs[strtolower($key)] = $value;
            }
        }

        return ProductVariant::where('product_id', $product->id)
            ->where(function ($q) use ($attrs) {
                foreach ($attrs as $key => $value) {
                    $q->where(DB::raw("JSON_EXTRACT(attributes, '$.\"{$key}\"')"), '=', $value);
                }
            })
            ->first();
    }
}
