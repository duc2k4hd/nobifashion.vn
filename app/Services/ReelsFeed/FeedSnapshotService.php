<?php

namespace App\Services\ReelsFeed;

use App\Models\Reel;
use App\Models\ReelRankingSnapshot;
use Illuminate\Support\Facades\Cache;

class FeedSnapshotService
{
    protected TrendingRankingEngine $trendingEngine;
    protected NewestRankingEngine $newestEngine;

    // Cache snapshot trong 60 phút
    protected int $snapshotTtlMinutes = 60;

    public function __construct(
        TrendingRankingEngine $trendingEngine,
        NewestRankingEngine $newestEngine
    ) {
        $this->trendingEngine = $trendingEngine;
        $this->newestEngine = $newestEngine;
    }

    /**
     * Lấy Snapshot xếp hạng hiện tại (tận dụng Cache hoặc sinh mới nếu chưa có/hết hạn)
     */
    public function getCurrentSnapshot(): ReelRankingSnapshot
    {
        $cacheKey = 'reels:feed:current_snapshot_data';

        $cached = Cache::get($cacheKey);
        if ($cached && isset($cached['id'])) {
            $snapshot = ReelRankingSnapshot::find($cached['id']);
            if ($snapshot && $snapshot->status === 'active' && (!$snapshot->expires_at || $snapshot->expires_at->isFuture())) {
                return $snapshot;
            }
        }

        // Tìm snapshot active mới nhất trong Database
        $latest = ReelRankingSnapshot::active()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('version')
            ->first();

        if ($latest) {
            Cache::put($cacheKey, ['id' => $latest->id, 'version' => $latest->version], now()->addMinutes(15));
            return $latest;
        }

        // Nếu chưa có hoặc đã hết hạn, tạo Snapshot mới
        return $this->generateNewSnapshot();
    }

    /**
     * Sinh Snapshot xếp hạng dùng chung mới
     */
    public function generateNewSnapshot(): ReelRankingSnapshot
    {
        $lastVersion = (int) ReelRankingSnapshot::max('version');
        $newVersion = $lastVersion + 1;

        // Sinh danh sách từ 2 Engine độc lập
        $trendingIds = $this->trendingEngine->generateTrendingIds();
        $newestIds = $this->newestEngine->generateNewestIds();

        // Tổng số video duy nhất đủ điều kiện
        $allIds = array_unique(array_merge($trendingIds, $newestIds));
        $totalVideos = count($allIds);

        // Đánh dấu các snapshot cũ thành expired
        ReelRankingSnapshot::active()->update(['status' => 'expired']);

        $snapshot = ReelRankingSnapshot::create([
            'version' => $newVersion,
            'total_videos' => $totalVideos,
            'trending_ids' => $trendingIds,
            'newest_ids' => $newestIds,
            'status' => 'active',
            'generated_at' => now(),
            'expires_at' => now()->addMinutes($this->snapshotTtlMinutes),
        ]);

        Cache::put('reels:feed:current_snapshot_data', [
            'id' => $snapshot->id,
            'version' => $snapshot->version
        ], now()->addMinutes(15));

        return $snapshot;
    }

    /**
     * Làm mới Snapshot khi có video mới xuất bản hoặc bị xóa
     */
    public function invalidateSnapshot(): void
    {
        Cache::forget('reels:feed:current_snapshot_data');
        $this->generateNewSnapshot();
    }
}

