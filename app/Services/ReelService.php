<?php

namespace App\Services;

use App\Models\Reel;
use App\Models\ReelComment;
use App\Models\ReelLike;
use App\Models\ReelSave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReelService
{
    /**
     * Lấy danh sách video Reels theo phân trang Cursor hiệu năng cao
     */
    public function getFeed(array $params, ?string $clientIp = null, ?int $userId = null): array
    {
        $limit = min((int) ($params['limit'] ?? 10), 20);
        $cursor = isset($params['cursor']) ? (int) $params['cursor'] : null;
        $category = $params['category'] ?? 'all';
        $search = trim((string) ($params['search'] ?? ''));

        $query = Reel::query()
            ->published()
            ->with([
                'brand:id,name,slug,logo',
                'products' => function ($q) {
                    $q->select(['products.id', 'products.name', 'products.slug', 'products.price', 'products.sale_price', 'products.short_description'])
                      ->with(['primaryImage:id,product_id,url,alt,title']);
                }
            ]);

        // Bộ lọc chuyên mục
        if ($category !== 'all' && $category !== '') {
            $query->where('category', $category);
        }

        // Tìm kiếm theo từ khóa
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('caption', 'like', "%{$search}%")
                  ->orWhereJsonContains('hashtags', '#' . ltrim($search, '#'));
            });
        }

        // Phân trang bằng Cursor (Sử dụng index [status, published_at, id])
        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $items = $query->orderByDesc('id')
            ->take($limit + 1)
            ->get();

        $hasMore = $items->count() > $limit;
        if ($hasMore) {
            $items = $items->slice(0, $limit);
        }

        $nextCursor = $items->isNotEmpty() ? $items->last()->id : null;

        // Bổ sung trạng thái isLiked và isSaved theo IP/User
        $data = $items->map(function (Reel $reel) use ($clientIp, $userId) {
            $feedItem = $reel->toFeedArray($clientIp, $userId);

            if ($clientIp || $userId) {
                $feedItem['isLiked'] = $this->hasLiked($reel->id, $clientIp, $userId);
                $feedItem['isSaved'] = $this->hasSaved($reel->id, $clientIp, $userId);
            }

            return $feedItem;
        })->values()->all();

        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'has_more' => $hasMore,
                'next_cursor' => $hasMore ? $nextCursor : null,
                'limit' => $limit,
            ]
        ];
    }

    /**
     * Tăng view an toàn: chống bot, chống cày view theo IP bằng Cache nguyên tử
     */
    public function recordView(Reel $reel, Request $request): bool
    {
        $userAgent = (string) $request->userAgent();
        if (trim($userAgent) === '' || strlen($userAgent) < 15) {
            return false;
        }

        // Loại bỏ bot crawler
        if (preg_match('/bot|crawl|spider|slurp|curl|wget|python|requests|axios|headless/i', $userAgent)) {
            return false;
        }

        $ip = (string) $request->ip();
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $cacheKey = sprintf('reel:view:%d:%s', $reel->id, md5($ip));

        // Atomic Cache Cooldown 30 phút cho mỗi cặp (reel_id, ip)
        if (Cache::add($cacheKey, 1, now()->addMinutes(30))) {
            $reel->increment('view_count');
            return true;
        }

        return false;
    }

    /**
     * Toggle Like cho Reel
     */
    public function toggleLike(Reel $reel, string $ip, ?int $userId = null): array
    {
        $query = ReelLike::where('reel_id', $reel->id);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ip);
        }

        $like = $query->first();

        if ($like) {
            $like->delete();
            $reel->where('id', $reel->id)->where('like_count', '>', 0)->decrement('like_count');
            $isLiked = false;
        } else {
            ReelLike::create([
                'reel_id' => $reel->id,
                'user_id' => $userId,
                'ip_address' => $ip,
            ]);
            $reel->increment('like_count');
            $isLiked = true;
        }

        return [
            'isLiked' => $isLiked,
            'likesCount' => (int) $reel->fresh()->like_count,
        ];
    }

    /**
     * Toggle Save / Bookmark cho Reel
     */
    public function toggleSave(Reel $reel, string $ip, ?int $userId = null): array
    {
        $query = ReelSave::where('reel_id', $reel->id);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ip);
        }

        $save = $query->first();

        if ($save) {
            $save->delete();
            $reel->where('id', $reel->id)->where('save_count', '>', 0)->decrement('save_count');
            $isSaved = false;
        } else {
            ReelSave::create([
                'reel_id' => $reel->id,
                'user_id' => $userId,
                'ip_address' => $ip,
            ]);
            $reel->increment('save_count');
            $isSaved = true;
        }

        return [
            'isSaved' => $isSaved,
            'savesCount' => (int) $reel->fresh()->save_count,
        ];
    }

    /**
     * Tăng số lượt chia sẻ
     */
    public function recordShare(Reel $reel): int
    {
        $reel->increment('share_count');
        return (int) $reel->fresh()->share_count;
    }

    /**
     * Thêm bình luận cho Reel
     */
    public function addComment(Reel $reel, string $authorName, string $content, ?int $parentId = null, ?int $userId = null): ReelComment
    {
        $comment = ReelComment::create([
            'reel_id' => $reel->id,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'author_name' => strip_tags(trim($authorName)),
            'content' => strip_tags(trim($content)),
            'status' => 'approved',
        ]);

        $reel->increment('comment_count');

        return $comment;
    }

    /**
     * Kiểm tra trạng thái đã Like
     */
    protected function hasLiked(int $reelId, ?string $ip, ?int $userId): bool
    {
        if (!$ip && !$userId) {
            return false;
        }

        $query = ReelLike::where('reel_id', $reelId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ip);
        }

        return $query->exists();
    }

    /**
     * Kiểm tra trạng thái đã Save
     */
    protected function hasSaved(int $reelId, ?string $ip, ?int $userId): bool
    {
        if (!$ip && !$userId) {
            return false;
        }

        $query = ReelSave::where('reel_id', $reelId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ip);
        }

        return $query->exists();
    }
}

