<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CartItemRequest;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $query = CartItem::with(['cart.account', 'product', 'variant']);

        if ($cartId = $request->get('cart_id')) {
            $query->where('cart_id', $cartId);
        }

        if ($productId = $request->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $items = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.cart-items.index', compact('items'));
    }

    public function edit(CartItem $cartItem)
    {
        $cartItem->load(['cart', 'product', 'variant']);

        return view('admins.cart-items.edit', compact('cartItem'));
    }

    public function update(CartItemRequest $request, CartItem $cartItem)
    {
        $data = $request->validated();

        if (isset($data['quantity'])) {
            $this->cartService->updateItemQuantity($cartItem, $data['quantity']);
        }

        if (isset($data['price'])) {
            $cartItem->price = $data['price'];
            $cartItem->total_price = $cartItem->quantity * $cartItem->price;
            $cartItem->save();
            $this->cartService->recalculateTotals($cartItem->cart);
        }

        return redirect()
            ->route('admin.carts.show', $cartItem->cart)
            ->with('success', 'Đã cập nhật sản phẩm trong giỏ hàng.');
    }

    public function destroy(CartItem $cartItem)
    {
        $cart = $cartItem->cart;
        $this->cartService->removeItem($cartItem, false);

        return redirect()
            ->route('admin.carts.show', $cart)
            ->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}

