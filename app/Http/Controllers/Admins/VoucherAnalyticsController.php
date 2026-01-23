<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class VoucherAnalyticsController extends Controller
{
    public function __construct(private readonly VoucherAnalyticsService $analyticsService)
    {
    }

    /**
     * Dashboard tổng quan
     */
    public function dashboard(Request $request): View
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : Carbon::now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : Carbon::now();

        $overallStats = $this->analyticsService->getOverallStats($startDate, $endDate);
        $topVouchers = $this->analyticsService->getTopVouchers('revenue', 10, $startDate, $endDate);
        $conversionRates = $this->analyticsService->getConversionRates($startDate, $endDate);
        $roiTracking = $this->analyticsService->getROITracking($startDate, $endDate);

        return view('admins.vouchers.analytics.dashboard', [
            'overallStats' => $overallStats,
            'topVouchers' => $topVouchers,
            'conversionRates' => $conversionRates,
            'roiTracking' => $roiTracking,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * Chi tiết performance của một voucher
     */
    public function voucherDetail(int $voucherId, Request $request): View
    {
        $voucher = Voucher::findOrFail($voucherId);

        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : Carbon::now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : Carbon::now();

        $period = $request->input('period', 'daily');

        $performance = $this->analyticsService->getVoucherPerformance($voucherId, $startDate, $endDate);
        $revenueTrend = $this->analyticsService->getRevenueTrend($voucherId, $period, $startDate, $endDate);

        return view('admins.vouchers.analytics.detail', [
            'voucher' => $voucher,
            'performance' => $performance,
            'revenueTrend' => $revenueTrend,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'period' => $period,
        ]);
    }

    /**
     * API: Lấy revenue trend data cho chart
     */
    public function getRevenueTrendData(int $voucherId, Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : Carbon::now()->subDays(30);
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : Carbon::now();

        $period = $request->input('period', 'daily');

        $data = $this->analyticsService->getRevenueTrend($voucherId, $period, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Lấy overall stats
     */
    public function getOverallStats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : null;
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : null;

        $stats = $this->analyticsService->getOverallStats($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}

