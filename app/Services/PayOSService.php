<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

/**
 * PayOS Service - Xử lý tích hợp thanh toán PayOS
 * 
 * PayOS là cổng thanh toán của Việt Nam, hỗ trợ nhiều phương thức thanh toán
 * như thẻ ngân hàng, ví điện tử, QR code, v.v.
 * 
 * @author NobiFashion Team
 * @version 1.0
 */
class PayOSService
{
    private string $clientId;
    private string $apiKey;
    private string $checksumKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = Config::get('services.pay_os.client_id');
        $this->apiKey = Config::get('services.pay_os.api_key');
        $this->checksumKey = Config::get('services.pay_os.checksum_key');
        $this->baseUrl = Config::get('services.pay_os.base_url');

        // Validate configuration
        if (empty($this->clientId) || empty($this->apiKey) || empty($this->checksumKey)) {
            throw new Exception('PayOS configuration is missing. Please check your .env file.');
        }
    }

    /**
     * Tạo link thanh toán PayOS
     * 
     * @param Order $order Đơn hàng cần thanh toán
     * @param string $returnUrl URL trả về sau khi thanh toán thành công
     * @param string $cancelUrl URL trả về khi hủy thanh toán
     * @return array Kết quả tạo link thanh toán
     * @throws Exception
     */
    public function createPaymentLink(Order $order, string $returnUrl = null, string $cancelUrl = null): array
    {
        try {
            // PayOS yêu cầu orderCode phải là số nguyên dương (max: 9007199254740991)
            // Convert mã đơn hàng (format: ORD-YYYYMMDD-RANDOM) thành số integer
            // Mã đơn hàng gốc vẫn được lưu trong database và hiển thị cho user
            $orderCode = $this->convertOrderCodeToInteger($order->code, $order->id);
            
            // Kiểm tra xem orderCode này đã được sử dụng chưa trong payments pending
            $existingPayment = Payment::where('transaction_code', (string) $orderCode)
                ->where('status', 'pending')
                ->where('method', 'payos')
                ->first();
            
            // Nếu đã có payment với orderCode này, thêm timestamp để tạo orderCode mới
            if ($existingPayment) {
                $timestamp = time();
                // Kết hợp orderCode với timestamp để tạo số mới
                $orderCode = ($orderCode % 1000000) * 10000 + ($timestamp % 10000);
                // Đảm bảo không vượt quá giới hạn
                $maxValue = 9007199254740991;
                if ($orderCode > $maxValue) {
                    $orderCode = $orderCode % $maxValue;
                }
                // Đảm bảo là số dương
                $orderCode = max(1, $orderCode);
                
                Log::info('Generated new orderCode to avoid duplicate', [
                    'original_orderCode' => $this->convertOrderCodeToInteger($order->code, $order->id),
                    'new_orderCode' => $orderCode,
                    'existing_payment_id' => $existingPayment->id
                ]);
            }
            
            // Tạo description ngắn gọn (tối đa 25 ký tự) - sử dụng mã đơn hàng gốc
            $description = "Đơn hàng " . $order->code;
            if (strlen($description) > 25) {
                $description = "ĐH " . substr($order->code, -10); // Lấy 10 ký tự cuối
            }

            // Chuẩn bị dữ liệu gửi đến PayOS
            $paymentData = [
                'orderCode' => $orderCode,
                'amount' => (int) $order->final_price, // PayOS yêu cầu số nguyên (VND)
                'description' => $description,
                'items' => $this->formatOrderItems($order),
                'returnUrl' => $returnUrl ?? route('payment.payos.return'),
                'cancelUrl' => $cancelUrl ?? route('payment.payos.cancel'),
                'buyerName' => $order->receiver_name,
                'buyerEmail' => $order->receiver_email,
                'buyerPhone' => $order->receiver_phone,
                'buyerAddress' => $order->shipping_address,
                'expiredAt' => now()->addHours(24)->timestamp, // Link hết hạn sau 24h
            ];

            // Tạo signature chỉ từ các trường cần thiết theo PayOS
            $signatureData = [
                'orderCode' => $orderCode,
                'amount' => (int) $order->final_price,
                'description' => $description,
                'returnUrl' => $returnUrl ?? route('payment.payos.return'),
                'cancelUrl' => $cancelUrl ?? route('payment.payos.cancel'),
            ];
            $paymentData['signature'] = $this->createSignature($signatureData);

            // Debug signature generation
            $debugDataStr = '';
            ksort($signatureData);
            foreach ($signatureData as $key => $value) {
                $cleanValue = $value === null ? '' : (string) $value;
                $debugDataStr .= $key . '=' . $cleanValue . '&';
            }
            $debugDataStr = rtrim($debugDataStr, '&');

            Log::info('Creating PayOS payment link', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'orderCode' => $orderCode,
                'amount' => $paymentData['amount'],
                'signature_data' => $signatureData,
                'signature_string' => $debugDataStr,
                'signature' => $paymentData['signature'],
                'checksum_key_length' => strlen($this->checksumKey)
            ]);

            // Gọi API PayOS
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/v2/payment-requests", $paymentData);

            $responseData = $response->json();

            Log::info('PayOS API response', [
                'status' => $response->status(),
                'response' => $responseData,
                'request_data' => $paymentData
            ]);

            // Kiểm tra response có hợp lệ không
            if (!$response->successful()) {
                $errorMsg = $responseData['desc'] ?? $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception("PayOS API HTTP error: " . $errorMsg);
            }

            // Kiểm tra code từ PayOS (có thể là '00' hoặc số khác)
            if (isset($responseData['code']) && $responseData['code'] !== '00' && $responseData['code'] !== 0) {
                $errorMsg = $responseData['desc'] ?? $responseData['message'] ?? 'Unknown PayOS error';
                throw new Exception("PayOS API error (code: {$responseData['code']}): " . $errorMsg);
            }

            // Kiểm tra có checkoutUrl không
            if (empty($responseData['data']['checkoutUrl'])) {
                throw new Exception("PayOS API did not return checkoutUrl");
            }

            // Lưu thông tin thanh toán vào database
            $payment = $this->createPaymentRecord($order, $orderCode, $responseData);
            
            // Refresh payment để đảm bảo dữ liệu đã được lưu
            $payment->refresh();

            Log::info('PayOS payment link created successfully', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'checkout_url' => $responseData['data']['checkoutUrl'],
                'raw_response_saved' => !empty($payment->raw_response)
            ]);

            return [
                'success' => true,
                'checkout_url' => $responseData['data']['checkoutUrl'],
                'payment_id' => $payment->id,
                'order_code' => $orderCode,
                'data' => $responseData['data']
            ];

        } catch (Exception $e) {
            Log::error('Failed to create PayOS payment link', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Xác thực callback từ PayOS
     * 
     * @param array $data Dữ liệu callback từ PayOS
     * @return array Kết quả xác thực
     */
    public function verifyCallback(array $data): array
    {
        try {
            // Kiểm tra signature chỉ khi có signature field (webhook)
            if (isset($data['signature']) && !$this->verifySignature($data)) {
                Log::warning('PayOS callback signature verification failed', ['data' => $data]);
                return [
                    'success' => false,
                    'error' => 'Invalid signature'
                ];
            }
            
            // Nếu không có signature (return callback), bỏ qua verification
            if (!isset($data['signature'])) {
                Log::info('PayOS return callback - skipping signature verification', ['data' => $data]);
            }

            $orderCode = $data['orderCode'];
            $status = $data['status'];

            Log::info('PayOS callback received', [
                'orderCode' => $orderCode,
                'status' => $status,
                'data' => $data
            ]);

            // Tìm payment record
            $payment = Payment::where('transaction_code', $orderCode)->first();
            if (!$payment) {
                Log::warning('Payment record not found for PayOS callback', ['orderCode' => $orderCode]);
                return [
                    'success' => false,
                    'error' => 'Payment record not found'
                ];
            }

            $order = $payment->order;

            // Xử lý theo trạng thái
            switch ($status) {
                case 'PAID':
                    $this->handlePaymentSuccess($order, $payment, $data);
                    break;
                case 'CANCELLED':
                    $this->handlePaymentCancelled($order, $payment, $data);
                    break;
                case 'EXPIRED':
                    $this->handlePaymentExpired($order, $payment, $data);
                    break;
                default:
                    Log::warning('Unknown PayOS payment status', ['status' => $status]);
            }

            return [
                'success' => true,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'status' => $status
            ];

        } catch (Exception $e) {
            Log::error('PayOS callback verification failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Hủy thanh toán PayOS
     * 
     * @param Payment $payment Payment record cần hủy
     * @return array Kết quả hủy thanh toán
     */
    public function cancelPayment(Payment $payment): array
    {
        try {
            $orderCode = $payment->transaction_code;

            Log::info('Cancelling PayOS payment', [
                'payment_id' => $payment->id,
                'orderCode' => $orderCode
            ]);

            // Gọi API hủy thanh toán
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/v2/payment-requests/{$orderCode}/cancel");

            $responseData = $response->json();

            if (!$response->successful() || $responseData['code'] !== '00') {
                throw new Exception("PayOS cancel error: " . ($responseData['desc'] ?? 'Unknown error'));
            }

            // Cập nhật trạng thái payment
            $payment->update([
                'status' => 'failed',
                'raw_response' => array_merge(
                    $payment->raw_response ?? [],
                    ['cancelled_at' => now()->toISOString(), 'cancel_response' => $responseData]
                )
            ]);

            // KHÔNG cập nhật trạng thái order khi hủy payment để tạo lại
            // Order vẫn giữ nguyên trạng thái pending để có thể tạo payment mới

            Log::info('PayOS payment cancelled successfully', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order->id
            ]);

            return [
                'success' => true,
                'message' => 'Payment cancelled successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to cancel PayOS payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin thanh toán từ PayOS
     * 
     * @param string $orderCode Mã đơn hàng PayOS
     * @return array Thông tin thanh toán
     */
    public function getPaymentInfo(string $orderCode): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-client-id' => $this->clientId,
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/v2/payment-requests/{$orderCode}");

            $responseData = $response->json();

            if (!$response->successful() || $responseData['code'] !== '00') {
                throw new Exception("PayOS API error: " . ($responseData['desc'] ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'data' => $responseData['data']
            ];

        } catch (Exception $e) {
            Log::error('Failed to get PayOS payment info', [
                'orderCode' => $orderCode,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert mã đơn hàng (ORD-YYYYMMDD-RANDOM) thành số integer cho PayOS
     * PayOS yêu cầu orderCode phải là số nguyên dương (max: 9007199254740991)
     * Sử dụng hash kết hợp với order ID để đảm bảo tính duy nhất
     */
    private function convertOrderCodeToInteger(string $orderCode, ?int $orderId = null): int
    {
        // Sử dụng hash của mã đơn hàng để tạo số integer
        // CRC32 cho giá trị 32-bit (0 đến 4294967295)
        $hash = abs(crc32($orderCode));
        
        // Kết hợp với order ID nếu có để đảm bảo tính duy nhất
        // PayOS max: 9007199254740991 (MAX_SAFE_INTEGER)
        if ($orderId) {
            // Kết hợp hash và order ID: hash * 10000 + orderId (giả sử orderId < 10000)
            // Hoặc đơn giản hơn: sử dụng order ID làm base và thêm hash
            $result = ($orderId * 1000000) + ($hash % 1000000);
        } else {
            $result = $hash;
        }
        
        // Đảm bảo không vượt quá giới hạn PayOS
        $maxValue = 9007199254740991;
        if ($result > $maxValue) {
            // Nếu vượt quá, sử dụng modulo
            $result = $result % $maxValue;
        }
        
        // Đảm bảo là số dương (PayOS yêu cầu)
        return max(1, $result);
    }

    /**
     * Format danh sách sản phẩm cho PayOS
     */
    private function formatOrderItems(Order $order): array
    {
        $items = [];
        
        foreach ($order->items as $item) {
            // Đảm bảo product_name là string và không null
            $productName = $item->product_name ?? 'Sản phẩm';
            if (!is_string($productName)) {
                $productName = (string) $productName;
            }
            
            // Rút ngắn tên sản phẩm nếu quá dài (PayOS có thể có giới hạn)
            if (strlen($productName) > 100) {
                $productName = substr($productName, 0, 97) . '...';
            }
            
            $items[] = [
                'name' => $productName,
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->price, // PayOS yêu cầu số nguyên
            ];
        }

        return $items;
    }

    /**
     * Tạo signature để bảo mật theo chuẩn PayOS
     */
    private function createSignature(array $data): string
    {
        // Loại bỏ signature khỏi data để tính toán
        unset($data['signature']);
        
        // Sắp xếp theo key
        ksort($data);
        
        // Tạo chuỗi để hash theo format PayOS (không URL encode)
        $dataStr = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $dataStr .= $key . '=' . json_encode($value, JSON_UNESCAPED_UNICODE) . '&';
            } else {
                // Không URL encode, chỉ thay thế null bằng chuỗi rỗng
                $cleanValue = $value === null ? '' : (string) $value;
                $dataStr .= $key . '=' . $cleanValue . '&';
            }
        }
        
        $dataStr = rtrim($dataStr, '&');
        
        // Sử dụng HMAC-SHA256
        return hash_hmac('sha256', $dataStr, $this->checksumKey);
    }

    /**
     * Xác thực signature từ PayOS
     */
    private function verifySignature(array $data): bool
    {
        $receivedSignature = $data['signature'] ?? '';
        unset($data['signature']);
        
        $expectedSignature = $this->createSignature($data);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Tạo payment record trong database
     */
    private function createPaymentRecord(Order $order, int $orderCode, array $responseData): Payment
    {
        // Đảm bảo raw_response được lưu đúng format JSON
        $payment = Payment::create([
            'order_id' => $order->id,
            'account_id' => $order->account_id,
            'method' => 'payos',
            'amount' => $order->final_price,
            'status' => 'pending',
            'transaction_code' => (string) $orderCode,
            'gateway' => 'payos',
            'raw_response' => $responseData, // Lưu toàn bộ response từ PayOS
        ]);
        
        // Log để debug
        Log::info('Payment record created', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'has_raw_response' => !empty($payment->raw_response),
            'raw_response_type' => gettype($payment->raw_response),
            'checkout_url_in_response' => isset($responseData['data']['checkoutUrl'])
        ]);
        
        return $payment;
    }

    /**
     * Xử lý thanh toán thành công
     */
    private function handlePaymentSuccess(Order $order, Payment $payment, array $data): void
    {
        // Idempotency: if already marked paid/success, do nothing
        if (($order->payment_status ?? null) === 'paid' || ($payment->status ?? null) === 'success') {
            Log::info('Payment success callback ignored (already processed)', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'order_payment_status' => $order->payment_status,
                'payment_status' => $payment->status
            ]);
            return;
        }

        // Cập nhật payment - sử dụng 'success' để khớp với enum trong migration
        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
            'transaction_code' => (string) ($data['orderCode'] ?? $payment->transaction_code),
            'raw_response' => array_merge(
                $payment->raw_response ?? [],
                ['paid_at' => now()->toISOString(), 'callback_data' => $data]
            )
        ]);
        
        // Refresh payment để đảm bảo dữ liệu đã được lưu
        $payment->refresh();
        
        Log::info('Payment status updated to success', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'payment_status' => $payment->status,
            'paid_at' => $payment->paid_at
        ]);

        // Trừ tồn kho theo từng mặt hàng trong đơn
        foreach ($order->items as $item) {
            $quantityToDeduct = (int) ($item->quantity ?? 0);
            if ($quantityToDeduct <= 0) {
                continue;
            }

            try {
                if (!empty($item->product_variant_id)) {
                    $variant = ProductVariant::find($item->product_variant_id);
                    if ($variant) {
                        // ensure not going below zero
                        $deduct = min($quantityToDeduct, max(0, (int) $variant->stock_quantity));
                        if ($deduct > 0) {
                            $variant->decrement('stock_quantity', $deduct);
                        }
                        Log::info('Stock deducted (variant)', [
                            'variant_id' => $variant->id,
                            'deduct' => $deduct,
                            'remaining' => $variant->stock_quantity,
                            'order_id' => $order->id,
                        ]);
                    }
                } else {
                    $product = $item->product ?? Product::find($item->product_id);
                    if ($product) {
                        $deduct = min($quantityToDeduct, max(0, (int) $product->stock_quantity));
                        if ($deduct > 0) {
                            $product->decrement('stock_quantity', $deduct);
                        }
                        Log::info('Stock deducted (product)', [
                            'product_id' => $product->id,
                            'deduct' => $deduct,
                            'remaining' => $product->stock_quantity,
                            'order_id' => $order->id,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Failed to deduct stock on payment success', [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Cập nhật order
        $order->update([
            'payment_status' => 'paid',
            'status' => 'processing',
            'transaction_code' => (string) ($data['orderCode'] ?? $order->transaction_code)
        ]);
        
        // Refresh order để đảm bảo dữ liệu đã được lưu
        $order->refresh();

        Log::info('Payment marked as successful', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'order_status' => $order->status,
            'order_payment_status' => $order->payment_status,
            'order_transaction_code' => $order->transaction_code
        ]);
    }

    /**
     * Xử lý thanh toán bị hủy
     */
    private function handlePaymentCancelled(Order $order, Payment $payment, array $data): void
    {
        // Cập nhật payment
        $payment->update([
            'status' => 'failed',
            'raw_response' => array_merge(
                $payment->raw_response ?? [],
                ['cancelled_at' => now()->toISOString(), 'callback_data' => $data]
            )
        ]);

        // Cập nhật order
        $order->update([
            'payment_status' => 'failed',
            'status' => 'cancelled'
        ]);

        Log::info('Payment marked as cancelled', [
            'order_id' => $order->id,
            'payment_id' => $payment->id
        ]);
    }

    /**
     * Xử lý thanh toán hết hạn
     */
    private function handlePaymentExpired(Order $order, Payment $payment, array $data): void
    {
        // Cập nhật payment
        $payment->update([
            'status' => 'failed',
            'raw_response' => array_merge(
                $payment->raw_response ?? [],
                ['expired_at' => now()->toISOString(), 'callback_data' => $data]
            )
        ]);

        // Cập nhật order
        $order->update([
            'payment_status' => 'failed',
            'status' => 'cancelled'
        ]);

        Log::info('Payment marked as expired', [
            'order_id' => $order->id,
            'payment_id' => $payment->id
        ]);
    }
}