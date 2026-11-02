<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnonymousUser;
use App\Models\CommentSticker;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Services\AnonymousIdentityService;
use App\Services\ReelAnalytics\ReelAnalyticsService;
use App\Services\ReelService;
use App\Services\ReelsFeed\ReelsFeedOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReelApiController extends Controller
{
    public function __construct(
        protected ReelService $reelService,
        protected AnonymousIdentityService $identityService,
        protected ReelsFeedOrchestrator $orchestrator,
        protected ReelAnalyticsService $analyticsService
    ) {}

    /**
     * Lấy thông tin Anonymous Identity hiện tại
     */
    public function me(Request $request): JsonResponse
    {
        $newRawToken = null;
        $anonUser = $this->identityService->resolveUser($request, $newRawToken);

        $response = response()->json([
            'success' => true,
            'data' => $anonUser->toPublicArray(),
        ]);

        if ($newRawToken) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Reels Feed Recommendation & Distribution Engine
     * Dual-Engine (Trending + Newest), Seeded Random & Full Video Coverage
     */
    public function feed(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $this->identityService->resolveUser($request);

        $limit = min((int) ($request->get('limit', 10)), 20);
        $cursor = $request->get('cursor');

        $batch = $this->orchestrator->getFeedBatch($anonUser, $userId, $clientIp, $limit, $cursor);

        return response()->json([
            'success' => true,
            'data' => $batch['items'],
            'pagination' => [
                'has_more' => $batch['hasMore'],
                'next_cursor' => $batch['nextCursor'],
                'limit' => $limit,
                'cycle_id' => $batch['cycleId'] ?? null,
                'snapshot_version' => $batch['snapshotVersion'] ?? 1,
                'total_videos' => $batch['totalVideos'] ?? 0,
                'current_index' => $batch['currentIndex'] ?? 0,
            ]
        ]);
    }

    /**
     * Lấy danh sách Reels theo phân trang Cursor
     * Tự động điều hướng sang Dual Engine nếu là feed chính (all)
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $this->identityService->resolveUser($request);

        $category = $request->get('category', 'all');
        $search = trim((string) $request->get('search', ''));

        // Feed mặc định ('all' và không có search): Dùng ReelsFeedOrchestrator
        if (($category === 'all' || empty($category)) && empty($search)) {
            $limit = min((int) ($request->get('limit', 10)), 20);
            $cursor = $request->get('cursor');

            $batch = $this->orchestrator->getFeedBatch($anonUser, $userId, $clientIp, $limit, $cursor);

            return response()->json([
                'success' => true,
                'data' => $batch['items'],
                'pagination' => [
                    'has_more' => $batch['hasMore'],
                    'next_cursor' => $batch['nextCursor'],
                    'limit' => $limit,
                    'cycle_id' => $batch['cycleId'] ?? null,
                    'snapshot_version' => $batch['snapshotVersion'] ?? 1,
                    'total_videos' => $batch['totalVideos'] ?? 0,
                    'current_index' => $batch['currentIndex'] ?? 0,
                ]
            ]);
        }

        // Nếu có bộ lọc category cụ thể hoặc search: Dùng ReelService
        $result = $this->reelService->getFeed($request->all(), $clientIp, $userId, $anonUser);

        return response()->json($result);
    }

    /**
     * Khởi tạo hoặc cập nhật phiên người dùng (IP, User-Agent, Device, Browser, OS, Screen, Referrer)
     */
    public function trackSession(Request $request): JsonResponse
    {
        $accountId = auth()->id();
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);

        $session = $this->analyticsService->recordSession($request, $anonUser, $accountId);

        return response()->json([
            'success' => true,
            'session_token' => $session->session_token,
            'device_type' => $session->device_type,
            'browser' => $session->browser,
            'platform' => $session->platform,
        ]);
    }

    /**
     * Ghi nhận phân tích hành vi xem video (thời lượng xem, % hoàn thành, số lần loop replay, tương tác)
     */
    public function trackWatch(Request $request): JsonResponse
    {
        $accountId = auth()->id();
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);

        $watchLog = $this->analyticsService->recordWatch($request, $anonUser, $accountId);

        return response()->json([
            'success' => $watchLog !== null,
            'watch_log_id' => $watchLog?->id,
        ]);
    }

    /**
     * Báo cáo thống kê tổng quan phân tích video phục vụ Admin
     */
    public function analyticsOverview(Request $request): JsonResponse
    {
        $overview = $this->analyticsService->getAdminAnalyticsOverview($request->all());

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Chi tiết 1 video Reel theo ID
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $reel = Reel::published()->with(['products.primaryImage'])->find($id);

        if (!$reel) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy video Reel!',
            ], 404);
        }

        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $this->identityService->resolveUser($request);

        $feedItem = $reel->toFeedArray($clientIp, $userId);
        $feedItem['isLiked'] = $this->reelService->hasLiked($reel->id, $clientIp, $userId, $anonUser);
        $feedItem['isSaved'] = $this->reelService->hasSaved($reel->id, $clientIp, $userId);

        return response()->json([
            'success' => true,
            'data' => $feedItem,
        ]);
    }

    /**
     * Chi tiết 1 video Reel theo Slug
     */
    public function showBySlug(string $slug, Request $request): JsonResponse
    {
        $reel = Reel::published()->where('slug', $slug)->with(['products.primaryImage'])->first();

        if (!$reel) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy video Reel!',
            ], 404);
        }

        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $this->identityService->resolveUser($request);

        $feedItem = $reel->toFeedArray($clientIp, $userId);
        $feedItem['isLiked'] = $this->reelService->hasLiked($reel->id, $clientIp, $userId, $anonUser);
        $feedItem['isSaved'] = $this->reelService->hasSaved($reel->id, $clientIp, $userId);

        return response()->json([
            'success' => true,
            'data' => $feedItem,
        ]);
    }

    /**
     * Ghi nhận lượt xem video (view) và cập nhật tiến độ xem của người dùng
     */
    public function view(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);
        $counted = $this->reelService->recordView($reel, $request);

        // Cập nhật tiến độ xem video trong ReelFeedCycle của người dùng để khi reload không bị lặp lại video đã xem
        if ($anonUser) {
            $cycle = \App\Models\ReelFeedCycle::active()
                ->where('anonymous_user_id', $anonUser->id)
                ->latest('id')
                ->first();

            if ($cycle && is_array($cycle->sequence)) {
                $pos = array_search($reel->id, $cycle->sequence);
                if ($pos !== false) {
                    $nextPos = $pos + 1;
                    if ($nextPos > (int) $cycle->current_index) {
                        $cycle->current_index = $nextPos;
                        $cycle->total_delivered = max((int) $cycle->total_delivered, $nextPos);
                        if ($cycle->current_index >= count($cycle->sequence)) {
                            $cycle->status = 'completed';
                            $cycle->completed_at = now();
                        }
                        $cycle->save();
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'viewCounted' => $counted,
            'views' => (int) $reel->fresh()->view_count,
        ]);
    }

    /**
     * Lấy trạng thái Like của Reel cho anonymous user
     */
    public function getLikeStatus(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);

        return response()->json([
            'success' => true,
            'isLiked' => $this->reelService->hasLiked($reel->id, $clientIp, $userId, $anonUser),
            'likesCount' => (int) $reel->like_count,
        ]);
    }

    /**
     * Thả tim Reel (PUT /api/reels/{id}/like) - Idempotent
     */
    public function like(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $userId = auth()->id();
        $clientIp = $request->ip();
        $newRawToken = null;
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request, $newRawToken);

        $result = $this->reelService->likeReel($reel, $anonUser, $clientIp, $userId);

        $response = response()->json([
            'success' => true,
            'isLiked' => $result['isLiked'],
            'likesCount' => $result['likesCount'],
        ]);

        if ($newRawToken) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Bỏ tim Reel (DELETE /api/reels/{id}/like) - Idempotent
     */
    public function unlike(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);
        $result = $this->reelService->unlikeReel($reel, $anonUser);

        return response()->json([
            'success' => true,
            'isLiked' => $result['isLiked'],
            'likesCount' => $result['likesCount'],
        ]);
    }

    /**
     * Toggle Save / Bookmark
     */
    public function save(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $userId = auth()->id();
        $clientIp = $request->ip();

        $result = $this->reelService->toggleSave($reel, $clientIp, $userId);

        return response()->json([
            'success' => true,
            'isSaved' => $result['isSaved'],
            'savesCount' => $result['savesCount'],
        ]);
    }

    /**
     * Tăng lượt chia sẻ
     */
    public function share(int $id): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $sharesCount = $this->reelService->recordShare($reel);

        return response()->json([
            'success' => true,
            'sharesCount' => $sharesCount,
        ]);
    }

    /**
     * Lấy danh sách bình luận của Reel (Cursor Pagination)
     */
    public function getComments(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $newRawToken = null;
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request, $newRawToken);
        $sort = (string) $request->query('sort', 'popular');
        $page = max((int) $request->query('page', 1), 1);
        $limit = min((int) $request->query('limit', 20), 50);
        $offset = (int) $request->query('offset', ($page - 1) * $limit);

        // Truy vấn comment cha: Tải trước 20 comment cha và tối đa 10 comment con cho mỗi cha
        $query = ReelComment::where('reel_id', $reel->id)
            ->whereNull('parent_id')
            ->where('status', 'approved')
            ->with([
                'sticker',
                'anonymousUser',
                'replies' => function ($q) {
                    $q->where('status', 'approved')->orderBy('id', 'asc')->take(10);
                },
                'replies.sticker',
                'replies.anonymousUser',
            ]);

        // Sắp xếp: Mặc định Phù hợp nhất (Nhiều tim nhất -> Nhiều trả lời nhất -> Mới nhất)
        if ($sort === 'latest') {
            $query->orderByDesc('id');
        } else {
            $query->orderByDesc('likes_count')
                ->orderByDesc('replies_count')
                ->orderByDesc('id');
        }

        $comments = $query->skip($offset)
            ->take($limit + 1)
            ->get();

        $hasMore = $comments->count() > $limit;
        if ($hasMore) {
            $comments = $comments->slice(0, $limit);
        }

        $nextOffset = $offset + $comments->count();

        // Lấy danh sách comment ID mà anonymous user hiện tại đã like
        $likedCommentIds = [];
        if ($anonUser) {
            $allIds = $comments->pluck('id')->merge($comments->flatMap->replies->pluck('id'))->all();
            $likedCommentIds = \App\Models\ReelCommentLike::whereIn('comment_id', $allIds)
                ->where('anonymous_user_id', $anonUser->id)
                ->pluck('comment_id')
                ->all();
            $likedCommentIds = array_flip($likedCommentIds);
        }

        $data = $comments->map(function ($c) use ($likedCommentIds) {
            $anon = $c->anonymousUser;
            return [
                'id' => (string) $c->id,
                'author' => $c->author_name ?: ($anon?->nickname ?? 'Khách Nobi'),
                'nickname' => $anon?->nickname ?? $c->author_name,
                'avatar_key' => $anon?->avatar_key ?? 'cat',
                'avatar_color' => $anon?->avatar_color ?? '#3b82f6',
                'initials' => mb_substr($c->author_name, 0, 2),
                'time' => $c->created_at->diffForHumans(),
                'timestamp' => $c->created_at->timestamp,
                'text' => $c->content,
                'comment_type' => $c->comment_type,
                'sticker' => $c->sticker ? [
                    'id' => $c->sticker->id,
                    'code' => $c->sticker->code,
                    'name' => $c->sticker->name,
                    'asset_url' => $c->sticker->asset_url,
                ] : null,
                'likes' => (int) $c->likes_count,
                'isLiked' => isset($likedCommentIds[$c->id]),
                'replies_count' => (int) $c->replies_count,
                'replies' => $c->replies->map(function ($r) use ($likedCommentIds) {
                    $rAnon = $r->anonymousUser;
                    return [
                        'id' => (string) $r->id,
                        'author' => $r->author_name ?: ($rAnon?->nickname ?? 'Khách Nobi'),
                        'nickname' => $rAnon?->nickname ?? $r->author_name,
                        'avatar_key' => $rAnon?->avatar_key ?? 'cat',
                        'avatar_color' => $rAnon?->avatar_color ?? '#3b82f6',
                        'initials' => mb_substr($r->author_name, 0, 2),
                        'time' => $r->created_at->diffForHumans(),
                        'timestamp' => $r->created_at->timestamp,
                        'text' => $r->content,
                        'comment_type' => $r->comment_type,
                        'sticker' => $r->sticker ? [
                            'id' => $r->sticker->id,
                            'code' => $r->sticker->code,
                            'name' => $r->sticker->name,
                            'asset_url' => $r->sticker->asset_url,
                        ] : null,
                        'likes' => (int) $r->likes_count,
                        'isLiked' => isset($likedCommentIds[$r->id]),
                    ];
                })->values()->all(),
            ];
        });

        $response = response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'has_more' => $hasMore,
                'next_offset' => $nextOffset,
                'page' => $page,
                'limit' => $limit,
            ],
            'total' => (int) $reel->comment_count,
        ]);

        if ($newRawToken) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Tải thêm danh sách comment con (Replies) phân trang 10 mục/lần
     * GET /api/v1/reels/comments/{id}/replies
     */
    public function getCommentReplies(int $id, Request $request): JsonResponse
    {
        $parentComment = ReelComment::find($id);
        if (!$parentComment) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại!'], 404);
        }

        $newRawToken = null;
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request, $newRawToken);
        $page = max((int) $request->query('page', 1), 1);
        $limit = min((int) $request->query('limit', 10), 30);
        $offset = (int) $request->query('offset', ($page - 1) * $limit);
        $cursor = $request->query('after_id');

        $query = ReelComment::where('parent_id', $id)
            ->where('status', 'approved')
            ->with(['sticker', 'anonymousUser']);

        if ($cursor) {
            $query->where('id', '>', (int) $cursor)->orderBy('id', 'asc');
        } else {
            $query->orderBy('id', 'asc')->skip($offset);
        }

        $replies = $query->take($limit + 1)->get();
        $hasMore = $replies->count() > $limit;
        if ($hasMore) {
            $replies = $replies->slice(0, $limit);
        }

        $likedCommentIds = [];
        if ($anonUser && $replies->isNotEmpty()) {
            $likedCommentIds = \App\Models\ReelCommentLike::whereIn('comment_id', $replies->pluck('id'))
                ->where('anonymous_user_id', $anonUser->id)
                ->pluck('comment_id')
                ->all();
            $likedCommentIds = array_flip($likedCommentIds);
        }

        $data = $replies->map(function ($r) use ($likedCommentIds) {
            $rAnon = $r->anonymousUser;
            return [
                'id' => (string) $r->id,
                'author' => $r->author_name ?: ($rAnon?->nickname ?? 'Khách Nobi'),
                'nickname' => $rAnon?->nickname ?? $r->author_name,
                'avatar_key' => $rAnon?->avatar_key ?? 'cat',
                'avatar_color' => $rAnon?->avatar_color ?? '#3b82f6',
                'initials' => mb_substr($r->author_name, 0, 2),
                'time' => $r->created_at->diffForHumans(),
                'timestamp' => $r->created_at->timestamp,
                'text' => $r->content,
                'comment_type' => $r->comment_type,
                'sticker' => $r->sticker ? [
                    'id' => $r->sticker->id,
                    'code' => $r->sticker->code,
                    'name' => $r->sticker->name,
                    'asset_url' => $r->sticker->asset_url,
                ] : null,
                'likes' => (int) $r->likes_count,
                'isLiked' => isset($likedCommentIds[$r->id]),
            ];
        })->values()->all();

        $response = response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'has_more' => $hasMore,
                'next_offset' => $offset + $replies->count(),
                'total_replies' => (int) $parentComment->replies_count,
            ],
        ]);

        if ($newRawToken) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Gửi bình luận mới (Có kiểm duyệt nội dung, emoji, sticker, anti-spam)
     */
    public function postComment(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:1000',
            'comment_type' => 'nullable|string|in:TEXT,STICKER',
            'sticker_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
        ]);

        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false, 'message' => 'Video không tồn tại!'], 404);
        }

        $newRawToken = null;
        $anonUser = $this->identityService->resolveUser($request, $newRawToken);

        $content = (string) $request->input('content', '');
        $commentType = strtoupper((string) $request->input('comment_type', 'TEXT'));
        $stickerId = $request->input('sticker_id') ? (int) $request->input('sticker_id') : null;
        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $userId = auth()->id();
        $ip = (string) $request->ip();

        if ($commentType === 'TEXT' && trim($content) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung bình luận!',
            ], 422);
        }

        $result = $this->reelService->addComment(
            $reel,
            $anonUser,
            $content,
            $commentType,
            $stickerId,
            $parentId,
            $userId,
            $ip
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status_code'] ?? 422);
        }

        $comment = $result['data'];

        $responseData = [
            'id' => (string) $comment->id,
            'author' => $comment->author_name,
            'nickname' => $anonUser->nickname,
            'avatar_key' => $anonUser->avatar_key,
            'avatar_color' => $anonUser->avatar_color,
            'initials' => mb_substr($comment->author_name, 0, 2),
            'time' => 'Vừa xong',
            'timestamp' => now()->timestamp,
            'text' => $comment->content,
            'comment_type' => $comment->comment_type,
            'sticker' => $comment->sticker ? [
                'id' => $comment->sticker->id,
                'code' => $comment->sticker->code,
                'name' => $comment->sticker->name,
                'asset_url' => $comment->sticker->asset_url,
            ] : null,
            'likes' => 0,
            'isLiked' => false,
            'replies_count' => 0,
            'replies' => [],
        ];

        $response = response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $responseData,
            'is_pending_review' => $result['is_pending_review'],
            'commentCount' => (int) $reel->fresh()->comment_count,
        ], $result['status_code']);

        if ($newRawToken) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Thả tim bình luận (PUT /api/reels/comments/{id}/like)
     */
    public function likeComment(int $id, Request $request): JsonResponse
    {
        $comment = ReelComment::find($id);
        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại!'], 404);
        }

        $userId = auth()->id();
        $newRawToken = null;
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request, $newRawToken);

        $result = $this->reelService->likeComment($comment, $anonUser, $userId);

        $response = response()->json([
            'success' => true,
            'isLiked' => $result['isLiked'],
            'likesCount' => $result['likesCount'],
        ]);

        if ($newRawToken) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Bỏ tim bình luận (DELETE /api/reels/comments/{id}/like)
     */
    public function unlikeComment(int $id, Request $request): JsonResponse
    {
        $comment = ReelComment::find($id);
        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại!'], 404);
        }

        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);
        $result = $this->reelService->unlikeComment($comment, $anonUser);

        return response()->json([
            'success' => true,
            'isLiked' => $result['isLiked'],
            'likesCount' => $result['likesCount'],
        ]);
    }

    /**
     * Lấy danh sách stickers có sẵn của hệ thống
     */
    public function getStickers(): JsonResponse
    {
        $stickers = CommentSticker::active()->get();

        return response()->json([
            'success' => true,
            'data' => $stickers->map(function ($s) {
                return [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'category' => $s->category,
                    'asset_url' => asset($s->asset_url),
                ];
            }),
        ]);
    }

    /**
     * Lấy danh mục stickers
     */
    public function getStickerCategories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['id' => 'cute', 'name' => 'Đáng yêu'],
                ['id' => 'reaction', 'name' => 'Cảm xúc'],
                ['id' => 'fashion', 'name' => 'Thời trang'],
            ],
        ]);
    }
}
