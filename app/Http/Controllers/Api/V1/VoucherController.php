<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherService $voucherService)
    {
    }

    public function active(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $vouchers = Voucher::active()
            ->orderByDesc('created_at')
            ->limit($limit > 0 ? $limit : 20)
            ->get();

        return response()->json([
            'data' => VoucherResource::collection($vouchers),
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $voucher = $this->voucherService->findByCode($code);
        if (!$voucher || $voucher->status !== Voucher::STATUS_ACTIVE) {
            return response()->json(['message' => 'Voucher không tồn tại hoặc đã tắt.'], 404);
        }

        return response()->json([
            'data' => new VoucherResource($voucher),
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'voucher_code' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.category_id' => ['nullable', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.is_flash_sale' => ['nullable', 'boolean'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $userId = $request->user('sanctum')?->id ?? $request->input('account_id');

        $result = $this->voucherService->validateAndApplyVoucher(
            $payload['voucher_code'],
            $payload,
            $userId ? (int) $userId : null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}


