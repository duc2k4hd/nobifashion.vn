<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Hiển thị danh sách đơn hàng của user
     */
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->id();
        $sessionId = session()->getId();

        // Lấy orders của user đã đăng nhập hoặc guest session
        $query = Order::with(['items.product.primaryImage', 'items.variant.primaryVariantImage'])
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('account_id', $userId);
        } else {
            $query->whereNull('account_id')->where('session_id', $sessionId);
        }

        // Filter theo status nếu có
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter theo payment_status nếu có
        if ($request->has('payment_status') && $request->payment_status !== '') {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->paginate(10);

        return view('clients.pages.order.index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->status ?? '',
                'payment_status' => $request->payment_status ?? '',
            ]
        ]);
    }

    /**
     * Hiển thị chi tiết đơn hàng
     */
    public function show($id)
    {
        $userId = Auth::guard('web')->id();
        $sessionId = session()->getId();

        $order = Order::with([
            'items.product.primaryImage',
            'items.variant.primaryVariantImage',
            'account'
        ])->findOrFail($id);

        // Kiểm tra quyền truy cập
        if ($userId) {
            if ($order->account_id !== $userId) {
                return view('clients.pages.errors.403');
            }
        } else {
            if ($order->session_id !== $sessionId || $order->account_id !== null) {
                return view('clients.pages.errors.403');
            }
        }

        return view('clients.pages.order.show', compact('order'));
    }
}

