<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\ReelVisitorSession;
use App\Models\ReelWatchLog;
use App\Services\ReelAnalytics\ReelAnalyticsService;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReelAnalyticsAdminController extends Controller
{
    protected ReelAnalyticsService $analyticsService;
    protected ReelsAdminService $adminService;

    public function __construct(ReelAnalyticsService $analyticsService, ReelsAdminService $adminService)
    {
        $this->analyticsService = $analyticsService;
        $this->adminService = $adminService;
    }

    /**
     * Báo cáo phân tích chuyên sâu (Reels Analytics Overview)
     */
    public function index(Request $request): View
    {
        $days = (int) $request->input('days', 30);
        $overview = $this->analyticsService->getAdminAnalyticsOverview(['days' => $days]);

        $startDate = now()->subDays($days)->startOfDay();

        // 1. Phân bổ độ phân giải màn hình phổ biến nhất
        $screenResolutions = ReelVisitorSession::where('created_at', '>=', $startDate)
            ->whereNotNull('screen_resolution')
            ->select('screen_resolution', DB::raw('count(*) as total'))
            ->groupBy('screen_resolution')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        // 2. Thống kê nguồn chiến dịch tiếp thị (UTM Campaigns)
        $utmCampaigns = ReelVisitorSession::where('created_at', '>=', $startDate)
            ->whereNotNull('utm_source')
            ->select('utm_source', 'utm_medium', 'utm_campaign', DB::raw('count(*) as sessions_count'), DB::raw('round(avg(total_watch_time_seconds), 1) as avg_watch_time'))
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('sessions_count')
            ->take(8)
            ->get();

        // 3. Tỷ lệ tương tác chi tiết trong các lượt xem
        $interactionRates = ReelWatchLog::where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_logs,
                SUM(interacted_like) as total_likes,
                SUM(interacted_comment) as total_comments,
                SUM(interacted_share) as total_shares,
                SUM(interacted_save) as total_saves,
                SUM(interacted_product_click) as total_product_clicks
            ')->first();

        $totalLogs = $interactionRates->total_logs ?? 0;
        $likeRate = $totalLogs > 0 ? round(($interactionRates->total_likes / $totalLogs) * 100, 2) : 0;
        $commentRate = $totalLogs > 0 ? round(($interactionRates->total_comments / $totalLogs) * 100, 2) : 0;
        $shareRate = $totalLogs > 0 ? round(($interactionRates->total_shares / $totalLogs) * 100, 2) : 0;
        $saveRate = $totalLogs > 0 ? round(($interactionRates->total_saves / $totalLogs) * 100, 2) : 0;
        $productClickRate = $totalLogs > 0 ? round(($interactionRates->total_product_clicks / $totalLogs) * 100, 2) : 0;

        return view('admins.reels.analytics.index', compact(
            'days',
            'overview',
            'screenResolutions',
            'utmCampaigns',
            'interactionRates',
            'likeRate',
            'commentRate',
            'shareRate',
            'saveRate',
            'productClickRate'
        ));
    }

    /**
     * Bảng nhật ký phiên khách truy cập (Visitor Sessions)
     */
    public function visitors(Request $request): View
    {
        $query = ReelVisitorSession::with(['account', 'anonymousUser']);

        if ($request->filled('device') && $request->input('device') !== 'all') {
            $query->where('device_type', $request->input('device'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('browser', 'like', "%{$search}%")
                  ->orWhere('platform', 'like', "%{$search}%")
                  ->orWhere('utm_source', 'like', "%{$search}%");
            });
        }

        $sessions = $query->latest('last_activity_at')->paginate(20)->withQueryString();

        // Định dạng ẩn IP một phần vì lý do bảo mật quyền riêng tư
        $sessions->getCollection()->transform(function ($sess) {
            $sess->masked_ip = $this->maskIp($sess->ip_address);
            return $sess;
        });

        return view('admins.reels.analytics.visitors', compact('sessions'));
    }

    /**
     * Hàm ẩn danh hóa IP (vd: 113.190.234.12 -> 113.190.***.***)
     */
    protected function maskIp(?string $ip): string
    {
        if (empty($ip)) return 'N/A';
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.***.***';
        }
        return substr($ip, 0, 8) . '***';
    }
}

