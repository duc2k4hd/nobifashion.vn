<?php

namespace App\Services\ReelsFeed;

use App\Models\Reel;
use Illuminate\Support\Collection;

class TrendingRankingEngine
{
    /**
     * Trọng số có thể cấu hình
     */
    protected float $weightView = 0.35;
    protected float $weightLike = 0.40;
    protected float $weightComment = 0.25;

    /**
     * Tính toán danh sách Reels theo điểm số thịnh hành (Trending & Popularity)
     * Kết hợp 3 chiến lược: Most Viewed, Most Liked, Most Commented + Time Decay & Log Scaling
     *
     * @return array Danh sách Reel IDs đã được xếp hạng
     */
    public function generateTrendingIds(): array
    {
        $reels = Reel::query()
            ->published()
            ->select(['id', 'view_count', 'like_count', 'comment_count', 'published_at', 'created_at'])
            ->get();

        if ($reels->isEmpty()) {
            return [];
        }

        $now = now();

        // 1. Tính toán PopularityScore cho từng Reel
        $scored = $reels->map(function ($r) use ($now) {
            $views = max(0, (int) $r->view_count);
            $likes = max(0, (int) $r->like_count);
            $comments = max(0, (int) $r->comment_count);

            // Log scaling để cân bằng khoảng cách giữa triệu view và ngàn like/comment
            $viewScore = log(1 + $views);
            $likeScore = log(1 + $likes) * 2.2;
            $commentScore = log(1 + $comments) * 3.5;

            // Time decay: Video càng mới càng có độ tươi (Freshness Boost)
            $publishedAt = $r->published_at ?? $r->created_at ?? $now;
            $ageInDays = max(0, $now->diffInDays($publishedAt));
            $freshnessBoost = 1.0 / (1.0 + (0.05 * $ageInDays)); // Giảm dần nhẹ sau 30-60 ngày

            $popularityScore = (
                ($this->weightView * $viewScore) +
                ($this->weightLike * $likeScore) +
                ($this->weightComment * $commentScore)
            ) * $freshnessBoost;

            return [
                'id' => (int) $r->id,
                'popularity_score' => $popularityScore,
                'view_count' => $views,
                'like_count' => $likes,
                'comment_count' => $comments,
            ];
        });

        // 2. Tạo 3 chiến lược con:
        // A1: Most Viewed
        $mostViewed = $scored->sortByDesc('view_count')->pluck('id')->values()->all();
        // A2: Most Liked
        $mostLiked = $scored->sortByDesc('like_count')->pluck('id')->values()->all();
        // A3: Most Commented
        $mostCommented = $scored->sortByDesc('comment_count')->pluck('id')->values()->all();
        // Overall: Ranked by PopularityScore
        $overallPopular = $scored->sortByDesc('popularity_score')->pluck('id')->values()->all();

        // 3. Luân phiên xen kẽ các chiến lược con để tránh thiên lệch
        $result = [];
        $seen = [];
        $maxLen = count($overallPopular);

        for ($i = 0; $i < $maxLen; $i++) {
            // Thứ tự xen kẽ: Overall Score -> Most Liked -> Most Viewed -> Most Commented
            $candidates = [
                $overallPopular[$i] ?? null,
                $mostLiked[$i] ?? null,
                $mostViewed[$i] ?? null,
                $mostCommented[$i] ?? null,
            ];

            foreach ($candidates as $candidateId) {
                if ($candidateId !== null && !isset($seen[$candidateId])) {
                    $seen[$candidateId] = true;
                    $result[] = $candidateId;
                }
            }
        }

        // Bổ sung các video còn lại (nếu có) để đảm bảo không bị starvation
        foreach ($overallPopular as $id) {
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $result[] = $id;
            }
        }

        return $result;
    }
}

