<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VoucherAnalyticsService
{
    /**
     * Lấy tổng quan performance của tất cả vouchers
     */
    public function getOverallStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Order::whereNotNull('voucher_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalOrders = $query->count();
        $totalRevenue = $query->sum('final_price');
        $totalDiscount = $query->sum('voucher_discount');
        $uniqueCustomers = $query->distinct('account_id')->count('account_id');

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'total_discount' => (float) $totalDiscount,
            'unique_customers' => $uniqueCustomers,
            'average_order_value' => $totalOrders > 0 ? (float) ($totalRevenue / $totalOrders) : 0,
            'average_discount_per_order' => $totalOrders > 0 ? (float) ($totalDiscount / $totalOrders) : 0,
        ];
    }

    /**
     * Lấy performance chi tiết của một voucher
     */
    public function getVoucherPerformance(int $voucherId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $voucher = Voucher::findOrFail($voucherId);

        $query = Order::where('voucher_id', $voucherId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $orders = $query->get();
        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('final_price');
        $totalDiscount = $orders->sum('voucher_discount');
        $uniqueCustomers = $orders->pluck('account_id')->unique()->count();

        // Conversion rate: số đơn hàng / số lượt sử dụng voucher
        $conversionRate = $voucher->usage_count > 0 
            ? ($totalOrders / $voucher->usage_count) * 100 
            : 0;

        // Revenue impact: tổng doanh thu từ voucher
        $revenueImpact = $totalRevenue;

        // Customer acquisition cost: chi phí để có 1 khách hàng mới
        $newCustomers = $this->getNewCustomersCount($voucherId, $startDate, $endDate);
        $customerAcquisitionCost = $newCustomers > 0 
            ? ($totalDiscount / $newCustomers) 
            : 0;

        // ROI: (Revenue - Discount) / Discount * 100
        $roi = $totalDiscount > 0 
            ? (($totalRevenue - $totalDiscount) / $totalDiscount) * 100 
            : 0;

        return [
            'voucher' => $voucher,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'total_discount' => (float) $totalDiscount,
            'unique_customers' => $uniqueCustomers,
            'new_customers' => $newCustomers,
            'conversion_rate' => round($conversionRate, 2),
            'revenue_impact' => (float) $revenueImpact,
            'customer_acquisition_cost' => round($customerAcquisitionCost, 2),
            'roi' => round($roi, 2),
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0,
            'average_discount_per_order' => $totalOrders > 0 ? round($totalDiscount / $totalOrders, 2) : 0,
        ];
    }

    /**
     * Lấy danh sách top vouchers theo performance
     */
    public function getTopVouchers(string $metric = 'revenue', int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Order::select(
            'voucher_id',
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(final_price) as total_revenue'),
            DB::raw('SUM(voucher_discount) as total_discount'),
            DB::raw('COUNT(DISTINCT account_id) as unique_customers')
        )
        ->whereNotNull('voucher_id')
        ->groupBy('voucher_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $orderBy = match($metric) {
            'revenue' => 'total_revenue',
            'orders' => 'total_orders',
            'discount' => 'total_discount',
            'customers' => 'unique_customers',
            default => 'total_revenue',
        };

        $results = $query->orderByDesc($orderBy)->limit($limit)->get();

        $vouchers = [];
        foreach ($results as $result) {
            $voucher = Voucher::find($result->voucher_id);
            if (!$voucher) continue;

            $conversionRate = $voucher->usage_count > 0 
                ? ($result->total_orders / $voucher->usage_count) * 100 
                : 0;

            $roi = $result->total_discount > 0 
                ? ((float)$result->total_revenue - (float)$result->total_discount) / (float)$result->total_discount * 100 
                : 0;

            $vouchers[] = [
                'voucher' => $voucher,
                'total_orders' => $result->total_orders,
                'total_revenue' => (float) $result->total_revenue,
                'total_discount' => (float) $result->total_discount,
                'unique_customers' => $result->unique_customers,
                'conversion_rate' => round($conversionRate, 2),
                'roi' => round($roi, 2),
            ];
        }

        return $vouchers;
    }

    /**
     * Lấy revenue theo thời gian (daily/weekly/monthly)
     */
    public function getRevenueTrend(int $voucherId, string $period = 'daily', ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Order::where('voucher_id', $voucherId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $dateFormat = match($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $results = $query->select(
            DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
            DB::raw('COUNT(*) as orders'),
            DB::raw('SUM(final_price) as revenue'),
            DB::raw('SUM(voucher_discount) as discount')
        )
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        return $results->map(function ($item) {
            return [
                'period' => $item->period,
                'orders' => $item->orders,
                'revenue' => (float) $item->revenue,
                'discount' => (float) $item->discount,
            ];
        })->toArray();
    }

    /**
     * Lấy conversion rate theo voucher
     */
    public function getConversionRates(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $vouchers = Voucher::whereHas('orders', function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        })->get();

        $conversions = [];
        foreach ($vouchers as $voucher) {
            $orders = Order::where('voucher_id', $voucher->id);
            if ($startDate) {
                $orders->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $orders->where('created_at', '<=', $endDate);
            }
            $orderCount = $orders->count();

            $conversionRate = $voucher->usage_count > 0 
                ? ($orderCount / $voucher->usage_count) * 100 
                : 0;

            $conversions[] = [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->code,
                'voucher_name' => $voucher->name,
                'usage_count' => $voucher->usage_count,
                'order_count' => $orderCount,
                'conversion_rate' => round($conversionRate, 2),
            ];
        }

        return collect($conversions)->sortByDesc('conversion_rate')->values()->toArray();
    }

    /**
     * Tính số khách hàng mới từ voucher
     */
    private function getNewCustomersCount(int $voucherId, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $query = Order::where('voucher_id', $voucherId)
            ->whereNotNull('account_id');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Lấy danh sách account_id đã dùng voucher này
        $voucherCustomers = $query->pluck('account_id')->unique();

        // Đếm số khách hàng mà đơn hàng đầu tiên của họ là đơn dùng voucher này
        $newCustomers = 0;
        foreach ($voucherCustomers as $accountId) {
            $firstOrder = Order::where('account_id', $accountId)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstOrder && $firstOrder->voucher_id === $voucherId) {
                $newCustomers++;
            }
        }

        return $newCustomers;
    }

    /**
     * Lấy ROI tracking theo voucher
     */
    public function getROITracking(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $vouchers = Voucher::whereHas('orders', function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        })->get();

        $roiData = [];
        foreach ($vouchers as $voucher) {
            $orders = Order::where('voucher_id', $voucher->id);
            if ($startDate) {
                $orders->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $orders->where('created_at', '<=', $endDate);
            }

            $totalRevenue = $orders->sum('final_price');
            $totalDiscount = $orders->sum('voucher_discount');

            $roi = $totalDiscount > 0 
                ? (($totalRevenue - $totalDiscount) / $totalDiscount) * 100 
                : 0;

            $roiData[] = [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->code,
                'voucher_name' => $voucher->name,
                'total_revenue' => (float) $totalRevenue,
                'total_discount' => (float) $totalDiscount,
                'net_profit' => (float) ($totalRevenue - $totalDiscount),
                'roi' => round($roi, 2),
            ];
        }

        return collect($roiData)->sortByDesc('roi')->values()->toArray();
    }
}

