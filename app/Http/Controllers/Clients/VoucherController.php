<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
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
     * Validate voucher code
     */
    public function validateVoucher(Request $request)
    {
        $validated = $request->validate([
            'voucher_code' => 'required|string|max:50|regex:/^[A-Za-z0-9\-_]+$/',
            'order_data' => 'required|array',
            'order_data.items' => 'required|array|min:1',
            'order_data.items.*.product_id' => 'required|integer|exists:products,id',
            'order_data.items.*.quantity' => 'required|integer|min:1',
            'order_data.items.*.total_price' => 'required|numeric|min:0',
            'order_data.shipping_fee' => 'nullable|numeric|min:0',
        ]);

        // Sanitize inputs to prevent XSS
        $validated = $this->sanitizeInput($validated);

        try {
            $userId = auth('web')->id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để sử dụng voucher.',
                    'discount_amount' => 0,
                    'requires_login' => true,
                ], 401);
            }

            $result = $this->voucherService->checkVoucherEligibility(
                $validated['voucher_code'],
                $validated['order_data'],
                $userId
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Voucher validation error', [
                'voucher_code' => $validated['voucher_code'],
                'user_id' => auth('web')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xử lý voucher. Vui lòng thử lại.',
                'discount_amount' => 0
            ], 500);
        }
    }

    /**
     * Get available vouchers for user
     */
    public function getAvailableVouchers(Request $request)
    {
        try {
            $userId = auth('web')->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng đăng nhập để xem voucher.',
                    'vouchers' => []
                ]);
            }

            $vouchers = \App\Models\Voucher::active()
                ->where(function ($query) use ($userId) {
                    $query->whereNull('account_id') // Voucher công khai
                          ->orWhere('account_id', $userId); // Voucher của user
                })
                ->where('start_at', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('end_at')
                          ->orWhere('end_at', '>=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('usage_limit')
                          ->orWhereRaw('usage_count < usage_limit');
                })
                ->select([
                    'id', 'code', 'name', 'description', 'type', 'value',
                    'min_order_amount', 'max_discount_amount', 'applicable_to',
                    'start_at', 'end_at'
                ])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'vouchers' => $vouchers
            ]);

        } catch (\Exception $e) {
            Log::error('Get available vouchers error', [
                'user_id' => auth('web')->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải danh sách voucher.',
                'vouchers' => []
            ], 500);
        }
    }
}
