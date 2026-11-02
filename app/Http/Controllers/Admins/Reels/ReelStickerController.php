<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\CommentSticker;
use App\Models\ReelComment;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelStickerController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Danh sách Sticker bình luận
     */
    public function index(Request $request): View
    {
        $query = CommentSticker::query();

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $stickers = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(24)->withQueryString();

        $categories = CommentSticker::select('category')->distinct()->pluck('category')->toArray();

        return view('admins.reels.stickers.index', compact('stickers', 'categories'));
    }

    /**
     * Thêm sticker mới
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:comment_stickers,code'],
            'name' => ['required', 'string', 'max:100'],
            'asset_url' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sticker = CommentSticker::create([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'asset_url' => trim($validated['asset_url']),
            'category' => trim($validated['category']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->adminService->logAudit('create_sticker', ['sticker_id' => $sticker->id, 'code' => $sticker->code]);

        return redirect()->route('admin.reels.stickers.index')
            ->with('success', "Đã thêm sticker {$sticker->name} thành công.");
    }

    /**
     * Cập nhật sticker
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $sticker = CommentSticker::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:comment_stickers,code,' . $id],
            'name' => ['required', 'string', 'max:100'],
            'asset_url' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sticker->update([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'asset_url' => trim($validated['asset_url']),
            'category' => trim($validated['category']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->adminService->logAudit('update_sticker', ['sticker_id' => $id, 'code' => $sticker->code]);

        return redirect()->route('admin.reels.stickers.index')
            ->with('success', 'Đã cập nhật thông tin sticker thành công.');
    }

    /**
     * Bật/tắt trạng thái sticker
     */
    public function toggle(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $sticker = CommentSticker::findOrFail($id);
        $sticker->is_active = !$sticker->is_active;
        $sticker->save();

        $this->adminService->logAudit('toggle_sticker', ['sticker_id' => $id, 'is_active' => $sticker->is_active]);

        $msg = $sticker->is_active ? 'Đã kích hoạt sticker.' : 'Đã ẩn sticker khỏi bình luận.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'is_active' => $sticker->is_active]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Xóa sticker (Kiểm tra an toàn: nếu đã có bình luận dùng thì không xóa cứng)
     */
    public function destroy(int $id): RedirectResponse
    {
        $sticker = CommentSticker::findOrFail($id);

        $hasComments = ReelComment::where('sticker_id', $id)->exists();
        if ($hasComments) {
            $sticker->is_active = false;
            $sticker->save();
            return back()->with('warning', "Sticker này đã được sử dụng trong các bình luận cũ. Để bảo toàn lịch sử, hệ thống đã VÔ HIỆU HÓA thay vì xóa hoàn toàn.");
        }

        $code = $sticker->code;
        $sticker->delete();

        $this->adminService->logAudit('delete_sticker', ['sticker_id' => $id, 'code' => $code]);

        return redirect()->route('admin.reels.stickers.index')
            ->with('success', "Đã xóa sticker \"{$code}\".");
    }
}

