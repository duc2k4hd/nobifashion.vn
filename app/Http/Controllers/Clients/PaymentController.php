<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayOSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PayOSService $payOS)
    {
    }

    public function pending()
    {
        $userId = auth('web')->id();
        $sessionId = session()->getId();
        $order = Order::where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('account_id', $userId);
            } else {
                $q->whereNull('account_id')->where('session_id', $sessionId);
            }
        })->where('status', 'pending')->first();

        if (!$order) {
            return redirect()->route('client.cart.index')->with('info', 'Không có đơn hàng chờ thanh toán.');
        }

        // Lấy payment record và checkout_url nếu có
        $payment = Payment::where('order_id', $order->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        
        $checkoutUrl = null;
        if ($payment && $payment->raw_response) {
            // Lấy checkout_url từ raw_response
            // PayOS response structure: { code: '00', desc: 'success', data: { checkoutUrl: '...' } }
            $rawResponse = is_array($payment->raw_response) 
                ? $payment->raw_response 
                : json_decode($payment->raw_response, true);
            
            // Thử nhiều cách để lấy checkout_url
            $checkoutUrl = $rawResponse['data']['checkoutUrl'] 
                ?? $rawResponse['data']['checkout_url'] 
                ?? $rawResponse['checkout_url'] 
                ?? $rawResponse['checkoutUrl']
                ?? null;
        }

        return view('clients.pages.payment.pending', compact('order', 'checkoutUrl'));
    }

    public function retry(Request $request)
    {
        $userId = auth('web')->id();
        $sessionId = session()->getId();
        
        \Log::info('Payment retry attempt', [
            'user_id' => $userId,
            'session_id' => $sessionId
        ]);
        
        $order = Order::where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('account_id', $userId);
            } else {
                $q->whereNull('account_id')->where('session_id', $sessionId);
            }
        })->where('status', 'pending')->first();
        
        // Nếu không tìm thấy, tìm order gần nhất với payment_method = bank_transfer
        if (!$order) {
            $order = Order::where('status', 'pending')
                         ->where('payment_method', 'bank_transfer')
                         ->latest()
                         ->first();
        }

        \Log::info('Order found for retry', [
            'order_id' => $order ? $order->id : null,
            'order_status' => $order ? $order->status : null,
            'payment_method' => $order ? $order->payment_method : null,
            'search_method' => $order ? 'found' : 'not_found'
        ]);

        if (!$order) {
            \Log::warning('No pending order found for retry');
            return redirect()->route('client.cart.index')->with('info', 'Không có đơn hàng chờ thanh toán.');
        }

        if ($order->payment_method === 'cod') {
            \Log::info('COD order, no online payment needed');
            return redirect()->route('client.cart.index')->with('warning', 'Đơn hàng COD không cần thanh toán online.');
        }

        \Log::info('Creating PayOS payment link for retry', [
            'order_id' => $order->id,
            'payment_method' => $order->payment_method
        ]);

        // Kiểm tra xem đã có payment record pending chưa
        $existingPayment = Payment::where('order_id', $order->id)
            ->where('status', 'pending')
            ->where('method', 'payos')
            ->latest()
            ->first();
        
        $checkoutUrl = null;
        
        // Nếu đã có payment record, thử lấy checkout_url từ đó
        if ($existingPayment && $existingPayment->raw_response) {
            $rawResponse = is_array($existingPayment->raw_response) 
                ? $existingPayment->raw_response 
                : json_decode($existingPayment->raw_response, true);
            
            $checkoutUrl = $rawResponse['data']['checkoutUrl'] 
                ?? $rawResponse['data']['checkout_url'] 
                ?? $rawResponse['checkout_url'] 
                ?? $rawResponse['checkoutUrl']
                ?? null;
            
            if ($checkoutUrl) {
                \Log::info('Using existing payment checkout URL', [
                    'payment_id' => $existingPayment->id,
                    'checkout_url' => $checkoutUrl
                ]);
                return redirect()->away($checkoutUrl);
            }
        }
        
        // Nếu không có checkout_url từ payment cũ, đánh dấu payment cũ là failed
        // Không hủy payment trên PayOS vì có thể gây lỗi "order already exists"
        // PayOS sẽ tự động từ chối nếu orderCode đã tồn tại, nhưng chúng ta sẽ tạo orderCode mới
        if ($existingPayment) {
            \Log::info('Marking old payment as failed before creating new one', [
                'payment_id' => $existingPayment->id
            ]);
            // Chỉ đánh dấu payment cũ là failed trong database, không hủy trên PayOS
            $existingPayment->update([
                'status' => 'failed',
                'raw_response' => array_merge(
                    $existingPayment->raw_response ?? [],
                    ['replaced_at' => now()->toISOString(), 'replaced_reason' => 'Creating new payment link']
                )
            ]);
        }

        // Tạo link thanh toán mới với orderCode mới (thêm timestamp để đảm bảo unique)
        // PayOSService sẽ tự động tạo orderCode mới từ order->code
        $result = $this->payOS->createPaymentLink($order);
        
        \Log::info('PayOS payment link result', [
            'success' => $result['success'] ?? false,
            'checkout_url' => $result['checkout_url'] ?? null,
            'error' => $result['error'] ?? null
        ]);
        
        if ($result['success'] && !empty($result['checkout_url'])) {
            return redirect()->away($result['checkout_url']);
        }
        return back()->with('warning', $result['error'] ?? 'Không thể tạo liên kết thanh toán.');
    }

    public function cancelPending(Request $request)
    {
        $userId = auth('web')->id();
        $sessionId = session()->getId();
        $order = Order::where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('account_id', $userId);
            } else {
                $q->whereNull('account_id')->where('session_id', $sessionId);
            }
        })->where('status', 'pending')->first();

        if (!$order) {
            return redirect()->route('client.cart.index')->with('info', 'Không có đơn hàng chờ thanh toán.');
        }

        $order->update(['payment_status' => 'failed']);
        if ($p = $order->payments()->latest()->first()) {
            $p->markAsFailed('User cancelled from pending page');
        }
        $order->markAsCancelled('User cancelled from pending page');

        return redirect()->route('client.cart.index')->with('success', 'Đã hủy đơn hàng. Bạn có thể tạo đơn mới.');
    }

    public function return(Request $request)
    {
        $data = $request->all();
        $orderCode = $data['orderCode'] ?? null;
        
        \Log::info('PayOS return callback', [
            'data' => $data,
            'orderCode' => $orderCode
        ]);
        
        if (!$orderCode) {
            return redirect()->route('client.cart.index')->with('error', 'Thiếu mã đơn hàng.');
        }

        // Tìm order thông qua Payment record với transaction_code = PayOS orderCode
        $payment = \App\Models\Payment::where('transaction_code', $orderCode)->first();
        if (!$payment) {
            \Log::warning('Payment not found for orderCode', ['orderCode' => $orderCode]);
            return redirect()->route('client.cart.index')->with('error', 'Đơn hàng không tồn tại.');
        }
        
        $order = $payment->order;
        if (!$order) {
            \Log::warning('Order not found for payment', ['payment_id' => $payment->id]);
            return redirect()->route('client.cart.index')->with('error', 'Đơn hàng không tồn tại.');
        }
        
        \Log::info('Found order for PayOS callback', [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'payment_id' => $payment->id,
            'payos_orderCode' => $orderCode
        ]);

        if (!($data['status'] ?? null)) {
            return redirect()->route('client.cart.index')->with('warning', 'Chưa xác nhận trạng thái thanh toán.');
        }

        if (($data['status'] ?? '') === 'PAID') {
            // Xác thực callback từ PayOS
            \Log::info('Attempting PayOS callback verification', [
                'data' => $data,
                'order_id' => $order->id
            ]);
            
            $result = $this->payOS->verifyCallback($data);
            
            \Log::info('PayOS callback verification result', [
                'success' => $result['success'] ?? false,
                'error' => $result['error'] ?? null,
                'result' => $result
            ]);
            
            if ($result['success']) {
                return redirect()->route('client.order.index')->with('success', 'Thanh toán thành công. Đơn hàng đang được xử lý.');
            } else {
                \Log::warning('PayOS callback verification failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'data' => $data
                ]);
            }
        }

        return redirect()->route('client.cart.index')->with('warning', 'Thanh toán chưa hoàn tất hoặc bị huỷ.');
    }

    public function cancel(Request $request)
    {
        $orderCode = $request->get('orderCode');
        if ($orderCode) {
            $order = is_numeric($orderCode)
                ? Order::find((int) $orderCode)
                : Order::where('code', $orderCode)->first();
            if ($order && $order->status === 'pending') {
                // Map to allowed enum
                $order->update(['payment_status' => 'failed']);
                if ($p = $order->payments()->latest()->first()) {
                    $p->markAsFailed('User cancelled at return');
                }
            }
        }
        return redirect()->route('client.cart.index')->with('warning', 'Bạn đã huỷ thanh toán.');
    }

    public function webhook(Request $request)
    {
        $data = $request->all();

        try {
            // Sử dụng PayOSService mới để xử lý webhook
            $result = $this->payOS->verifyCallback($data);
            
            if ($result['success']) {
                return response()->json(['message' => 'ok']);
            } else {
                Log::warning('PayOS webhook processing failed', [
                    'error' => $result['error'],
                    'data' => $data
                ]);
                return response()->json(['message' => $result['error']], 400);
            }
        } catch (\Throwable $e) {
            Log::error('PayOS webhook error', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'error'], 500);
        }
    }
}


