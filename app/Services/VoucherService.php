<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherHistory;
use App\Models\VoucherUserUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VoucherService
{
    public function findByCode(string $code): ?Voucher
    {
        $normalized = strtoupper(trim($code));

        return Cache::remember("voucher:{$normalized}", now()->addMinutes(5), function () use ($normalized) {
            return Voucher::withTrashed()->where('code', $normalized)->first();
        });
    }

    public function validateAndApplyVoucher(string $voucherCode, array $orderData, ?int $userId = null, array $options = []): array
    {
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Vui lòng đăng nhập để sử dụng voucher.',
                'discount_amount' => 0,
                'requires_login' => true,
            ];
        }

        $voucher = $this->findByCode($voucherCode);
        if (!$voucher || $voucher->trashed()) {
            return $this->errorResponse('Mã voucher không tồn tại.', 404);
        }

        $voucher->refreshComputedStatus();
        if ($voucher->status !== Voucher::STATUS_ACTIVE) {
            return $this->errorResponse('Voucher không hoạt động.');
        }

        if ($voucher->account_id && (int) $voucher->account_id !== $userId) {
            return $this->errorResponse('Voucher chỉ dành cho tài khoản được chỉ định.');
        }

        $summary = $this->summarizeOrderData($orderData);
        if ($summary['subtotal'] <= 0) {
            return $this->errorResponse('Giỏ hàng không hợp lệ.');
        }

        if (!$this->checkFlashSaleCompatibility($orderData, $options)) {
            return $this->errorResponse('Voucher không áp dụng cho sản phẩm Flash Sale.');
        }

        $userUsageCount = $this->getUserVoucherUsageCount($voucher->id, $userId);
        if (!$voucher->isValid($summary['subtotal'], $userUsageCount)) {
            return $this->errorResponse(
                $this->getVoucherValidationMessage($voucher, $summary['subtotal'], $userUsageCount)
            );
        }

        if (!$this->checkApplicability($voucher, $summary['items'])) {
            return $this->errorResponse('Voucher không áp dụng cho sản phẩm trong giỏ.');
        }

        $discountBreakdown = $this->calculateDiscount($voucher, $summary);
        if ($discountBreakdown['total_discount'] <= 0) {
            return $this->errorResponse('Voucher không mang lại ưu đãi cho đơn hàng này.');
        }

        return [
            'success' => true,
            'message' => 'Áp dụng voucher thành công.',
            'discount_amount' => $discountBreakdown['order_discount'],
            'shipping_discount' => $discountBreakdown['shipping_discount'],
            'total_discount' => $discountBreakdown['total_discount'],
            'voucher' => $voucher,
        ];
    }

    public function applyVoucherToOrder(Order $order, Voucher $voucher, float $orderDiscount, float $shippingDiscount = 0): void
    {
        DB::transaction(function () use ($order, $voucher, $orderDiscount, $shippingDiscount) {
            $totalDiscount = $orderDiscount + $shippingDiscount;

            $order->update([
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->code,
                'voucher_discount' => $totalDiscount,
                'final_price' => max(0, $order->final_price - $totalDiscount),
            ]);

            $voucher->incrementUsage();

            if ($order->account_id) {
                $usage = VoucherUserUsage::query()
                    ->where('voucher_id', $voucher->id)
                    ->where('account_id', $order->account_id)
                    ->lockForUpdate()
                    ->first();

                if (!$usage) {
                    $usage = new VoucherUserUsage([
                        'voucher_id' => $voucher->id,
                        'account_id' => $order->account_id,
                        'usage_count' => 0,
                    ]);
                }

                $usage->usage_count = ($usage->usage_count ?? 0) + 1;
                $usage->last_used_at = now();
                $usage->save();
            }
        });
    }

    public function logHistory(Voucher $voucher, string $action, ?array $before = null, ?array $after = null, ?string $note = null): void
    {
        VoucherHistory::create([
            'voucher_id' => $voucher->id,
            'account_id' => auth('web')->id(),
            'action' => $action,
            'note' => $note,
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function checkVoucherEligibility(string $voucherCode, array $orderData, int $userId = null): array
    {
        return $this->validateAndApplyVoucher($voucherCode, $orderData, $userId);
    }

    protected function errorResponse(string $message, int $code = 422): array
    {
        return [
            'success' => false,
            'message' => $message,
            'status_code' => $code,
            'discount_amount' => 0,
            'shipping_discount' => 0,
            'total_discount' => 0,
        ];
    }

    protected function summarizeOrderData(array $orderData): array
    {
        $items = collect($orderData['items'] ?? [])
            ->map(function ($item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $quantity = $quantity > 0 ? $quantity : 1;

                $price = (float) ($item['price'] ?? $item['unit_price'] ?? 0);
                $total = (float) ($item['total_price'] ?? $price * $quantity);

                return [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'category_id' => (int) ($item['category_id'] ?? 0),
                    'quantity' => $quantity,
                    'price' => $price,
                    'total_price' => $total,
                    'is_flash_sale' => (bool) ($item['is_flash_sale'] ?? false),
                ];
            })
            ->filter(fn ($item) => $item['product_id'] > 0)
            ->values()
            ->all();

        $subtotal = collect($items)->sum('total_price');

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_fee' => (float) ($orderData['shipping_fee'] ?? 0),
        ];
    }

    protected function checkFlashSaleCompatibility(array $orderData, array $options = []): bool
    {
        $allowFlashSale = (bool) ($options['allow_flash_sale'] ?? false);

        if ($allowFlashSale) {
            return true;
        }

        foreach ($orderData['items'] ?? [] as $item) {
            if (!empty($item['is_flash_sale'])) {
                return false;
            }
        }

        return true;
    }

    protected function getUserVoucherUsageCount(int $voucherId, ?int $userId): int
    {
        if (!$userId) {
            return 0;
        }

        return VoucherUserUsage::query()
            ->where('voucher_id', $voucherId)
            ->where('account_id', $userId)
            ->value('usage_count') ?? 0;
    }

    protected function checkApplicability(Voucher $voucher, array $items): bool
    {
        if ($voucher->applicable_to === Voucher::APPLICABLE_ALL) {
            return !empty($items);
        }

        if (empty($voucher->applicable_ids)) {
            return false;
        }

        $ids = collect($voucher->applicable_ids)->map(fn ($id) => (int) $id)->filter()->all();

        foreach ($items as $item) {
            if ($voucher->applicable_to === Voucher::APPLICABLE_PRODUCTS && in_array($item['product_id'], $ids, true)) {
                return true;
            }

            if ($voucher->applicable_to === Voucher::APPLICABLE_CATEGORIES && in_array($item['category_id'], $ids, true)) {
                return true;
            }
        }

        return false;
    }

    protected function calculateDiscount(Voucher $voucher, array $summary): array
    {
        $orderDiscount = 0;
        $shippingDiscount = 0;

        switch ($voucher->type) {
            case Voucher::TYPE_PERCENTAGE:
                $orderDiscount = $summary['subtotal'] * ($voucher->value / 100);
                break;
            case Voucher::TYPE_FIXED_AMOUNT:
                $orderDiscount = min($voucher->value, $summary['subtotal']);
                break;
            case Voucher::TYPE_FREE_SHIPPING:
                $shippingDiscount = $summary['shipping_fee'];
                break;
            case Voucher::TYPE_SHIPPING_PERCENTAGE:
                $shippingDiscount = $summary['shipping_fee'] * ($voucher->value / 100);
                break;
            case Voucher::TYPE_SHIPPING_FIXED:
                $shippingDiscount = min($voucher->value, $summary['shipping_fee']);
                break;
        }

        $totalDiscount = $orderDiscount + $shippingDiscount;
        if ($voucher->max_discount_amount && $totalDiscount > $voucher->max_discount_amount) {
            $excess = $totalDiscount - $voucher->max_discount_amount;

            if ($shippingDiscount >= $excess) {
                $shippingDiscount -= $excess;
            } else {
                $orderDiscount = max(0, $orderDiscount - ($excess - $shippingDiscount));
                $shippingDiscount = 0;
            }
            $totalDiscount = $voucher->max_discount_amount;
        }

        return [
            'order_discount' => round($orderDiscount, 2),
            'shipping_discount' => round($shippingDiscount, 2),
            'total_discount' => round($totalDiscount, 2),
        ];
    }

    protected function getVoucherValidationMessage(Voucher $voucher, float $orderAmount, int $userUsageCount): string
    {
        if ($voucher->status === Voucher::STATUS_DISABLED) {
            return 'Voucher đã bị vô hiệu hóa.';
        }

        if ($voucher->start_at && $voucher->start_at->isFuture()) {
            return 'Voucher chưa tới thời gian sử dụng.';
        }

        if ($voucher->end_at && $voucher->end_at->isPast()) {
            return 'Voucher đã hết hạn.';
        }

        if ($voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit) {
            return 'Voucher đã hết lượt sử dụng.';
        }

        if ($voucher->per_user_limit && $userUsageCount >= $voucher->per_user_limit) {
            return 'Bạn đã đạt giới hạn sử dụng voucher này.';
        }

        if ($voucher->min_order_amount && $orderAmount < $voucher->min_order_amount) {
            return 'Đơn hàng chưa đạt giá trị tối thiểu.';
        }

        return 'Voucher không khả dụng.';
    }

    public function forgetCache(string $code): void
    {
        Cache::forget('voucher:' . strtoupper($code));
    }
}
