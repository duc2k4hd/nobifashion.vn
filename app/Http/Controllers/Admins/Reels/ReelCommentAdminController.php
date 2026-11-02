<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelCommentAdminController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Danh sách bình luận với bộ lọc & phân trang server-side
     */
    public function index(Request $request): View
    {
        $query = ReelComment::withTrashed()
            ->with(['reel', 'anonymousUser', 'user', 'sticker', 'parent']);

        // 1. Tìm kiếm nội dung hoặc tên người gửi
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        // 2. Lọc theo trạng thái
        $status = $request->input('status', 'all');
        if ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status !== 'all' && in_array($status, ['approved', 'pending', 'rejected', 'hidden'])) {
            $query->where('status', $status);
        }

        // 3. Lọc theo Reel cụ thể
        if ($request->filled('reel_id')) {
            $query->where('reel_id', (int) $request->input('reel_id'));
        }

        // 4. Lọc theo Anonymous User
        if ($request->filled('anonymous_user_id')) {
            $query->where('anonymous_user_id', (int) $request->input('anonymous_user_id'));
        }

        // 5. Lọc loại bình luận (TEXT / STICKER)
        if ($request->filled('type') && in_array($request->input('type'), ['TEXT', 'STICKER'])) {
            $query->where('comment_type', $request->input('type'));
        }

        // 6. Sắp xếp
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'likes_count', 'replies_count', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderByDesc('id');
        }

        $comments = $query->paginate(20)->withQueryString();

        // Đếm theo trạng thái
        $counts = [
            'all' => ReelComment::count(),
            'approved' => ReelComment::where('status', 'approved')->count(),
            'pending' => ReelComment::where('status', 'pending')->count(),
            'rejected' => ReelComment::where('status', 'rejected')->count(),
            'hidden' => ReelComment::where('status', 'hidden')->count(),
            'trashed' => ReelComment::onlyTrashed()->count(),
        ];

        return view('admins.reels.comments.index', compact('comments', 'counts'));
    }

    /**
     * Cập nhật trạng thái duyệt bình luận (approved, pending, rejected, hidden)
     */
    public function updateStatus(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $comment = ReelComment::withTrashed()->findOrFail($id);
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['approved', 'pending', 'rejected', 'hidden'])) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Trạng thái không hợp lệ.']);
            }
            return back()->with('error', 'Trạng thái không hợp lệ.');
        }

        $oldStatus = $comment->status;
        $comment->status = $newStatus;
        $comment->save();

        // Cập nhật lại bộ đếm bình luận của video nếu trạng thái thay đổi liên quan đến approved
        if ($oldStatus !== $newStatus) {
            $this->syncReelCommentCount($comment->reel_id);
            if ($comment->parent_id) {
                $this->syncParentReplyCount($comment->parent_id);
            }
        }

        $this->adminService->logAudit('update_comment_status', [
            'comment_id' => $id,
            'reel_id' => $comment->reel_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        $statusLabels = [
            'approved' => 'Đã duyệt',
            'pending' => 'Chờ duyệt',
            'rejected' => 'Từ chối',
            'hidden' => 'Đã ẩn',
        ];

        $msg = "Đã chuyển bình luận sang trạng thái: " . ($statusLabels[$newStatus] ?? $newStatus);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'status' => $newStatus]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Xóa mềm bình luận
     */
    public function destroy(int $id): RedirectResponse
    {
        $comment = ReelComment::findOrFail($id);
        $reelId = $comment->reel_id;
        $parentId = $comment->parent_id;

        $comment->delete();

        $this->syncReelCommentCount($reelId);
        if ($parentId) {
            $this->syncParentReplyCount($parentId);
        }

        $this->adminService->logAudit('soft_delete_comment', ['comment_id' => $id, 'reel_id' => $reelId]);

        return back()->with('success', 'Đã chuyển bình luận vào thùng rác.');
    }

    /**
     * Khôi phục bình luận
     */
    public function restore(int $id): RedirectResponse
    {
        $comment = ReelComment::onlyTrashed()->findOrFail($id);
        $comment->restore();

        $this->syncReelCommentCount($comment->reel_id);
        if ($comment->parent_id) {
            $this->syncParentReplyCount($comment->parent_id);
        }

        $this->adminService->logAudit('restore_comment', ['comment_id' => $id, 'reel_id' => $comment->reel_id]);

        return back()->with('success', 'Đã khôi phục bình luận thành công.');
    }

    /**
     * Thao tác hàng loạt trên bình luận (Bulk actions)
     */
    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $action = $request->input('action');
        $ids = (array) $request->input('ids', []);

        if (empty($ids)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Chưa chọn bình luận nào.']);
            }
            return back()->with('error', 'Vui lòng chọn ít nhất một bình luận.');
        }

        $count = count($ids);
        $affectedReelIds = ReelComment::withTrashed()->whereIn('id', $ids)->pluck('reel_id')->unique()->toArray();

        switch ($action) {
            case 'approve':
                ReelComment::whereIn('id', $ids)->update(['status' => 'approved']);
                $msg = "Đã duyệt {$count} bình luận thành công.";
                break;

            case 'reject':
                ReelComment::whereIn('id', $ids)->update(['status' => 'rejected']);
                $msg = "Đã từ chối {$count} bình luận.";
                break;

            case 'hide':
                ReelComment::whereIn('id', $ids)->update(['status' => 'hidden']);
                $msg = "Đã ẩn {$count} bình luận khỏi hiển thị.";
                break;

            case 'delete':
                ReelComment::whereIn('id', $ids)->delete();
                $msg = "Đã chuyển {$count} bình luận vào thùng rác.";
                break;

            case 'restore':
                ReelComment::onlyTrashed()->whereIn('id', $ids)->restore();
                $msg = "Đã khôi phục {$count} bình luận.";
                break;

            default:
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Hành động không hợp lệ.']);
                }
                return back()->with('error', 'Hành động không hợp lệ.');
        }

        // Đồng bộ lại comment count cho các reel bị ảnh hưởng
        foreach ($affectedReelIds as $rId) {
            $this->syncReelCommentCount($rId);
        }

        $this->adminService->logAudit('bulk_comment_' . $action, ['count' => $count, 'ids' => $ids]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Đồng bộ số lượng bình luận approved vào cột comment_count bảng reels
     */
    protected function syncReelCommentCount(int $reelId): void
    {
        try {
            $approvedCount = ReelComment::where('reel_id', $reelId)
                ->where('status', 'approved')
                ->count();

            Reel::where('id', $reelId)->update(['comment_count' => $approvedCount]);
        } catch (\Throwable $e) {}
    }

    /**
     * Đồng bộ số lượng reply approved vào cột replies_count của bình luận cha
     */
    protected function syncParentReplyCount(int $parentId): void
    {
        try {
            $repliesCount = ReelComment::where('parent_id', $parentId)
                ->where('status', 'approved')
                ->count();

            ReelComment::where('id', $parentId)->update(['replies_count' => $repliesCount]);
        } catch (\Throwable $e) {}
    }
}

