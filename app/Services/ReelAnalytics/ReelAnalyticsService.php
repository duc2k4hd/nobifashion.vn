<?php

namespace App\Services\ReelAnalytics;

use App\Models\AnonymousUser;
use App\Models\Reel;
use App\Models\ReelVisitorSession;
use App\Models\ReelWatchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReelAnalyticsService
{
    /**
     * Ghi nhận hoặc cập nhật phiên truy cập của người dùng (kể cả khách vãng lai và đã đăng nhập)
     */
    public function recordSession(Request $request, ?AnonymousUser $anonUser = null, ?int $accountId = null): ReelVisitorSession
    {
        $sessionToken = trim((string) $request->input('session_token', ''));
        if (empty($sessionToken)) {
            $sessionToken = (string) Str::uuid();
        }

        $ipAddress = $request->ip() ?: '127.0.0.1';
        $userAgent = (string) $request->userAgent();
        $parsedUa = UserAgentParser::parse($userAgent);

        $session = ReelVisitorSession::where('session_token', $sessionToken)->first();

        if (!$session) {
            $session = new ReelVisitorSession();
            $session->session_token = $sessionToken;
            $session->first_activity_at = now();
            $session->landing_page = substr((string) ($request->input('landing_page') ?: $request->fullUrl()), 0, 255);
            $session->referrer_url = (string) ($request->input('referrer') ?: $request->header('referer'));
            $session->utm_source = substr((string) $request->input('utm_source'), 0, 50) ?: null;
            $session->utm_medium = substr((string) $request->input('utm_medium'), 0, 50) ?: null;
            $session->utm_campaign = substr((string) $request->input('utm_campaign'), 0, 50) ?: null;
        }

        // Cập nhật các thông tin hiện tại
        if ($anonUser) {
            $session->anonymous_user_id = $anonUser->id;
        }
        if ($accountId) {
            $session->account_id = $accountId;
        }

        $session->ip_address = $ipAddress;
        $session->user_agent = $userAgent;
        $session->device_type = $parsedUa['device_type'];
        $session->browser = $parsedUa['browser'];
        $session->platform = $parsedUa['platform'];

        if ($request->filled('screen_resolution')) {
            $session->screen_resolution = substr(trim((string) $request->input('screen_resolution')), 0, 25);
        }

        $session->last_activity_at = now();
        $session->save();

        return $session;
    }

    /**
     * Ghi nhận phân tích hành vi xem video (thời lượng xem, tỷ lệ hoàn thành, loop replay, tương tác)
     */
    public function recordWatch(Request $request, ?AnonymousUser $anonUser = null, ?int $accountId = null): ?ReelWatchLog
    {
        $reelId = (int) $request->input('reel_id');
        $sessionToken = trim((string) $request->input('session_token', ''));

        if (!$reelId || empty($sessionToken)) {
            return null;
        }

        $reel = Reel::find($reelId);
        if (!$reel) {
            return null;
        }

        // Bảo đảm session đã tồn tại
        $session = $this->recordSession($request, $anonUser, $accountId);

        $watchTimeSeconds = max(0, (float) $request->input('watch_time_seconds', 0));
        $videoDuration = max(0, (float) ($request->input('video_duration') ?: ($reel->duration ?: 0)));
        $completionRate = (float) $request->input('completion_rate', 0);
        $loopCount = max(0, (int) $request->input('loop_count', 0));
        $isCompleted = (bool) $request->input('is_completed', false);
        $isMuted = (bool) $request->input('is_muted', false);
        $playbackRate = max(0.25, min(3.0, (float) $request->input('playback_rate', 1.00)));

        if ($videoDuration > 0 && $completionRate <= 0) {
            $completionRate = min(100, round(($watchTimeSeconds / $videoDuration) * 100, 2));
        }

        if ($completionRate >= 95 || $loopCount > 0) {
            $isCompleted = true;
        }

        // Tìm hoặc tạo log xem cho phiên này và video này
        $watchLog = ReelWatchLog::where('session_token', $sessionToken)
            ->where('reel_id', $reelId)
            ->latest('id')
            ->first();

        $isNewLog = false;
        if (!$watchLog) {
            $watchLog = new ReelWatchLog();
            $watchLog->session_token = $sessionToken;
            $watchLog->reel_id = $reelId;
            $isNewLog = true;
        }

        $watchLog->account_id = $accountId ?: $session->account_id;
        $watchLog->anonymous_user_id = $anonUser ? $anonUser->id : $session->anonymous_user_id;
        $watchLog->watch_time_seconds = max((float) $watchLog->watch_time_seconds, $watchTimeSeconds);
        $watchLog->video_duration = $videoDuration;
        $watchLog->completion_rate = max((float) $watchLog->completion_rate, $completionRate);
        $watchLog->is_completed = $watchLog->is_completed || $isCompleted;
        $watchLog->loop_count = max((int) $watchLog->loop_count, $loopCount);
        $watchLog->is_muted = $isMuted;
        $watchLog->playback_rate = $playbackRate;

        // Cập nhật các tương tác
        if ($request->boolean('interacted_like')) $watchLog->interacted_like = true;
        if ($request->boolean('interacted_comment')) $watchLog->interacted_comment = true;
        if ($request->boolean('interacted_share')) $watchLog->interacted_share = true;
        if ($request->boolean('interacted_save')) $watchLog->interacted_save = true;
        if ($request->boolean('interacted_product_click')) $watchLog->interacted_product_click = true;

        $watchLog->ip_address = $session->ip_address;
        $watchLog->device_type = $session->device_type;
        $watchLog->save();

        // Cập nhật tổng số video và tổng thời gian xem của phiên
        if ($isNewLog) {
            $session->increment('total_reels_viewed');
        }
        $session->total_watch_time_seconds = (float) ReelWatchLog::where('session_token', $sessionToken)->sum('watch_time_seconds');
        $session->last_activity_at = now();
        $session->save();

        return $watchLog;
    }

    /**
     * Báo cáo thống kê tổng quan (Analytics Overview) phục vụ Trang Admin
     */
    public function getAdminAnalyticsOverview(array $filters = []): array
    {
        $days = (int) ($filters['days'] ?? 30);
        $startDate = now()->subDays($days)->startOfDay();

        // 1. Thống kê phiên truy cập
        $sessionQuery = ReelVisitorSession::where('created_at', '>=', $startDate);
        $totalSessions = (clone $sessionQuery)->count();
        $anonymousSessions = (clone $sessionQuery)->whereNull('account_id')->count();
        $authenticatedSessions = (clone $sessionQuery)->whereNotNull('account_id')->count();
        $avgWatchTimePerSession = (clone $sessionQuery)->avg('total_watch_time_seconds') ?: 0;
        $avgReelsPerSession = (clone $sessionQuery)->avg('total_reels_viewed') ?: 0;

        // 2. Phân bố thiết bị
        $deviceBreakdown = (clone $sessionQuery)
            ->select('device_type', DB::raw('count(*) as total'))
            ->groupBy('device_type')
            ->pluck('total', 'device_type')
            ->toArray();

        // 3. Phân bố trình duyệt & hệ điều hành
        $browserBreakdown = (clone $sessionQuery)
            ->select('browser', DB::raw('count(*) as total'))
            ->groupBy('browser')
            ->orderByDesc('total')
            ->take(5)
            ->pluck('total', 'browser')
            ->toArray();

        $platformBreakdown = (clone $sessionQuery)
            ->select('platform', DB::raw('count(*) as total'))
            ->groupBy('platform')
            ->orderByDesc('total')
            ->take(5)
            ->pluck('total', 'platform')
            ->toArray();

        // 4. Thống kê hành vi xem video
        $watchQuery = ReelWatchLog::where('created_at', '>=', $startDate);
        $totalWatches = (clone $watchQuery)->count();
        $completedWatches = (clone $watchQuery)->where('is_completed', true)->count();
        $totalLoopReplays = (clone $watchQuery)->sum('loop_count');
        $avgWatchTimeSeconds = (clone $watchQuery)->avg('watch_time_seconds') ?: 0;
        $avgCompletionRate = (clone $watchQuery)->avg('completion_rate') ?: 0;

        // 5. Top 5 video được xem lâu nhất & giữ chân người xem tốt nhất
        $topEngagingReels = ReelWatchLog::where('reel_watch_logs.created_at', '>=', $startDate)
            ->join('reels', 'reels.id', '=', 'reel_watch_logs.reel_id')
            ->select([
                'reels.id',
                'reels.title',
                'reels.caption',
                'reels.poster_url',
                DB::raw('count(*) as total_views'),
                DB::raw('round(avg(reel_watch_logs.watch_time_seconds), 1) as avg_watch_time'),
                DB::raw('round(avg(reel_watch_logs.completion_rate), 1) as avg_completion_rate'),
                DB::raw('sum(reel_watch_logs.loop_count) as total_loops'),
            ])
            ->groupBy('reels.id', 'reels.title', 'reels.caption', 'reels.poster_url')
            ->orderByDesc('avg_watch_time')
            ->take(10)
            ->get();

        return [
            'period_days' => $days,
            'summary' => [
                'total_sessions' => $totalSessions,
                'anonymous_sessions' => $anonymousSessions,
                'authenticated_sessions' => $authenticatedSessions,
                'total_watches' => $totalWatches,
                'completed_watches' => $completedWatches,
                'completion_ratio' => $totalWatches > 0 ? round(($completedWatches / $totalWatches) * 100, 1) : 0,
                'total_loop_replays' => $totalLoopReplays,
                'avg_watch_time_seconds' => round($avgWatchTimeSeconds, 1),
                'avg_completion_rate' => round($avgCompletionRate, 1),
                'avg_watch_time_per_session' => round($avgWatchTimePerSession, 1),
                'avg_reels_per_session' => round($avgReelsPerSession, 1),
            ],
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'platform_breakdown' => $platformBreakdown,
            'top_engaging_reels' => $topEngagingReels,
        ];
    }
}

