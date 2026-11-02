<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\AnonymousUser;
use App\Models\CommentModerationLog;
use App\Models\ReelComment;
use App\Services\ReelsAdmin\ReelsAdminService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelAnonymousUserController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Danh sách người dùng ẩn danh
     */
    public function index(Request $request): View
    {
        $query = AnonymousUser::query()
            ->withCount(['comments', 'likes', 'commentLikes']);

        // 1. Tìm kiếm
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhere('nickname', 'like', "%{$search}%")
                  ->orWhere('public_id', 'like', "%{$search}%");
            });
        }

        // 2. Lọc theo trạng thái
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'banned') {
                $query->where(function ($q) {
                    $q->where('is_banned', true)->orWhere('status', 'banned');
                });
            } else {
                $query->where('status', $status);
            }
        }

        // 3. Sắp xếp
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'last_seen_at', 'created_at', 'comments_count', 'likes_count'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderByDesc('id');
        }

        $users = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => AnonymousUser::count(),
            'active' => AnonymousUser::where('status', 'active')->where('is_banned', false)->count(),
            'muted' => AnonymousUser::where('status', 'muted')->count(),
            'banned' => AnonymousUser::where('is_banned', true)->orWhere('status', 'banned')->count(),
        ];

        return view('admins.reels.anonymous-users.index', compact('users', 'counts'));
    }

    /**
     * Xem chi tiết người dùng ẩn danh và lịch sử hoạt động
     */
    public function show(int $id): View
    {
        $user = AnonymousUser::withCount(['comments', 'likes', 'commentLikes'])->findOrFail($id);

        // Lịch sử bình luận
        $comments = ReelComment::withTrashed()
            ->with(['reel'])
            ->where('anonymous_user_id', $id)
            ->latest('id')
            ->paginate(15, ['*'], 'comments_page');

        // Lịch sử bị kiểm duyệt
        $moderationLogs = CommentModerationLog::with(['rule'])
            ->where('anonymous_user_id', $id)
            ->latest('id')
            ->take(20)
            ->get();

        return view('admins.reels.anonymous-users.show', compact('user', 'comments', 'moderationLogs'));
    }

    /**
     * Khóa tài khoản người dùng ẩn danh (Ban)
     */
    public function ban(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = AnonymousUser::findOrFail($id);

        $reason = trim((string) $request->input('reason', 'Vi phạm tiêu chuẩn cộng đồng'));
        $durationDays = (int) $request->input('duration_days', 0); // 0 = vĩnh viễn

        $bannedUntil = null;
        if ($durationDays > 0) {
            $bannedUntil = Carbon::now()->addDays($durationDays);
        }

        $user->update([
            'is_banned' => true,
            'status' => 'banned',
            'ban_reason' => $reason,
            'banned_until' => $bannedUntil,
        ]);

        $this->adminService->logAudit('ban_anonymous_user', [
            'user_id' => $id,
            'nickname' => $user->nickname,
            'reason' => $reason,
            'duration_days' => $durationDays,
        ]);

        $msg = "Đã khóa người dùng {$user->nickname}" . ($durationDays > 0 ? " trong {$durationDays} ngày." : " vĩnh viễn.");

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Mở khóa tài khoản người dùng ẩn danh (Unban)
     */
    public function unban(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = AnonymousUser::findOrFail($id);

        $user->update([
            'is_banned' => false,
            'status' => 'active',
            'ban_reason' => null,
            'banned_until' => null,
        ]);

        $this->adminService->logAudit('unban_anonymous_user', [
            'user_id' => $id,
            'nickname' => $user->nickname,
        ]);

        $msg = "Đã mở khóa người dùng {$user->nickname} thành công.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Chuyển trạng thái người dùng (active / muted)
     */
    public function toggleMute(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = AnonymousUser::findOrFail($id);

        $newStatus = $user->status === 'muted' ? 'active' : 'muted';
        $user->status = $newStatus;
        $user->save();

        $this->adminService->logAudit('toggle_mute_anonymous_user', [
            'user_id' => $id,
            'status' => $newStatus,
        ]);

        $msg = $newStatus === 'muted'
            ? "Đã chặn bình luận (Mute) người dùng {$user->nickname}."
            : "Đã gỡ chặn bình luận người dùng {$user->nickname}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'status' => $newStatus]);
        }
        return back()->with('success', $msg);
    }
}

