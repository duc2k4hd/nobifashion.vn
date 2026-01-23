<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderItemRequest;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $query = OrderItem::with(['order', 'product.primaryImage', 'variant.primaryVariantImage']);

        if ($orderId = $request->get('order_id')) {
            $query->where('order_id', $orderId);
        }

        if ($productId = $request->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($variantId = $request->get('product_variant_id')) {
            $query->where('product_variant_id', $variantId);
        }

        $items = $query->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->query());

        return view('admins.order-items.index', compact('items'));
    }

    public function edit(OrderItem $orderItem)
    {
        // Check if can edit
        $order = $orderItem->order;
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'Không thể sửa item của đơn hàng đã hoàn thành hoặc đã hủy.');
        }

        $orderItem->load(['order', 'product', 'variant']);

        return view('admins.order-items.edit', compact('orderItem'));
    }

    public function update(OrderItemRequest $request, OrderItem $orderItem)
    {
        try {
            // Check if can edit
            $order = $orderItem->order;
            if (in_array($order->status, ['completed', 'cancelled'])) {
                return back()
                    ->with('error', 'Không thể sửa item của đơn hàng đã hoàn thành hoặc đã hủy.');
            }

            $data = $request->validated();
            
            // Update item
            $orderItem->update([
                'quantity' => $data['quantity'],
                'price' => $data['price'],
                'total_price' => $data['quantity'] * $data['price'],
            ]);

            // Recalculate order totals
            $this->orderService->recalculateOrderTotals($order);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Đã cập nhật sản phẩm trong đơn hàng.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật: ' . $e->getMessage());
        }
    }

    public function destroy(OrderItem $orderItem)
    {
        try {
            // Check if can delete
            $order = $orderItem->order;
            if (in_array($order->status, ['completed', 'cancelled'])) {
                return back()
                    ->with('error', 'Không thể xóa item của đơn hàng đã hoàn thành hoặc đã hủy.');
            }

            // Check minimum items
            if ($order->items()->count() <= 1) {
                return back()
                    ->with('error', 'Không thể xóa item. Đơn hàng phải có ít nhất 1 sản phẩm.');
            }

            $orderItem->delete();

            // Recalculate order totals
            $this->orderService->recalculateOrderTotals($order);

            return back()
                ->with('success', 'Đã xóa sản phẩm khỏi đơn hàng.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể xóa: ' . $e->getMessage());
        }
    }
}

