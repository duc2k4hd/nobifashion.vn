<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Services\ReelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReelApiController extends Controller
{
    public function __construct(
        protected ReelService $reelService
    ) {}

    /**
     * Lấy danh sách Reels theo phân trang Cursor
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $clientIp = $request->ip();

        $result = $this->reelService->getFeed($request->all(), $clientIp, $userId);

        return response()->json($result);
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

        return response()->json([
            'success' => true,
            'data' => $reel->toFeedArray($clientIp, $userId),
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

        return response()->json([
            'success' => true,
            'data' => $reel->toFeedArray($clientIp, $userId),
        ]);
    }

    /**
     * Ghi nhận lượt xem video (chống bot & atomic cooldown)
     */
    public function view(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $recorded = $this->reelService->recordView($reel, $request);

        return response()->json([
            'success' => true,
            'recorded' => $recorded,
            'viewCount' => (int) $reel->fresh()->view_count,
        ]);
    }

    /**
     * Toggle Like cho video
     */
    public function like(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $ip = $request->ip();
        $userId = auth()->id();

        $result = $this->reelService->toggleLike($reel, $ip, $userId);

        return response()->json([
            'success' => true,
            'isLiked' => $result['isLiked'],
            'likesCount' => $result['likesCount'],
        ]);
    }

    /**
     * Toggle Bookmark / Save cho video
     */
    public function save(int $id, Request $request): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $ip = $request->ip();
        $userId = auth()->id();

        $result = $this->reelService->toggleSave($reel, $ip, $userId);

        return response()->json([
            'success' => true,
            'isSaved' => $result['isSaved'],
            'savesCount' => $result['savesCount'],
        ]);
    }

    /**
     * Ghi nhận lượt Share
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
     * Lấy danh sách bình luận của Reel
     */
    public function getComments(int $id): JsonResponse
    {
        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $comments = $reel->comments()
            ->with(['replies'])
            ->take(50)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => (string) $c->id,
                    'author' => $c->author_name,
                    'initials' => mb_substr($c->author_name, 0, 2),
                    'time' => $c->created_at->diffForHumans(),
                    'timestamp' => $c->created_at->timestamp,
                    'text' => $c->content,
                    'likes' => (int) $c->likes_count,
                    'isLiked' => false,
                    'replies' => $c->replies->map(function ($r) {
                        return [
                            'id' => (string) $r->id,
                            'author' => $r->author_name,
                            'initials' => mb_substr($r->author_name, 0, 2),
                            'time' => $r->created_at->diffForHumans(),
                            'text' => $r->content,
                            'likes' => (int) $r->likes_count,
                            'isLiked' => false,
                        ];
                    })->values()->all(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * Gửi bình luận mới
     */
    public function postComment(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'author_name' => 'nullable|string|max:100',
            'parent_id' => 'nullable|integer',
        ]);

        $reel = Reel::find($id);
        if (!$reel) {
            return response()->json(['success' => false], 404);
        }

        $authorName = $request->input('author_name') ?: (auth()->user()?->name ?? 'Khách Nobi');
        $content = $request->input('content');
        $parentId = $request->input('parent_id');
        $userId = auth()->id();

        $comment = $this->reelService->addComment($reel, $authorName, $content, $parentId, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Bình luận thành công!',
            'data' => [
                'id' => (string) $comment->id,
                'author' => $comment->author_name,
                'initials' => mb_substr($comment->author_name, 0, 2),
                'time' => 'Vừa xong',
                'timestamp' => now()->timestamp,
                'text' => $comment->content,
                'likes' => 0,
                'isLiked' => false,
                'replies' => [],
            ],
            'commentCount' => (int) $reel->fresh()->comment_count,
        ]);
    }
}

