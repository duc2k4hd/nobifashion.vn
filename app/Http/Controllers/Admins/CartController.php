<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CartRequest;
use App\Http\Requests\Admin\CreateOrderFromCartRequest;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cartService;
    protected OrderService $orderService;

    public function __construct(CartService $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $query = Cart::with(['account', 'items.product', 'items.variant']);

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhereHas('account', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($accountId = $request->get('account_id')) {
            $query->where('account_id', $accountId);
        }

        if ($request->has('has_session')) {
            if ($request->get('has_session')) {
                $query->whereNotNull('session_id');
            } else {
                $query->whereNull('session_id');
            }
        }

        $carts = $query->orderByDesc('updated_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.carts.index', compact('carts'));
    }

    public function createOrderIndex(Request $request)
    {
        // Chỉ hiển thị các giỏ hàng active có items
        $query = Cart::with(['account', 'items' => function ($q) {
            $q->where('status', 'active');
        }])
        ->where('status', 'active')
        ->has('items', '>', 0);

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhereHas('account', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($accountId = $request->get('account_id')) {
            $query->where('account_id', $accountId);
        }

        $carts = $query->orderByDesc('updated_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.carts.create-order-index', compact('carts'));
    }

    public function show(Cart $cart)
    {
        $cart->load([
            'account',
            'items.product.primaryImage',
            'items.variant.primaryVariantImage',
            'items.variant',
        ]);

        return view('admins.carts.show', compact('cart'));
    }

    public function edit(Cart $cart)
    {
        return view('admins.carts.edit', compact('cart'));
    }

    public function update(CartRequest $request, Cart $cart)
    {
        $cart->update($request->validated());

        return redirect()
            ->route('admin.carts.show', $cart)
            ->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(Cart $cart)
    {
        $cart->delete();

        return redirect()
            ->route('admin.carts.index')
            ->with('success', 'Đã xóa giỏ hàng.');
    }

    public function recalculate(Cart $cart)
    {
        $this->cartService->recalculateTotals($cart);

        return back()
            ->with('success', 'Đã tính lại tổng tiền giỏ hàng.');
    }

    public function createOrder(Cart $cart)
    {
        // Validate cart (skip price check for admin-created orders)
        $errors = $this->cartService->validateCart($cart, skipPriceCheck: true);
        if (!empty($errors)) {
            return redirect()
                ->route('admin.carts.show', $cart)
                ->with('error', implode(' ', $errors));
        }

        if ($cart->status !== 'active') {
            return redirect()
                ->route('admin.carts.show', $cart)
                ->with('error', 'Giỏ hàng không ở trạng thái hoạt động.');
        }

        $cart->load([
            'account',
            'items.product.primaryImage',
            'items.variant.primaryVariantImage',
            'items.variant',
        ]);

        return view('admins.carts.create-order', compact('cart'));
    }

    public function storeOrder(CreateOrderFromCartRequest $request, Cart $cart)
    {
        try {
            $order = $this->orderService->createOrderFromCart($cart, $request->validated());

            return redirect()
                ->route('admin.carts.show', $cart)
                ->with('success', 'Đã tạo đơn hàng thành công! Mã đơn: ' . $order->code);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể tạo đơn hàng: ' . $e->getMessage());
        }
    }
}

