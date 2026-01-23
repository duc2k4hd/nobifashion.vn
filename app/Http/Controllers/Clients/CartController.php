<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\FlashSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    protected CartService $cartService;
    protected FlashSaleService $flashSaleService;

    public function __construct(CartService $cartService, FlashSaleService $flashSaleService)
    {
        $this->cartService = $cartService;
        $this->flashSaleService = $flashSaleService;
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
    public function index(Request $request)
    {
        $productNew = Product::active()->with('primaryImage')->orderBy('created_at', 'desc')->inRandomOrder()->limit(9)->get() ?? [];

        // Lấy cart của user hoặc guest
        $userId = auth('web')->id();
        $sessionId = session()->getId();
        
        $cart = Cart::where('status', 'active')
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('account_id', $userId);
                } else {
                    $q->whereNull('account_id')->where('session_id', $sessionId);
                }
            })
            ->latest()
            ->first();

        // Validate và cập nhật giá Flash Sale trước khi hiển thị
        if ($cart) {
            $this->flashSaleService->validateCartPrices($cart);
            $cart->refresh();
        }

        return view('clients.pages.cart.index', [
            'productNew' => $productNew,
            'cart' => $cart,
        ]);
    }

    public function add(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "action"     => "required|string|in:add_to_cart,buy_now",
                "quantity"   => "required|integer|min:1",
                "variant_id" => "required|integer|exists:product_variants,id",
            ],
            [
                "action.required"   => "⚠️ Vui lòng chọn hành động (Thêm vào giỏ hàng hoặc Mua ngay).",
                "action.string"     => "Dữ liệu hành động không hợp lệ.",
                "action.in"         => "Hành động không hợp lệ. Vui lòng thử lại.",

                "quantity.required" => "⚠️ Vui lòng nhập số lượng sản phẩm.",
                "quantity.integer"  => "Số lượng phải là số nguyên.",
                "quantity.min"      => "Số lượng tối thiểu là 1.",

                "variant_id.required" => "⚠️ Vui lòng chọn biến thể sản phẩm.",
                "variant_id.integer"  => "Mã biến thể sản phẩm không hợp lệ.",
                "variant_id.exists"   => "Biến thể sản phẩm không tồn tại trong hệ thống.",
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = (object) $validator->validated();
        $productVariant = ProductVariant::findOrFail($validated->variant_id);

        // Kiểm tra tồn kho
        if ($productVariant->stock_quantity < $validated->quantity) {
            return redirect()->back()->with(
                'error',
                "Xin lỗi, hiện chỉ còn {$productVariant->stock_quantity} sản phẩm. Vui lòng giảm số lượng để tiếp tục!"
            );
        }

        // 1. Xác định cart cha (sử dụng CartService để tự động validate giá Flash Sale)
        $cart = $this->cartService->getOrCreateCart(
            auth('web')->id(),
            session()->getId()
        );

        try {
            // Validate giá Flash Sale trước khi thêm item
            $this->flashSaleService->validateCartPrices($cart);
            $cart->refresh();
            
            $cartItem = $this->cartService->addItem(
                $cart,
                $productVariant->product_id,
                $productVariant->id,
                $validated->quantity
            );
            
            // Validate lại sau khi thêm item
            $this->flashSaleService->validateCartPrices($cart);
        } catch (\Throwable $e) {
            Log::warning('Add to cart failed', [
                'error' => $e->getMessage(),
                'variant_id' => $productVariant->id,
                'user_id' => auth('web')->id(),
                'session_id' => session()->getId(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }

        // 4. Phân nhánh action
        if ($validated->action === 'add_to_cart') {
            return redirect()->back()->with('success', '🛒 Đã thêm vào giỏ hàng thành công.');
        }

        if ($validated->action === 'buy_now') {
            return redirect()->route('client.checkout.cart.item', [
                'uuid' => $cartItem->uuid
            ])->with('success', '🛒 Chuyển đến trang thanh toán thành công!');
        }
    }

    public function updateQuantity(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:cart_items,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $cartItem = CartItem::active()->with(['variant', 'product', 'cart'])->find($validated['item_id']);
        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng.']);
        }

        $availableStock = (int) ($cartItem->variant ? $cartItem->variant->stock_quantity : $cartItem->product->stock_quantity);
        if ($validated['quantity'] > $availableStock) {
            return response()->json(['success' => false, 'message' => "Chỉ còn {$availableStock} sản phẩm trong kho."]);
        }

        // Validate và cập nhật giá Flash Sale trước khi update quantity
        $cart = $cartItem->cart;
        if ($cart) {
            $this->flashSaleService->validateCartPrices($cart);
            $cartItem->refresh(); // Refresh để lấy giá mới sau khi validate
        }

        // Lấy giá đúng (có thể đã được validate và cập nhật từ Flash Sale)
        $correctPrice = (float) $cartItem->price;
        
        // Update cart item với giá đúng
        $cartItem->update([
            'quantity' => $validated['quantity'],
            'total_price' => $validated['quantity'] * $correctPrice,
        ]);

        // Refresh cart item để lấy giá trị mới nhất
        $cartItem->refresh();

        // Update cart totals
        if ($cart) {
            $cart->updateTotals();
            $cart->refresh();
        }

        // Đếm số lượng sản phẩm (items) trong cart, không phải tổng quantity
        $cartItemsCount = $cart->items()->where('status', 'active')->count();
        
        // Trả về giá trị chính xác từ database sau khi update
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật số lượng thành công!',
            'item_total' => number_format((float) $cartItem->total_price, 0, ',', '.') . ' đ',
            'cart_total' => number_format((float) ($cart->total_price ?? 0), 0, ',', '.') . ' đ',
            'cart_quantity' => $cartItemsCount, // Số lượng sản phẩm (items) trong giỏ hàng
            'cart_total_quantity' => (int) ($cart->total_quantity ?? 0), // Tổng số lượng (quantity) của tất cả items
        ]);
    }

    public function removeItem($id)
    {
        $cartItem = CartItem::active()->with(['variant', 'product'])->find($id);
        if (!$cartItem) {
            return redirect()->back()->with('error', 'Sản phẩm không tồn tại trong giỏ hàng.');
        }

        $productName = $cartItem->variant ? $cartItem->variant->product->name : $cartItem->product->name;
        $cartItem->update(['status' => 'removed']);
        $cartItem->cart->updateTotals();

        return redirect()->back()->with('success', "Đã xóa '{$productName}' khỏi giỏ hàng.");
    }

    public function clear(Request $request)
    {
        $cartId = $request->input('cart_id');
        if (!$cartId) {
            return redirect()->back()->with('error', 'Không tìm thấy giỏ hàng.');
        }

        $cart = Cart::find($cartId);
        if (!$cart) {
            return redirect()->back()->with('error', 'Giỏ hàng không tồn tại.');
        }
        
        // Validate giá Flash Sale trước khi clear
        $this->flashSaleService->validateCartPrices($cart);
        $cart->refresh();

        $itemCount = $cart->items()->active()->count();
        $cart->items()->active()->update(['status' => 'removed']);
        $cart->updateTotals();

        return redirect()->back()->with('success', "Đã xóa {$itemCount} sản phẩm khỏi giỏ hàng.");
    }
}
