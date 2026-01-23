<?php

namespace App\Http\Controllers\Apis\V1\PayOS;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayOSService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * PayOS API Controller
 * 
 * Xử lý các API endpoint cho tích hợp PayOS:
 * - Tạo link thanh toán
 * - Xử lý webhook callback
 * - Hủy thanh toán
 * - Lấy thông tin thanh toán
 * 
 * @author NobiFashion Team
 * @version 1.0
 */
class PayOSController extends Controller
{
    private PayOSService $payOSService;

    public function __construct(PayOSService $payOSService)
    {
        $this->payOSService = $payOSService;
    }

    /**
     * Tạo link thanh toán PayOS
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @api POST /api/v1/payos/create-payment
     * 
     * @bodyParam order_id integer required ID của đơn hàng
     * @bodyParam return_url string optional URL trả về sau khi thanh toán thành công
     * @bodyParam cancel_url string optional URL trả về khi hủy thanh toán
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Payment link created successfully",
     *   "data": {
     *     "checkout_url": "https://pay.payos.vn/web/...",
     *     "payment_id": 123,
     *     "order_code": 1234567890,
     *     "expires_at": "2024-01-01T12:00:00Z"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "order_id": ["The order id field is required."]
     *   }
     * }
     */
    public function createPayment(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|integer|exists:orders,id',
                'return_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Tìm order
            $order = Order::findOrFail($request->order_id);

            // Kiểm tra trạng thái order
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has already been paid'
                ], 400);
            }

            // Tạo link thanh toán
            $result = $this->payOSService->createPaymentLink(
                $order,
                $request->return_url,
                $request->cancel_url
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment link',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment link created successfully',
                'data' => [
                    'checkout_url' => $result['checkout_url'],
                    'payment_id' => $result['payment_id'],
                    'order_code' => $result['order_code'],
                    'expires_at' => now()->addHours(24)->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('PayOS create payment failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook callback từ PayOS
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @api POST /api/v1/payos/webhook
     * 
     * @bodyParam orderCode integer required Mã đơn hàng PayOS
     * @bodyParam status string required Trạng thái thanh toán (PAID, CANCELLED, EXPIRED)
     * @bodyParam signature string required Chữ ký xác thực
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Webhook processed successfully"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid signature"
     * }
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            Log::info('PayOS webhook received', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Xác thực callback
            $result = $this->payOSService->verifyCallback($request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'data' => [
                    'order_id' => $result['order_id'],
                    'payment_id' => $result['payment_id'],
                    'status' => $result['status']
                ]
            ]);

        } catch (Exception $e) {
            Log::error('PayOS webhook processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hủy thanh toán PayOS
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @api POST /api/v1/payos/cancel-payment
     * 
     * @bodyParam payment_id integer required ID của payment record
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Payment cancelled successfully"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Payment not found"
     * }
     */
    public function cancelPayment(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|integer|exists:payments,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Tìm payment
            $payment = Payment::findOrFail($request->payment_id);

            // Kiểm tra trạng thái payment
            if ($payment->status === 'success') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel completed payment'
                ], 400);
            }

            // Hủy thanh toán
            $result = $this->payOSService->cancelPayment($payment);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel payment',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully'
            ]);

        } catch (Exception $e) {
            Log::error('PayOS cancel payment failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin thanh toán từ PayOS
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @api GET /api/v1/payos/payment-info/{orderCode}
     * 
     * @urlParam orderCode integer required Mã đơn hàng PayOS
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": "payment_id",
     *     "orderCode": 1234567890,
     *     "amount": 100000,
     *     "status": "PAID",
     *     "createdAt": "2024-01-01T10:00:00Z",
     *     "paidAt": "2024-01-01T10:05:00Z"
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Payment not found"
     * }
     */
    public function getPaymentInfo(Request $request, int $orderCode): JsonResponse
    {
        try {
            $result = $this->payOSService->getPaymentInfo((string) $orderCode);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (Exception $e) {
            Log::error('PayOS get payment info failed', [
                'error' => $e->getMessage(),
                'orderCode' => $orderCode
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách thanh toán PayOS
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @api GET /api/v1/payos/payments
     * 
     * @queryParam order_id integer optional Lọc theo order ID
     * @queryParam status string optional Lọc theo trạng thái (pending, success, failed)
     * @queryParam page integer optional Trang hiện tại
     * @queryParam per_page integer optional Số bản ghi mỗi trang
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "payments": [...],
     *     "pagination": {
     *       "current_page": 1,
     *       "total": 100,
     *       "per_page": 15
     *     }
     *   }
     * }
     */
    public function getPayments(Request $request): JsonResponse
    {
        try {
            $query = Payment::where('method', 'payos')
                ->with(['order', 'account']);

            // Lọc theo order_id
            if ($request->has('order_id')) {
                $query->where('order_id', $request->order_id);
            }

            // Lọc theo status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Phân trang
            $perPage = $request->get('per_page', 15);
            $payments = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments->items(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'total' => $payments->total(),
                        'per_page' => $payments->perPage(),
                        'last_page' => $payments->lastPage(),
                        'has_more' => $payments->hasMorePages()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('PayOS get payments failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test endpoint để kiểm tra kết nối PayOS
     * 
     * @return JsonResponse
     * 
     * @api GET /api/v1/payos/test
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "PayOS service is working",
     *   "config": {
     *     "client_id": "***",
     *     "base_url": "https://api-merchant.payos.vn"
     *   }
     * }
     */
    public function test(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'PayOS service is working',
                'config' => [
                    'client_id' => substr(config('services.pay_os.client_id'), 0, 8) . '***',
                    'base_url' => config('services.pay_os.base_url'),
                    'has_api_key' => !empty(config('services.pay_os.api_key')),
                    'has_checksum_key' => !empty(config('services.pay_os.checksum_key'))
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PayOS service error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
