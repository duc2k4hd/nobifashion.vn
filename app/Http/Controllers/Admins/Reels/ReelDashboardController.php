<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\AccountLog;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelDashboardController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Hiển thị Dashboard trung tâm quản trị Reels
     */
    public function index(Request $request): View
    {
        $forceRefresh = $request->boolean('refresh');
        $kpis = $this->adminService->getDashboardKPIs($forceRefresh);
        $health = $this->adminService->getSystemHealth();

        // 10 hoạt động quản trị gần nhất
        $recentActivities = AccountLog::where('type', 'like', 'reels_admin_%')
            ->with(['account', 'admin'])
            ->latest('id')
            ->take(10)
            ->get();

        // Nếu chưa có nhiều account logs, lấy bổ sung các sự kiện thực tế từ CSDL
        $recentReels = Reel::latest('id')->take(5)->get();
        $recentComments = ReelComment::with(['reel', 'anonymousUser'])->latest('id')->take(5)->get();

        return view('admins.reels.dashboard.index', compact('kpis', 'health', 'recentActivities', 'recentReels', 'recentComments'));
    }

    /**
     * API trả dữ liệu biểu đồ realtime theo bộ lọc thời gian
     */
    public function chartData(Request $request): JsonResponse
    {
        $period = $request->input('period', '7days');
        $from = $request->input('from');
        $to = $request->input('to');

        $data = $this->adminService->getChartData($period, $from, $to);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

