<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommentUpdateRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::query()->with(['account', 'commentable']);

        // Filters
        if ($type = $request->get('commentable_type')) {
            $query->where('commentable_type', $type);
        }

        if ($status = $request->get('status')) {
            if ($status === 'approved') {
                $query->approved();
            } elseif ($status === 'pending') {
                $query->pending();
            }
        }

        if ($accountId = $request->get('account_id')) {
            $query->where('account_id', $accountId);
        }

        if ($keyword = $request->get('q')) {
            $query->where('content', 'like', '%' . $keyword . '%');
        }

        if ($commentableId = $request->get('commentable_id')) {
            $query->where('commentable_id', $commentableId);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        $comments = $query->latest()->paginate(20)->withQueryString();

        return view('admins.comments.index', compact('comments'));
    }

    public function show(Comment $comment)
    {
        $comment->load(['account', 'commentable', 'replies.account']);

        return view('admins.comments.show', compact('comment'));
    }

    public function update(CommentUpdateRequest $request, Comment $comment)
    {
        $comment->update($request->validated());

        return redirect()->route('admin.comments.show', $comment)
            ->with('success', 'Đã cập nhật bình luận.');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        return redirect()->route('admin.comments.index')
            ->with('success', 'Đã xoá bình luận.');
    }

    public function toggleApprove(Comment $comment)
    {
        $comment->is_approved = ! $comment->is_approved;
        $comment->save();

        return back()->with('success', $comment->is_approved ? 'Đã duyệt bình luận.' : 'Đã bỏ duyệt bình luận.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            Comment::whereIn('id', $ids)->delete();
        }

        return back()->with('success', 'Đã xoá các bình luận đã chọn.');
    }
}


