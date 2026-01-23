<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GHNService
{
    protected $baseUrl;
    protected $token;
    protected $shopId;
    protected $fromDistrictId;
    protected $serviceId;
    protected $serviceTypeId;
    protected $fromWardCode;

    public function __construct()
    {
        // Base URL cho GHN API v2 - endpoint create order
        // Production: https://online-gateway.ghn.vn/shiip/public-api/v2/
        // Test: https://dev-online-gateway.ghn.vn/shiip/public-api/v2/
        $this->baseUrl = config('services.ghn.base_url', 'https://online-gateway.ghn.vn/shiip/public-api/');
        
        // Đảm bảo baseUrl có dấu / ở cuối
        if (!str_ends_with($this->baseUrl, '/')) {
            $this->baseUrl .= '/';
        }
        
        $this->token = config('services.ghn.token');
        $this->shopId = (int) config('services.ghn.shop_id', 5236454);
        $this->fromDistrictId = (int) config('services.ghn.from_district_id', 1588); // Quận Lê Chân
        $this->serviceId = (int) config('services.ghn.service_id', 53320); // GHN Tiêu chuẩn
        $this->serviceTypeId = (int) config('services.ghn.service_type_id', 2); // Hàng dưới 20kg
        $this->fromWardCode = (string) config('services.ghn.from_ward_code', 30212); // Phường Vĩnh Niệm
        
        // Validate required config
        if (empty($this->token)) {
            throw new \Exception('GHN token is not configured. Please set APP_API_GHN in .env');
        }
        if (empty($this->shopId)) {
            throw new \Exception('GHN shop_id is not configured. Please set GHN_SHOP_ID in .env');
        }
    }

    /**
     * Tạo token để in đơn hàng GHN
     * 
     * @param array|string $orderCodes Mã đơn hàng GHN (có thể là string hoặc array)
     * @return array
     */
    public function generatePrintToken($orderCodes): array
    {
        try {
            // Đảm bảo orderCodes là array
            if (is_string($orderCodes)) {
                $orderCodes = [$orderCodes];
            }

            if (empty($orderCodes)) {
                throw new Exception('Vui lòng cung cấp mã đơn hàng GHN.');
            }

            $endpoint = $this->baseUrl . 'v2/a5/gen-token';

            $payload = [
                'order_codes' => $orderCodes,
            ];

            Log::info('Generating GHN print token', [
                'endpoint' => $endpoint,
                'order_codes' => $orderCodes,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Token' => $this->token,
            ])->post($endpoint, $payload);

            $responseData = $response->json();

            Log::info('GHN print token API response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // Check response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            $token = $responseData['data']['token'] ?? null;

            if (!$token) {
                throw new Exception('Không thể tạo token in đơn hàng từ GHN.');
            }

            // Tạo các link in theo các format khác nhau
            $basePrintUrl = str_contains($this->baseUrl, 'dev-') 
                ? 'https://dev-online-gateway.ghn.vn/a5/public-api'
                : 'https://online-gateway.ghn.vn/a5/public-api';

            $printUrls = [
                'a5' => $basePrintUrl . '/printA5?token=' . $token,
                '80x80' => $basePrintUrl . '/print80x80?token=' . $token,
                '52x70' => $basePrintUrl . '/print52x70?token=' . $token,
            ];

            return [
                'success' => true,
                'token' => $token,
                'print_urls' => $printUrls,
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate GHN print token', [
                'order_codes' => $orderCodes,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lấy danh sách ca lấy hàng (pick shifts) từ GHN
     */
    public function getPickShifts(): array
    {
        try {
            $endpoint = $this->baseUrl . 'v2/shift/date';

            Log::info('Getting GHN pick shifts', [
                'endpoint' => $endpoint,
            ]);

            // Docs says header is 'token' (lowercase)
            $response = Http::withHeaders([
                'token' => $this->token,
            ])->get($endpoint);

            $responseData = $response->json();

            Log::info('GHN pick shifts API response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // Check response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            $shifts = $responseData['data'] ?? [];

            return [
                'success' => true,
                'shifts' => $shifts,
            ];

        } catch (Exception $e) {
            Log::error('Failed to get GHN pick shifts', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'shifts' => [],
            ];
        }
    }

    /**
     * Tạo đơn hàng GHN
     */
    public function createOrder(Order $order, array $options = []): array
    {
        try {
            // Validate order có đủ thông tin không
            if (!$order->receiver_name || !$order->receiver_phone || !$order->shipping_address) {
                throw new Exception('Đơn hàng thiếu thông tin người nhận.');
            }

            if (!$order->shipping_district_id || !$order->shipping_ward_id) {
                throw new Exception('Đơn hàng thiếu thông tin địa chỉ giao hàng (quận/huyện, phường/xã).');
            }

            // Kiểm tra xem đơn hàng đã có tracking code chưa
            if ($order->shipping_tracking_code) {
                throw new Exception('Đơn hàng đã có mã vận đơn GHN: ' . $order->shipping_tracking_code);
            }

            // Tính toán weight và dimensions từ order items
            $weight = $options['weight'] ?? $this->calculateWeight($order);
            $dimensions = $this->calculateDimensions($order, $options);

            // Chuẩn bị items cho GHN
            $items = $this->formatOrderItems($order);

            // Chuẩn bị dữ liệu gửi đến GHN
            // Đảm bảo tất cả các trường số là integer/float đúng định dạng
            $pickShiftId = $options['pick_shift_id'] ?? $options['pick_shift'] ?? null;
            
            // Cast các giá trị số đảm bảo không null
            $weightValue = $weight ? (int) round((float) $weight) : 200; // Tối thiểu 200g
            $lengthValue = $dimensions['length'] ? (int) round((float) $dimensions['length']) : 10;
            $widthValue = $dimensions['width'] ? (int) round((float) $dimensions['width']) : 10;
            $heightValue = $dimensions['height'] ? (int) round((float) $dimensions['height']) : 10;
            $insuranceValue = (int) round((float) ($options['insurance_value'] ?? $order->final_price ?? 0));
            $finalPrice = (float) ($order->final_price ?? 0);
            
            $data = [
                // 1: Shop trả, 2: Người nhận trả
                'payment_type_id' => isset($options['payment_type_id'])
                    ? (int) $options['payment_type_id']
                    : ($order->payment_method === 'cod' ? 2 : 1),
                // Một số tài khoản GHN yêu cầu 2 trường này khi tính phí.
                // GHN dùng field name hoa đầu (ConfigFeeID, ExtraCostID).
                'ConfigFeeID' => isset($options['config_fee_id'])
                    ? (int) $options['config_fee_id']
                    : (int) config('services.ghn.config_fee_id', 0),
                'ExtraCostID' => isset($options['extra_cost_id'])
                    ? (int) $options['extra_cost_id']
                    : (int) config('services.ghn.extra_cost_id', 0),
                'note' => (string) ($order->admin_note ?? $order->customer_note ?? ''),
                'required_note' => (string) ($options['required_note'] ?? 'KHONGCHOXEMHANG'), // CHOTHUHANG, CHOXEMHANGKHONGTHU, KHONGCHOXEMHANG
                // FROM ADDRESS – BẮT BUỘC GHN YÊU CẦU
                'from_name' => config('services.ghn.from_name'),
                'from_phone' => config('services.ghn.from_phone'),
                'from_address' => config('services.ghn.from_address'),
                'from_ward_code' => config('services.ghn.from_ward_code'),
                'from_district_id' => config('services.ghn.from_district_id'),
                'return_phone' => (string) config('services.ghn.return_phone', '0827786198'),
                'return_address' => (string) config('services.ghn.return_address', '39 NTT'),
                'return_district_id' => (int) config('services.ghn.return_district_id', 0),
                'return_ward_code' => (string) config('services.ghn.return_ward_code', ''),
                'client_order_code' => (string) $order->code, // Mã đơn hàng của hệ thống
                'to_name' => (string) $order->receiver_name,
                'to_phone' => (string) $order->receiver_phone,
                'to_address' => (string) $order->shipping_address,
                'to_ward_code' => (string) $order->shipping_ward_id,
                'to_district_id' => (int) $order->shipping_district_id,
                'cod_amount' => $order->payment_method === 'cod' ? (int) round($finalPrice) : 0,
                'content' => 'Đơn hàng ' . $order->code,
                'weight' => $weightValue,
                'length' => $lengthValue,
                'width' => $widthValue,
                'height' => $heightValue,
                'insurance_value' => $insuranceValue,
                'service_id' => (int) ($options['service_id'] ?? $this->serviceId),
                'service_type_id' => (int) ($options['service_type_id'] ?? $this->serviceTypeId),
                'items' => $items,
            ];

            // Thêm các trường optional nếu có giá trị
            if (!empty($options['pick_station_id'])) {
                $data['pick_station_id'] = (int) $options['pick_station_id'];
            }
            if (!empty($options['deliver_station_id'])) {
                $data['deliver_station_id'] = (int) $options['deliver_station_id'];
            }
            if (!empty($options['coupon'])) {
                $data['coupon'] = (string) $options['coupon'];
            }
            if ($pickShiftId !== null && $pickShiftId !== '') {
                // GHN API yêu cầu pick_shift là mảng số nguyên, không phải số đơn
                $data['pick_shift'] = [(int) $pickShiftId];
            }

            // Loại bỏ các field null hoặc rỗng (trừ các trường bắt buộc)
            $data = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });

            // Xây dựng URL đầy đủ - endpoint đúng theo GHN docs
            // Production: https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/create
            // Test: https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/create
            // baseUrl đã có / ở cuối, nên chỉ cần thêm path
            $endpoint = $this->baseUrl . 'v2/shipping-order/create';
            
            Log::info('Creating GHN order', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'endpoint' => $endpoint,
                'shop_id' => $this->shopId,
                'token_length' => strlen($this->token ?? ''),
                'data' => $data
            ]);

            // Gọi API GHN
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ShopId' => (string) $this->shopId,
                'Token' => $this->token,
            ])->post($endpoint, $data);

            $responseData = $response->json();

            Log::info('GHN API response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'response' => $responseData,
                'order_id' => $order->id,
                'endpoint' => $endpoint
            ]);

            // Kiểm tra response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (empty($responseData['data']['order_code'])) {
                throw new Exception("GHN API did not return order_code");
            }

            $ghnData = $responseData['data'];

            $statusDefinitions = config('ghn.shipping_statuses', []);
            $readyDefinition = $statusDefinitions['ready_to_pick'] ?? [
                'label' => 'Chờ lấy hàng',
                'description' => 'Đơn GHN vừa được tạo.',
                'delivery_bucket' => 'pending',
            ];

            $existingRaw = $order->shipping_raw_response ?? [];
            $history = $existingRaw['status_history'] ?? [];
            $history[] = [
                'status' => 'ready_to_pick',
                'label' => $readyDefinition['label'],
                'description' => $readyDefinition['description'],
                'note' => 'Đơn GHN được tạo thành công.',
                'created_at' => now()->toIso8601String(),
                'created_by' => 'System',
                'source' => 'ghn_api',
            ];

            $existingRaw['status_history'] = $history;
            $existingRaw['current_status'] = 'ready_to_pick';
            $existingRaw['ghn'] = $ghnData;

            // Lưu thông tin GHN vào order
            // Đơn mới tạo có status 'ready_to_pick' → delivery_bucket: 'pending' → delivery_status: 'pending'
            $order->update([
                'shipping_tracking_code' => $ghnData['order_code'],
                'shipping_raw_response' => $existingRaw,
                'delivery_status' => $this->mapBucketToDeliveryStatus('pending'), // Đơn mới tạo nên là 'pending'
                'shipping_partner' => 'ghn',
            ]);

            Log::info('GHN order created successfully', [
                'order_id' => $order->id,
                'ghn_order_code' => $responseData['data']['order_code'],
                'sort_code' => $responseData['data']['sort_code'] ?? null,
                'expected_delivery_time' => $responseData['data']['expected_delivery_time'] ?? null
            ]);

            return [
                'success' => true,
                'order_code' => $responseData['data']['order_code'],
                'sort_code' => $responseData['data']['sort_code'] ?? null,
                'expected_delivery_time' => $responseData['data']['expected_delivery_time'] ?? null,
                'total_fee' => $responseData['data']['total_fee'] ?? null,
                'fee' => $responseData['data']['fee'] ?? null,
                'data' => $responseData['data']
            ];

        } catch (Exception $e) {
            Log::error('Failed to create GHN order', [
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
     * Đồng bộ trạng thái từ GHN
     */
    public function syncOrderStatus(Order $order): array
    {
        try {
            if (!$order->shipping_tracking_code) {
                throw new Exception('Đơn hàng chưa có mã vận đơn GHN.');
            }

            $endpoint = $this->baseUrl . 'v2/shipping-order/detail';

            $payload = [
                'order_code' => $order->shipping_tracking_code,
            ];

            Log::info('Syncing GHN order status', [
                'order_id' => $order->id,
                'tracking_code' => $order->shipping_tracking_code,
                'endpoint' => $endpoint,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ShopId' => (string) $this->shopId,
                'Token' => $this->token,
            ])->post($endpoint, $payload);

            $responseData = $response->json();

            Log::info('GHN sync response', [
                'status' => $response->status(),
                'response' => $responseData,
                'order_id' => $order->id,
            ]);

            if (!$response->successful() || ($responseData['code'] ?? 0) !== 200) {
                $message = $responseData['message'] ?? ('HTTP ' . $response->status());
                throw new Exception($this->translateGhnError($message));
            }

            $data = $responseData['data'] ?? [];
            $status = $data['status'] ?? null;

            $definitions = config('ghn.shipping_statuses', []);
            $definition = $definitions[$status] ?? [
                'label' => strtoupper($status),
                'description' => null,
                'delivery_bucket' => null,
            ];

            $raw = $order->shipping_raw_response ?? [];
            $history = $raw['status_history'] ?? [];
            $lastStatus = end($history)['status'] ?? null;

            if ($status && $status !== $lastStatus) {
                $history[] = [
                    'status' => $status,
                    'label' => $definition['label'] ?? $status,
                    'description' => $definition['description'] ?? null,
                    'note' => 'Đồng bộ tự động từ GHN',
                    'created_at' => $data['status_date'] ?? $data['updated_date'] ?? now()->toIso8601String(),
                    'source' => 'ghn_sync',
                ];
            }

            $raw['status_history'] = $history;
            if ($status) {
                $raw['current_status'] = $status;
            }
            $raw['ghn'] = $data;

            $update = [
                'shipping_raw_response' => $raw,
            ];

            // Xử lý cập nhật delivery_status
            // Kiểm tra trạng thái hủy trước (cancelled, cancel, hủy)
            $statusLower = strtolower($status ?? '');
            if (str_contains($statusLower, 'cancel') || str_contains($statusLower, 'hủy') || str_contains($statusLower, 'huỷ')) {
                $update['delivery_status'] = 'cancelled';
            } elseif (!empty($definition['delivery_bucket'])) {
                $update['delivery_status'] = $this->mapBucketToDeliveryStatus($definition['delivery_bucket']);
            } elseif (isset($data['cancel_reason']) || isset($data['cancel_reason_id'])) {
                // Nếu có cancel_reason hoặc cancel_reason_id thì đơn đã bị hủy
                $update['delivery_status'] = 'cancelled';
            }

            $order->update($update);

            return [
                'success' => true,
                'status' => $status,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Failed to sync GHN order status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getOrderInfo(string $orderCode): array
    {
        try {
            if (!$orderCode) {
                throw new Exception('Vui lòng nhập mã vận đơn GHN.');
            }

            $endpoint = $this->baseUrl . 'v2/shipping-order/detail';
            $payload = ['order_code' => $orderCode];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Token' => $this->token,
            ])->post($endpoint, $payload);

            $responseData = $response->json();

            if (!$response->successful() || ($responseData['code'] ?? 0) !== 200) {
                $message = $responseData['message'] ?? ('HTTP ' . $response->status());
                throw new Exception($this->translateGhnError($message));
            }

            $rawData = $responseData['data'] ?? [];
            $normalized = [];
            if ($rawData) {
                if (isset($rawData[0])) {
                    $normalized = $rawData;
                } else {
                    $normalized = [$rawData];
                }
            }

            return [
                'success' => true,
                'data' => $normalized,
            ];
        } catch (Exception $e) {
            Log::error('GHN get order info failed', [
                'order_code' => $orderCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Hủy vận đơn GHN
     */
    public function cancelOrder(Order $order, ?string $note = null): array
    {
        try {
            if (!$order->shipping_tracking_code) {
                throw new Exception('Không có mã vận đơn GHN để hủy.');
            }

            // Docs: https://online-gateway.ghn.vn/shiip/public-api/v2/switch-status/cancel
            $endpoint = $this->baseUrl . 'v2/switch-status/cancel';
            $payload = [
                'order_codes' => [$order->shipping_tracking_code],
            ];

            Log::info('Cancelling GHN order', [
                'order_id' => $order->id,
                'tracking_code' => $order->shipping_tracking_code,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ShopId' => (string) $this->shopId,
                'Token' => $this->token,
            ])->post($endpoint, $payload);

            $responseData = $response->json();

            if (!$response->successful() || ($responseData['code'] ?? 0) !== 200) {
                $message = $responseData['message'] ?? ('HTTP ' . $response->status());
                throw new Exception($this->translateGhnError($message));
            }

            $dataList = $responseData['data'] ?? [];
            $resultMap = collect($dataList)->firstWhere('order_code', $order->shipping_tracking_code);
            if ($resultMap && empty($resultMap['result'])) {
                throw new Exception($this->translateGhnError($resultMap['message'] ?? 'GHN từ chối hủy vận đơn.'));
            }

            $definitions = config('ghn.shipping_statuses', []);
            $definition = $definitions['cancel'] ?? [
                'label' => 'Đã hủy trên GHN',
                'description' => 'Vận đơn đã bị hủy từ hệ thống GHN.',
                'delivery_bucket' => 'cancelled',
            ];

            $raw = $order->shipping_raw_response ?? [];
            $history = $raw['status_history'] ?? [];
            $history[] = [
                'status' => 'cancel',
                'label' => $definition['label'] ?? 'cancel',
                'description' => $definition['description'] ?? null,
                'note' => $note,
                'created_at' => now()->toIso8601String(),
                'source' => 'ghn_api_cancel',
            ];
            $raw['status_history'] = $history;
            $raw['current_status'] = 'cancel';

            $update = [
                'shipping_raw_response' => $raw,
            ];

            // Luôn cập nhật delivery_status khi hủy
            if (!empty($definition['delivery_bucket'])) {
                $update['delivery_status'] = $this->mapBucketToDeliveryStatus($definition['delivery_bucket']);
            } else {
                // Nếu không có bucket nhưng đang hủy thì set cancelled
                $update['delivery_status'] = 'cancelled';
            }

            $order->update($update);

            return [
                'success' => true,
            ];
        } catch (Exception $e) {
            Log::error('Failed to cancel GHN order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cập nhật vận đơn GHN
     */
    public function updateGhnOrder(Order $order, array $payload): array
    {
        try {
            if (!$order->shipping_tracking_code) {
                throw new Exception('Đơn hàng chưa có mã vận đơn GHN.');
            }

            $endpoint = $this->baseUrl . 'v2/shipping-order/update';

            $defaults = [
                'order_code' => $order->shipping_tracking_code,
                'client_order_code' => $order->code,
                'payment_type_id' => $order->payment_method === 'cod' ? 2 : 1,
                'note' => $order->admin_note ?? '',
                'required_note' => 'KHONGCHOXEMHANG',
                'to_name' => $order->receiver_name,
                'to_phone' => $order->receiver_phone,
                'to_address' => $order->shipping_address,
                'to_ward_code' => (string) $order->shipping_ward_id,
                'to_district_id' => (int) $order->shipping_district_id,
                'cod_amount' => $order->payment_method === 'cod' ? (int) $order->final_price : 0,
                'content' => 'Đơn hàng ' . $order->code,
                'weight' => $this->calculateWeight($order),
                'length' => 10,
                'width' => 10,
                'height' => 10,
            ];

            $cleanPayload = array_filter($payload, function ($value) {
                return $value !== null && $value !== '';
            });

            $intFields = [
                'payment_type_id',
                'cod_amount',
                'to_district_id',
                'weight',
                'length',
                'width',
                'height',
            ];

            foreach ($intFields as $field) {
                if (isset($cleanPayload[$field])) {
                    $cleanPayload[$field] = (int) $cleanPayload[$field];
                }
            }

            $body = array_merge($defaults, $cleanPayload);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'ShopId' => (string) $this->shopId,
                'Token' => $this->token,
            ])->post($endpoint, $body);

            $responseData = $response->json();

            if (!$response->successful() || ($responseData['code'] ?? 0) !== 200) {
                $message = $responseData['message'] ?? ('HTTP ' . $response->status());
                throw new Exception($this->translateGhnError($message));
            }

            // Sync to capture new info
            $this->syncOrderStatus($order);

            return [
                'success' => true,
            ];
        } catch (Exception $e) {
            Log::error('Failed to update GHN order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Map delivery bucket -> order delivery_status
     */
    public function mapBucketToDeliveryStatus(?string $bucket): string
    {
        return match ($bucket) {
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'returned' => 'returned',
            'cancelled', 'cancel' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Tính toán weight từ order items
     */
    private function calculateWeight(Order $order): int
    {
        // Mặc định 200g mỗi sản phẩm, tối thiểu 200g
        $totalWeight = 0;
        foreach ($order->items as $item) {
            $totalWeight += ($item->quantity ?? 1) * 200; // 200g mỗi sản phẩm
        }
        return max(200, $totalWeight); // Tối thiểu 200g
    }

    /**
     * Tính toán dimensions từ order items
     */
    private function calculateDimensions(Order $order, array $options): array
    {
        // Mặc định dimensions
        return [
            'length' => $options['length'] ?? 10,
            'width' => $options['width'] ?? 10,
            'height' => $options['height'] ?? 10,
        ];
    }

    /**
     * Format order items cho GHN
     */
    private function formatOrderItems(Order $order): array
    {
        $items = [];
        
        foreach ($order->items as $item) {
            $product = $item->product;
            $items[] = [
                'name' => $product->name ?? 'Sản phẩm',
                'code' => $product->sku ?? $product->id,
                'quantity' => (int) ($item->quantity ?? 1),
                'price' => (int) ($item->price ?? 0),
                'length' => 12,
                'width' => 12,
                'height' => 12,
                'weight' => 200, // 200g mỗi sản phẩm
                'category' => [
                    'level1' => 'Thời trang'
                ]
            ];
        }

        return $items;
    }

    /**
     * Tạo ticket hỗ trợ GHN
     */
    public function createTicket(Order $order, array $data): array
    {
        try {
            if (!$order->shipping_tracking_code) {
                throw new Exception('Đơn hàng chưa có mã vận đơn GHN.');
            }

            $endpoint = $this->baseUrl . 'ticket/create';

            // Validate category
            $allowedCategories = [
                'Tư vấn',
                'Hối Giao/Lấy/Trả hàng',
                'Thay đổi thông tin',
                'Khiếu nại'
            ];

            $category = $data['category'] ?? 'Tư vấn';
            if (!in_array($category, $allowedCategories)) {
                throw new Exception('Loại ticket không hợp lệ.');
            }

            // Prepare request data
            $requestData = [
                'order_code' => $order->shipping_tracking_code,
                'category' => $category,
                'description' => $data['description'] ?? '',
                'c_email' => $data['c_email'] ?? '',
            ];

            // Validate description length
            if (mb_strlen($requestData['description']) > 2000) {
                throw new Exception('Mô tả không được vượt quá 2000 ký tự.');
            }

            Log::info('Creating GHN ticket', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'ghn_order_code' => $order->shipping_tracking_code,
                'category' => $category,
                'endpoint' => $endpoint,
            ]);

            // Prepare multipart request for file upload
            $http = Http::withHeaders([
                'Token' => $this->token,
            ]);

            // If there's a file attachment
            if (!empty($data['attachment']) && $data['attachment']->isValid()) {
                $http = $http->attach('attachments', file_get_contents($data['attachment']->getRealPath()), $data['attachment']->getClientOriginalName());
            }

            // Make request
            $response = $http->post($endpoint, $requestData);

            $responseData = $response->json();

            Log::info('GHN ticket API response', [
                'status' => $response->status(),
                'response' => $responseData,
                'order_id' => $order->id,
            ]);

            // Check response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            $ticketData = $responseData['data'] ?? [];

            if (empty($ticketData['id'])) {
                throw new Exception("GHN API did not return ticket ID");
            }

            // Save ticket info to order
            $raw = $order->shipping_raw_response ?? [];
            $tickets = $raw['tickets'] ?? [];
            $tickets[] = [
                'id' => $ticketData['id'],
                'order_code' => $ticketData['order_code'] ?? $order->shipping_tracking_code,
                'category' => $ticketData['type'] ?? $category,
                'description' => $ticketData['description'] ?? $requestData['description'],
                'status' => $ticketData['status'] ?? 'Đang xử lý',
                'status_id' => $ticketData['status_id'] ?? 1,
                'created_at' => $ticketData['created_at'] ?? now()->toIso8601String(),
                'created_by' => auth('web')->user()->name ?? 'System',
                'created_by_id' => auth('web')->id(),
            ];

            $raw['tickets'] = $tickets;
            $order->update([
                'shipping_raw_response' => $raw,
            ]);

            Log::info('GHN ticket created successfully', [
                'order_id' => $order->id,
                'ticket_id' => $ticketData['id'],
                'ticket_status' => $ticketData['status'] ?? 'Đang xử lý',
            ]);

            return [
                'success' => true,
                'ticket_id' => $ticketData['id'],
                'ticket' => $ticketData,
            ];

        } catch (Exception $e) {
            Log::error('Failed to create GHN ticket', [
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
     * Lấy thông tin chi tiết ticket GHN
     */
    public function getTicket(int $ticketId): array
    {
        try {
            if (!$ticketId) {
                throw new Exception('Vui lòng nhập mã ticket.');
            }

            $endpoint = $this->baseUrl . 'ticket/detail';

            $payload = [
                'ticket_id' => $ticketId,
            ];

            Log::info('Getting GHN ticket detail', [
                'ticket_id' => $ticketId,
                'endpoint' => $endpoint,
            ]);

            // Docs says "post/get" but curl example uses POST with body
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Token' => $this->token,
            ])->post($endpoint, $payload);

            $responseData = $response->json();

            Log::info('GHN ticket detail API response', [
                'status' => $response->status(),
                'response' => $responseData,
                'ticket_id' => $ticketId,
            ]);

            // Check response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            $ticketData = $responseData['data'] ?? [];

            if (empty($ticketData['id'])) {
                throw new Exception("GHN API did not return ticket data");
            }

            return [
                'success' => true,
                'ticket' => $ticketData,
            ];

        } catch (Exception $e) {
            Log::error('Failed to get GHN ticket detail', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Đồng bộ thông tin ticket từ GHN và cập nhật vào order
     */
    public function syncTicket(Order $order, int $ticketId): array
    {
        try {
            $result = $this->getTicket($ticketId);

            if (!$result['success']) {
                return $result;
            }

            $ticketData = $result['ticket'];

            // Update ticket in order's shipping_raw_response
            $raw = $order->shipping_raw_response ?? [];
            $tickets = $raw['tickets'] ?? [];

            // Find and update ticket
            $updated = false;
            foreach ($tickets as &$ticket) {
                if (($ticket['id'] ?? null) == $ticketId) {
                    $ticket['status'] = $ticketData['status'] ?? $ticket['status'];
                    $ticket['status_id'] = $ticketData['status_id'] ?? $ticket['status_id'];
                    $ticket['type'] = $ticketData['type'] ?? $ticket['category'] ?? $ticket['type'];
                    $ticket['description'] = $ticketData['description'] ?? $ticket['description'];
                    $ticket['updated_at'] = $ticketData['updated_at'] ?? now()->toIso8601String();
                    $ticket['conversations'] = $ticketData['conversations'] ?? [];
                    $ticket['attachments'] = $ticketData['attachments'] ?? [];
                    $ticket['c_email'] = $ticketData['c_email'] ?? null;
                    $ticket['c_name'] = $ticketData['c_name'] ?? null;
                    $updated = true;
                    break;
                }
            }

            // If ticket not found in list, add it
            if (!$updated) {
                $tickets[] = [
                    'id' => $ticketData['id'],
                    'order_code' => $ticketData['order_code'] ?? $order->shipping_tracking_code,
                    'category' => $ticketData['type'] ?? 'Tư vấn',
                    'description' => $ticketData['description'] ?? '',
                    'status' => $ticketData['status'] ?? 'Đang xử lý',
                    'status_id' => $ticketData['status_id'] ?? 1,
                    'created_at' => $ticketData['created_at'] ?? now()->toIso8601String(),
                    'updated_at' => $ticketData['updated_at'] ?? now()->toIso8601String(),
                    'conversations' => $ticketData['conversations'] ?? [],
                    'attachments' => $ticketData['attachments'] ?? [],
                    'c_email' => $ticketData['c_email'] ?? null,
                    'c_name' => $ticketData['c_name'] ?? null,
                    'created_by' => 'System',
                    'source' => 'ghn_sync',
                ];
            }

            $raw['tickets'] = $tickets;
            $order->update([
                'shipping_raw_response' => $raw,
            ]);

            return [
                'success' => true,
                'ticket' => $ticketData,
            ];

        } catch (Exception $e) {
            Log::error('Failed to sync GHN ticket', [
                'order_id' => $order->id,
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Phản hồi ticket GHN
     */
    public function replyTicket(int $ticketId, array $data): array
    {
        try {
            if (!$ticketId) {
                throw new Exception('Vui lòng nhập mã ticket.');
            }

            $endpoint = $this->baseUrl . 'ticket/reply';

            // Prepare request data
            $requestData = [
                'ticket_id' => $ticketId,
                'description' => $data['description'] ?? '',
            ];

            // Validate description
            if (empty($requestData['description'])) {
                throw new Exception('Vui lòng nhập nội dung phản hồi.');
            }

            Log::info('Replying to GHN ticket', [
                'ticket_id' => $ticketId,
                'endpoint' => $endpoint,
            ]);

            // Prepare multipart request for file upload
            $http = Http::withHeaders([
                'Token' => $this->token,
            ]);

            // If there's a file attachment
            if (!empty($data['attachment']) && $data['attachment']->isValid()) {
                $http = $http->attach('attachments', file_get_contents($data['attachment']->getRealPath()), $data['attachment']->getClientOriginalName());
            }

            // Make request (multipart/form-data)
            $response = $http->asMultipart()->post($endpoint, $requestData);

            $responseData = $response->json();

            Log::info('GHN ticket reply API response', [
                'status' => $response->status(),
                'response' => $responseData,
                'ticket_id' => $ticketId,
            ]);

            // Check response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            $replyData = $responseData['data'] ?? [];

            // After replying, sync ticket to get updated conversations
            // This will be done in controller if needed

            return [
                'success' => true,
                'reply' => $replyData,
            ];

        } catch (Exception $e) {
            Log::error('Failed to reply GHN ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lấy danh sách tickets từ GHN
     */
    public function getTicketList(?string $orderCode = null): array
    {
        try {
            $endpoint = $this->baseUrl . 'ticket/index';

            Log::info('Getting GHN ticket list', [
                'order_code' => $orderCode,
                'endpoint' => $endpoint,
                'shop_id' => $this->shopId,
            ]);

            $response = Http::withHeaders([
                'Token' => $this->token,
                'ShopId' => (string) $this->shopId,
            ])->get($endpoint);

            $responseData = $response->json();

            Log::info('GHN ticket list API response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // Check response
            if (!$response->successful()) {
                $errorMsg = $responseData['message'] ?? 'HTTP ' . $response->status();
                throw new Exception($this->translateGhnError($errorMsg));
            }

            if (($responseData['code'] ?? 0) !== 200) {
                $errorMsg = $responseData['message'] ?? 'Unknown GHN error';
                throw new Exception($this->translateGhnError($errorMsg));
            }

            $tickets = $responseData['data'] ?? [];

            // Filter by order_code if provided
            if ($orderCode && !empty($tickets)) {
                $tickets = array_filter($tickets, function ($ticket) use ($orderCode) {
                    return ($ticket['order_code'] ?? '') === $orderCode;
                });
                $tickets = array_values($tickets); // Re-index array
            }

            return [
                'success' => true,
                'tickets' => $tickets,
            ];

        } catch (Exception $e) {
            Log::error('Failed to get GHN ticket list', [
                'order_code' => $orderCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tickets' => [],
            ];
        }
    }

    /**
     * Đồng bộ danh sách tickets từ GHN và cập nhật vào order
     */
    public function syncTicketList(Order $order): array
    {
        try {
            if (!$order->shipping_tracking_code) {
                throw new Exception('Đơn hàng chưa có mã vận đơn GHN.');
            }

            $result = $this->getTicketList($order->shipping_tracking_code);

            if (!$result['success']) {
                return $result;
            }

            $ghnTickets = $result['tickets'] ?? [];

            // Update tickets in order's shipping_raw_response
            $raw = $order->shipping_raw_response ?? [];
            $existingTickets = $raw['tickets'] ?? [];

            // Create a map of existing tickets by ID
            $existingTicketsMap = [];
            foreach ($existingTickets as $ticket) {
                $existingTicketsMap[$ticket['id'] ?? null] = $ticket;
            }

            // Merge GHN tickets with existing tickets
            $mergedTickets = [];
            foreach ($ghnTickets as $ghnTicket) {
                $ticketId = $ghnTicket['id'] ?? null;
                
                if ($ticketId) {
                    // If ticket exists, merge data (keep local data like created_by)
                    if (isset($existingTicketsMap[$ticketId])) {
                        $existing = $existingTicketsMap[$ticketId];
                        $mergedTickets[] = array_merge($existing, [
                            'status' => $ghnTicket['status'] ?? $existing['status'],
                            'status_id' => $ghnTicket['status_id'] ?? $existing['status_id'],
                            'type' => $ghnTicket['type'] ?? $existing['category'] ?? $existing['type'],
                            'description' => $ghnTicket['description'] ?? $existing['description'],
                            'updated_at' => $ghnTicket['updated_at'] ?? $existing['updated_at'],
                            'order_code' => $ghnTicket['order_code'] ?? $existing['order_code'],
                            // Keep conversations and attachments from existing if they exist
                            'conversations' => $existing['conversations'] ?? $ghnTicket['conversations'] ?? [],
                            'attachments' => $existing['attachments'] ?? $ghnTicket['attachments'] ?? [],
                        ]);
                    } else {
                        // New ticket from GHN, add it
                        $mergedTickets[] = [
                            'id' => $ghnTicket['id'],
                            'order_code' => $ghnTicket['order_code'] ?? $order->shipping_tracking_code,
                            'category' => $ghnTicket['type'] ?? 'Tư vấn',
                            'description' => $ghnTicket['description'] ?? '',
                            'status' => $ghnTicket['status'] ?? 'Đang xử lý',
                            'status_id' => $ghnTicket['status_id'] ?? 1,
                            'created_at' => $ghnTicket['created_at'] ?? now()->toIso8601String(),
                            'updated_at' => $ghnTicket['updated_at'] ?? now()->toIso8601String(),
                            'conversations' => $ghnTicket['conversations'] ?? [],
                            'attachments' => $ghnTicket['attachments'] ?? [],
                            'created_by' => 'GHN',
                            'source' => 'ghn_sync',
                        ];
                    }
                }
            }

            // Sort by created_at desc
            usort($mergedTickets, function ($a, $b) {
                $timeA = strtotime($a['created_at'] ?? 0);
                $timeB = strtotime($b['created_at'] ?? 0);
                return $timeB - $timeA;
            });

            $raw['tickets'] = $mergedTickets;
            $order->update([
                'shipping_raw_response' => $raw,
            ]);

            return [
                'success' => true,
                'tickets' => $mergedTickets,
            ];

        } catch (Exception $e) {
            Log::error('Failed to sync GHN ticket list', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tickets' => [],
            ];
        }
    }

    /**
     * Chuẩn hóa thông báo lỗi GHN sang tiếng Việt dễ hiểu
     */
    private function translateGhnError(?string $message): string
    {
        if (!$message) {
            return 'GHN: Lỗi không xác định.';
        }

        $normalized = strtolower($message);

        if (str_contains($normalized, 'đơn hàng không tồn tại')) {
            return 'Đơn hàng không tồn tại.';
        }

        if (str_contains($normalized, 'corev2_tenant_order_detail')) {
            return 'Đơn hàng không tồn tại.';
        }

        if (str_contains($normalized, 'ward') || str_contains($normalized, 'phường')) {
            return 'GHN: Mã phường/xã hoặc quận/huyện không tồn tại trong hệ thống.';
        }

        if (str_contains($normalized, 'unmarshal type error')) {
            return 'GHN: Dữ liệu gửi lên không đúng định dạng (ví dụ: trường số nhưng gửi chuỗi).';
        }

        if (preg_match('/Not support change field:\s*\[([^\]]+)\]\s*with order status:\s*(\w+)/i', $message, $matches)) {
            $fields = str_replace(['[', ']', ','], ['', '', ', '], trim($matches[1]));
            $status = $matches[2];
            return "GHN không cho phép thay đổi các trường {$fields} khi vận đơn đang ở trạng thái {$status}.";
        }

        if (str_contains($normalized, 'not found') || str_contains($normalized, 'does not exist')) {
            return 'GHN: Không tìm thấy vận đơn. Vui lòng kiểm tra lại mã vận đơn.';
        }

        return 'GHN: ' . $message;
    }
}

