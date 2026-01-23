<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommentRequest;
use App\Http\Requests\Api\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::query()
            ->with([
                'account:id,name,email',
                'replies.account:id,name,email',
                'replies.replies.account:id,name,email',
            ])
            ->approved()
            ->whereNull('parent_id')
            ->latest();

        if ($type = $request->get('commentable_type')) {
            $query->where('commentable_type', $type);
        }
        if ($id = $request->get('commentable_id')) {
            $query->where('commentable_id', $id);
        }

        $comments = $query->paginate(10);

        return CommentResource::collection($comments);
    }

    public function show(Comment $comment)
    {
        $comment->load(['account', 'replies.account']);

        return new CommentResource($comment);
    }

    public function store(StoreCommentRequest $request)
    {
        $user = $request->user() ?? $request->user('sanctum') ?? auth('web')->user();

        // Anti-spam: limit 1 comment / 5 seconds per IP
        $ip = $request->ip();
        $key = 'comment:' . sha1($ip);
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json([
                'message' => 'Bạn thao tác quá nhanh, vui lòng thử lại sau vài giây.',
            ], 429);
        }
        RateLimiter::hit($key, 5);

        $data = $request->validated();

        $comment = new Comment();
        $comment->fill($data);
        $comment->is_approved = false;
        if ($user) {
            $comment->account_id = $user->id;
        }
        $comment->ip_address = $ip;
        $comment->user_agent = (string) $request->userAgent();
        $comment->save();

        return new CommentResource($comment->fresh(['account', 'replies']));
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        $comment->update($request->validated());

        return new CommentResource($comment->fresh(['account', 'replies']));
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        return response()->json(['message' => 'Đã xoá bình luận.']);
    }

    public function approve(Comment $comment)
    {
        $comment->approve();

        return new CommentResource($comment);
    }

    public function report(Request $request, Comment $comment)
    {
        $ip = $request->ip();
        $key = sprintf('comment-report:%s:%s', $comment->id, sha1($ip));

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json([
                'message' => 'Bạn đã báo cáo bình luận này rồi. Cảm ơn bạn!',
            ], 429);
        }

        RateLimiter::hit($key, now()->addDays(365));

        $comment->increment('reports_count');
        $comment->is_reported = true;
        $comment->save();

        return response()->json([
            'message' => 'Đã ghi nhận báo cáo bình luận.',
            'reports_count' => $comment->reports_count,
        ]);
    }
}


