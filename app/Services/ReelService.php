<?php

namespace App\Services;

use App\Models\AnonymousUser;
use App\Models\CommentSticker;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Models\ReelCommentLike;
use App\Models\ReelLike;
use App\Models\ReelSave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReelService
{
    public function __construct(
        protected ReelCommentModerationService $moderator
    ) {}

    /**
     * Lấy danh sách video Reels theo phân trang Cursor hiệu năng cao
     */
    public function getFeed(array $params, ?string $clientIp = null, ?int $userId = null, ?AnonymousUser $anonUser = null): array
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

        // Phân trang bằng Cursor
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

        // Bổ sung trạng thái isLiked và isSaved theo Anonymous User & IP
        $data = $items->map(function (Reel $reel) use ($clientIp, $userId, $anonUser) {
            $feedItem = $reel->toFeedArray($clientIp, $userId);

            $feedItem['isLiked'] = $this->hasLiked($reel->id, $clientIp, $userId, $anonUser);
            $feedItem['isSaved'] = $this->hasSaved($reel->id, $clientIp, $userId);

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
     * Thả tim Reel có tính Idempotent (Bắt buộc tối đa 1 Like/video)
     */
    public function likeReel(Reel $reel, AnonymousUser $anonUser, string $ip, ?int $userId = null): array
    {
        return DB::transaction(function () use ($reel, $anonUser, $ip, $userId) {
            $existing = ReelLike::where('reel_id', $reel->id)
                ->where('anonymous_user_id', $anonUser->id)
                ->lockForUpdate()
                ->first();

            if (!$existing) {
                ReelLike::create([
                    'reel_id' => $reel->id,
                    'anonymous_user_id' => $anonUser->id,
                    'user_id' => $userId,
                    'ip_address' => $ip,
                ]);
                $reel->increment('like_count');
            }

            return [
                'isLiked' => true,
                'likesCount' => (int) $reel->fresh()->like_count,
            ];
        });
    }

    /**
     * Bỏ tim Reel có tính Idempotent
     */
    public function unlikeReel(Reel $reel, AnonymousUser $anonUser): array
    {
        return DB::transaction(function () use ($reel, $anonUser) {
            $existing = ReelLike::where('reel_id', $reel->id)
                ->where('anonymous_user_id', $anonUser->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $reel->where('id', $reel->id)->where('like_count', '>', 0)->decrement('like_count');
            }

            return [
                'isLiked' => false,
                'likesCount' => (int) $reel->fresh()->like_count,
            ];
        });
    }

    /**
     * Thả tim hoặc Bỏ tim Bình luận (Comment Like)
     */
    public function likeComment(ReelComment $comment, AnonymousUser $anonUser, ?int $userId = null): array
    {
        return DB::transaction(function () use ($comment, $anonUser, $userId) {
            $existing = ReelCommentLike::where('comment_id', $comment->id)
                ->where('anonymous_user_id', $anonUser->id)
                ->lockForUpdate()
                ->first();

            if (!$existing) {
                ReelCommentLike::create([
                    'comment_id' => $comment->id,
                    'anonymous_user_id' => $anonUser->id,
                    'user_id' => $userId,
                ]);
                $comment->increment('likes_count');
            }

            return [
                'isLiked' => true,
                'likesCount' => (int) $comment->fresh()->likes_count,
            ];
        });
    }

    public function unlikeComment(ReelComment $comment, AnonymousUser $anonUser): array
    {
        return DB::transaction(function () use ($comment, $anonUser) {
            $existing = ReelCommentLike::where('comment_id', $comment->id)
                ->where('anonymous_user_id', $anonUser->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $comment->where('id', $comment->id)->where('likes_count', '>', 0)->decrement('likes_count');
            }

            return [
                'isLiked' => false,
                'likesCount' => (int) $comment->fresh()->likes_count,
            ];
        });
    }

    /**
     * Thêm bình luận cho Reel với kiểm duyệt đa tầng & chống Spam
     */
    public function addComment(
        Reel $reel,
        AnonymousUser $anonUser,
        string $content,
        string $commentType = 'TEXT',
        ?int $stickerId = null,
        ?int $parentId = null,
        ?int $userId = null,
        string $ip = ''
    ): array {
        // 1. Kiểm tra tài khoản ẩn danh có bị cấm không
        if ($anonUser->isCurrentlyBanned()) {
            return [
                'success' => false,
                'status_code' => 403,
                'message' => 'Tài khoản của bạn đang bị giới hạn tương tác. ' . ($anonUser->ban_reason ? "Lý do: {$anonUser->ban_reason}" : ''),
            ];
        }

        // 2. Chống spam bình luận (Tối đa 5 bình luận/phút & cooldown 3 giây)
        $rateKey = sprintf('reels:comment:rate:%d', $anonUser->id);
        $attempts = (int) Cache::get($rateKey, 0);
        if ($attempts >= 5) {
            return [
                'success' => false,
                'status_code' => 429,
                'message' => 'Bạn đang bình luận quá nhanh. Vui lòng đợi 1 phút trước khi thử lại.',
            ];
        }

        $cooldownKey = sprintf('reels:comment:cooldown:%d', $anonUser->id);
        if (Cache::has($cooldownKey)) {
            return [
                'success' => false,
                'status_code' => 429,
                'message' => 'Vui lòng chờ giây lát trước khi gửi bình luận tiếp theo.',
            ];
        }

        // 3. Kiểm tra nội dung trùng lặp liên tục
        $contentHash = md5($content . ($stickerId ?? 0));
        $dupKey = sprintf('reels:comment:dup:%d:%s', $anonUser->id, $contentHash);
        if (Cache::has($dupKey)) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Nội dung bình luận bị trùng lặp với bình luận vừa gửi.',
            ];
        }

        // 4. Nếu là STICKER, kiểm tra sticker có tồn tại trong hệ thống không
        $verifiedSticker = null;
        if ($commentType === 'STICKER') {
            if (!$stickerId) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Sticker không hợp lệ.',
                ];
            }
            $verifiedSticker = CommentSticker::active()->find($stickerId);
            if (!$verifiedSticker) {
                return [
                    'success' => false,
                    'status_code' => 422,
                    'message' => 'Sticker được chọn không tồn tại hoặc đã ngừng hỗ trợ.',
                ];
            }
            if ($content === '') {
                $content = $verifiedSticker->name;
            }
        }

        // 5. Kiểm duyệt nội dung qua Moderation Engine
        $moderation = $this->moderator->moderate($content, [
            'anonymous_user_id' => $anonUser->id,
            'ip' => $ip,
            'comment_type' => $commentType,
            'sticker_id' => $stickerId,
        ]);

        if ($moderation['decision'] === 'BLOCK') {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => $moderation['user_message'] ?? 'Nội dung bình luận chưa phù hợp quy tắc cộng đồng.',
            ];
        }

        $status = ($moderation['decision'] === 'REVIEW') ? 'pending_review' : 'approved';

        // 6. Xử lý phân cấp phản hồi (Thread Replies):
        // Nếu trả lời comment con/cháu/chắt, luôn tìm về Root Comment cao nhất để đưa vào cùng một luồng thảo luận
        $effectiveParentId = null;
        if ($parentId) {
            $targetComment = ReelComment::where('id', $parentId)->where('reel_id', $reel->id)->first();
            if ($targetComment) {
                $rootParent = $targetComment;
                while ($rootParent->parent_id !== null) {
                    $upParent = ReelComment::where('id', $rootParent->parent_id)->where('reel_id', $reel->id)->first();
                    if (!$upParent) break;
                    $rootParent = $upParent;
                }
                $effectiveParentId = $rootParent->id;

                // Nếu trả lời một bình luận con, tự động tag @tên người đó nếu chưa có
                if ($targetComment->id !== $rootParent->id) {
                    $targetAuthor = $targetComment->author_name ?: ($targetComment->anonymousUser?->nickname ?? 'bạn');
                    $tagPrefix = '@' . trim($targetAuthor);
                    if (!str_starts_with(trim($content ?? ''), '@')) {
                        $content = $tagPrefix . ' ' . trim($content ?? '');
                    }
                }
            }
        }

        // 7. Lưu vào Database an toàn
        $comment = ReelComment::create([
            'reel_id' => $reel->id,
            'anonymous_user_id' => $anonUser->id,
            'user_id' => $userId,
            'parent_id' => $effectiveParentId,
            'author_name' => $anonUser->nickname,
            'content' => e(strip_tags(trim($content))), // XSS prevention
            'comment_type' => $commentType,
            'sticker_id' => $verifiedSticker?->id,
            'status' => $status,
        ]);

        // Tăng đếm replies_count nếu là phản hồi
        if ($effectiveParentId && $status === 'approved') {
            ReelComment::where('id', $effectiveParentId)->increment('replies_count');
        }

        if ($status === 'approved') {
            $reel->increment('comment_count');
        }

        // Cập nhật Cache Rate Limits
        Cache::put($rateKey, $attempts + 1, now()->addMinute());
        Cache::put($cooldownKey, 1, now()->addSeconds(3));
        Cache::put($dupKey, 1, now()->addMinutes(2));

        return [
            'success' => true,
            'status_code' => 201,
            'data' => $comment->load(['sticker', 'anonymousUser']),
            'is_pending_review' => ($status === 'pending_review'),
            'message' => ($status === 'pending_review') 
                ? 'Bình luận của bạn đang được kiểm duyệt trước khi hiển thị.' 
                : 'Bình luận thành công!',
        ];
    }

    /**
     * Kiểm tra trạng thái đã Like Reel
     */
    public function hasLiked(int $reelId, ?string $ip, ?int $userId, ?AnonymousUser $anonUser = null): bool
    {
        $query = ReelLike::where('reel_id', $reelId);

        if ($anonUser) {
            return $query->where('anonymous_user_id', $anonUser->id)->exists();
        }

        if ($userId) {
            return $query->where('user_id', $userId)->exists();
        }

        if ($ip) {
            return $query->where('ip_address', $ip)->exists();
        }

        return false;
    }

    /**
     * Kiểm tra trạng thái đã Save Reel
     */
    public function hasSaved(int $reelId, ?string $ip, ?int $userId): bool
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

    /**
     * Ghi nhận và tăng số lượt chia sẻ của Reel
     */
    public function recordShare(Reel $reel): int
    {
        $reel->increment('share_count');
        return (int) $reel->fresh()->share_count;
    }

    /**
     * Toggle Lưu video (Save / Bookmark)
     */
    public function toggleSave(Reel $reel, ?string $ip, ?int $userId): array
    {
        $query = ReelSave::where('reel_id', $reel->id);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ip);
        }

        $existing = $query->first();

        if ($existing) {
            $existing->delete();
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
}
