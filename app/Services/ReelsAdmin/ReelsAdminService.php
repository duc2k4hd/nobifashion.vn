<?php

namespace App\Services\ReelsAdmin;

use App\Models\AccountLog;
use App\Models\AnonymousUser;
use App\Models\CommentModerationLog;
use App\Models\CommentModerationRule;
use App\Models\CommentSticker;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Models\ReelRankingSnapshot;
use App\Models\ReelVisitorSession;
use App\Models\ReelWatchLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReelsAdminService
{
    /**
     * Lấy KPI tổng quan hệ thống Reels (có cache 60s để tránh query nặng)
     */
    public function getDashboardKPIs(bool $forceRefresh = false): array
    {
        $cacheKey = 'reels_admin_dashboard_kpis_v1';
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 60, function () {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            $sevenDaysAgo = $now->copy()->subDays(7)->startOfDay();

            // Video counts
            $totalVideos = Reel::withTrashed()->count();
            $publishedVideos = Reel::where('status', 'published')->count();
            $draftVideos = Reel::where('status', 'draft')->count();
            $hiddenVideos = Reel::where('status', 'hidden')->count();
            $trashedVideos = Reel::onlyTrashed()->count();
            $newVideosToday = Reel::where('created_at', '>=', $today)->count();
            $newVideos7Days = Reel::where('created_at', '>=', $sevenDaysAgo)->count();

            // Aggregated counters
            $aggregates = Reel::selectRaw('
                COALESCE(SUM(view_count), 0) as total_views,
                COALESCE(SUM(like_count), 0) as total_likes,
                COALESCE(SUM(comment_count), 0) as total_comments,
                COALESCE(SUM(share_count), 0) as total_shares,
                COALESCE(SUM(save_count), 0) as total_saves
            ')->first();

            // Watch & Session Analytics
            $totalWatchTime = (float) ReelWatchLog::sum('watch_time_seconds');
            $totalSessions = ReelVisitorSession::count();
            $uniqueAnonymousVisitors = AnonymousUser::count();
            $bannedAnonymousUsers = AnonymousUser::where('is_banned', true)->orWhere('status', 'banned')->count();

            // Moderation & Comments
            $pendingComments = ReelComment::where('status', 'pending')->count();
            $blockedCommentsCount = CommentModerationLog::where('moderation_result', 'blocked')->count();

            return [
                'total_videos' => $totalVideos,
                'published_videos' => $publishedVideos,
                'draft_videos' => $draftVideos,
                'hidden_videos' => $hiddenVideos,
                'trashed_videos' => $trashedVideos,
                'new_videos_today' => $newVideosToday,
                'new_videos_7days' => $newVideos7Days,
                'total_views' => (int) ($aggregates->total_views ?? 0),
                'total_likes' => (int) ($aggregates->total_likes ?? 0),
                'total_comments' => (int) ($aggregates->total_comments ?? 0),
                'total_shares' => (int) ($aggregates->total_shares ?? 0),
                'total_saves' => (int) ($aggregates->total_saves ?? 0),
                'total_watch_time_seconds' => round($totalWatchTime, 1),
                'total_watch_time_hours' => round($totalWatchTime / 3600, 2),
                'total_sessions' => $totalSessions,
                'unique_anonymous_visitors' => $uniqueAnonymousVisitors,
                'banned_anonymous_users' => $bannedAnonymousUsers,
                'pending_comments' => $pendingComments,
                'blocked_comments' => $blockedCommentsCount,
            ];
        });
    }

    /**
     * Lấy dữ liệu biểu đồ theo khoảng thời gian
     */
    public function getChartData(string $period = '7days', ?string $from = null, ?string $to = null): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $groupByFormat = '%Y-%m-%d %H:00:00';
                $periodLabelFormat = 'H:00';
                break;
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                $groupByFormat = '%Y-%m-%d %H:00:00';
                $periodLabelFormat = 'H:00';
                break;
            case '30days':
                $startDate = $now->copy()->subDays(30)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $groupByFormat = '%Y-%m-%d';
                $periodLabelFormat = 'd/m';
                break;
            case '90days':
                $startDate = $now->copy()->subDays(90)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $groupByFormat = '%Y-%m-%d';
                $periodLabelFormat = 'd/m';
                break;
            case 'custom':
                $startDate = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->subDays(7)->startOfDay();
                $endDate = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();
                $diffDays = $startDate->diffInDays($endDate);
                $groupByFormat = $diffDays <= 2 ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
                $periodLabelFormat = $diffDays <= 2 ? 'H:00' : 'd/m';
                break;
            case '7days':
            default:
                $startDate = $now->copy()->subDays(7)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $groupByFormat = '%Y-%m-%d';
                $periodLabelFormat = 'd/m';
                break;
        }

        // 1. Biểu đồ nhật ký xem video (Watches & Watch Time)
        $watchStats = ReelWatchLog::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$groupByFormat}') as period, COUNT(*) as total_watches, ROUND(SUM(watch_time_seconds), 1) as total_watch_time, SUM(is_completed) as completed_watches")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // 2. Biểu đồ bình luận
        $commentStats = ReelComment::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$groupByFormat}') as period, COUNT(*) as total_comments")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Tạo chuỗi nhãn thời gian liên tục
        $labels = [];
        $watchesData = [];
        $watchTimeData = [];
        $commentsData = [];
        $completedData = [];

        $current = $startDate->copy();
        $isHourly = str_contains($groupByFormat, '%H');

        while ($current->lte($endDate)) {
            $key = $current->format($isHourly ? 'Y-m-d H:00:00' : 'Y-m-d');
            $label = $current->format($periodLabelFormat);

            $labels[] = $label;
            $w = $watchStats->get($key);
            $c = $commentStats->get($key);

            $watchesData[] = $w ? (int) $w->total_watches : 0;
            $watchTimeData[] = $w ? (float) $w->total_watch_time : 0;
            $completedData[] = $w ? (int) $w->completed_watches : 0;
            $commentsData[] = $c ? (int) $c->total_comments : 0;

            if ($isHourly) {
                $current->addHour();
            } else {
                $current->addDay();
            }
        }

        // 3. Phân bố thiết bị
        $devices = ReelVisitorSession::whereBetween('created_at', [$startDate, $endDate])
            ->select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        // 4. Top 5 video tương tác cao nhất
        $topReels = Reel::where('status', 'published')
            ->orderByDesc('view_count')
            ->take(5)
            ->get(['id', 'title', 'view_count', 'like_count', 'comment_count', 'poster_url', 'thumbnail_url']);

        return [
            'labels' => $labels,
            'watches' => $watchesData,
            'watch_time' => $watchTimeData,
            'completed' => $completedData,
            'comments' => $commentsData,
            'devices' => $devices,
            'top_reels' => $topReels,
        ];
    }

    /**
     * Kiểm tra trạng thái sức khỏe hệ thống liên quan đến Reels
     */
    public function getSystemHealth(): array
    {
        $health = [
            'database' => ['status' => 'ok', 'message' => 'Kết nối MySQL hoạt động bình thường'],
            'redis' => ['status' => 'not_configured', 'message' => 'Redis không cấu hình hoặc không có extension'],
            'snapshot' => ['status' => 'ok', 'message' => 'Chưa có snapshot'],
            'moderation' => ['status' => 'ok', 'message' => 'Hệ thống kiểm duyệt sẵn sàng'],
        ];

        // 1. Database check
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $health['database'] = ['status' => 'error', 'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()];
        }

        // 2. Redis check
        try {
            if (extension_loaded('redis')) {
                \Illuminate\Support\Facades\Redis::ping();
                $health['redis'] = ['status' => 'ok', 'message' => 'Kết nối Redis hoạt động tốt'];
            }
        } catch (\Throwable $e) {
            $health['redis'] = ['status' => 'warning', 'message' => 'Không kết nối được Redis: ' . $e->getMessage()];
        }

        // 3. Snapshot check
        try {
            $latestSnapshot = ReelRankingSnapshot::where('status', 'active')->latest('version')->first();
            if ($latestSnapshot) {
                $isExpired = $latestSnapshot->expires_at && $latestSnapshot->expires_at->isPast();
                $health['snapshot'] = [
                    'status' => $isExpired ? 'warning' : 'ok',
                    'message' => "Snapshot v{$latestSnapshot->version} ({$latestSnapshot->total_videos} video)" . ($isExpired ? ' - Đã hết hạn' : ' - Hoạt động'),
                    'version' => $latestSnapshot->version,
                    'total_videos' => $latestSnapshot->total_videos,
                ];
            } else {
                $health['snapshot'] = ['status' => 'warning', 'message' => 'Chưa có Snapshot xếp hạng nào được tạo'];
            }
        } catch (\Throwable $e) {
            $health['snapshot'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // 4. Moderation rules count
        try {
            $activeRulesCount = CommentModerationRule::where('is_active', true)->count();
            $health['moderation'] = [
                'status' => 'ok',
                'message' => "Đang áp dụng {$activeRulesCount} quy tắc kiểm duyệt nội dung",
            ];
        } catch (\Throwable $e) {
            $health['moderation'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $health;
    }

    /**
     * Ghi nhật ký quản trị vào account_logs
     */
    public function logAudit(string $type, array $payload, ?int $adminId = null): void
    {
        try {
            $adminId = $adminId ?: (auth('web')->id() ?: auth('account')->id());
            AccountLog::create([
                'account_id' => $adminId ?: 1,
                'admin_id' => $adminId,
                'type' => 'reels_admin_' . $type,
                'payload' => $payload,
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Tránh crash luồng chính nếu ghi log thất bại
        }
    }
}

