<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\CommentSticker;
use App\Models\ReelComment;
use App\Services\ReelsAdmin\ReelsAdminService;
use App\Services\ReelsAdmin\ZaloStickerSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

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

        $counts = [
            'total' => CommentSticker::count(),
            'active' => CommentSticker::where('is_active', true)->count(),
            'inactive' => CommentSticker::where('is_active', false)->count(),
            'categories' => count($categories),
        ];

        return view('admins.reels.stickers.index', compact('stickers', 'categories', 'counts'));
    }

    /**
     * Thêm sticker mới (Hỗ trợ tải tệp ảnh từ máy hoặc nhập URL)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:comment_stickers,code'],
            'name' => ['required', 'string', 'max:100'],
            'asset_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp,gif', 'max:3072'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $assetUrl = trim($request->input('asset_url', ''));

        // Xử lý tệp tải lên nếu có
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $extension = $file->getClientOriginalExtension() ?: 'svg';
            $cleanName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = time() . '_' . $cleanName . '.' . $extension;
            $targetDir = public_path('clients/assets/img/stickers');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $fileName);
            $assetUrl = '/clients/assets/img/stickers/' . $fileName;
        }

        if (empty($assetUrl)) {
            return back()->withInput()->with('error', 'Vui lòng tải lên tệp ảnh sticker hoặc nhập đường dẫn ảnh (URL).');
        }

        $sticker = CommentSticker::create([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'asset_url' => $assetUrl,
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
            'asset_url' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp,gif', 'max:3072'],
            'category' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $assetUrl = trim($request->input('asset_url', $sticker->asset_url));

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $extension = $file->getClientOriginalExtension() ?: 'svg';
            $cleanName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = time() . '_' . $cleanName . '.' . $extension;
            $targetDir = public_path('clients/assets/img/stickers');
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $fileName);
            $assetUrl = '/clients/assets/img/stickers/' . $fileName;
        }

        $sticker->update([
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'asset_url' => $assetUrl,
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

    /**
     * Nạp nhanh bộ Sticker mẫu thời trang chuẩn đẹp (1-Click Seed)
     */
    public function seedPresets(Request $request): RedirectResponse
    {
        $presets = [
            ['code' => 'chot_don', 'name' => 'Chốt Đơn Liền Tay', 'asset_url' => '/clients/assets/img/stickers/chot_don.svg', 'category' => 'shopping', 'sort_order' => 1],
            ['code' => 'xin_link', 'name' => 'Cho Xin Link Đồ', 'asset_url' => '/clients/assets/img/stickers/xin_link.svg', 'category' => 'shopping', 'sort_order' => 2],
            ['code' => 'shopping_bag', 'name' => 'Giỏ Hàng Thời Trang', 'asset_url' => '/clients/assets/img/stickers/shopping_bag.svg', 'category' => 'shopping', 'sort_order' => 3],
            ['code' => 'heart_fire', 'name' => 'Thả Tim Nồng Nhiệt', 'asset_url' => '/clients/assets/img/stickers/heart_fire.svg', 'category' => 'emotion', 'sort_order' => 4],
            ['code' => 'heart_eyes', 'name' => 'Mê Mẩn Ánh Nhìn', 'asset_url' => '/clients/assets/img/stickers/heart_eyes.svg', 'category' => 'emotion', 'sort_order' => 5],
            ['code' => 'wow_fashion', 'name' => 'Trầm Trồ Xuất Sắc', 'asset_url' => '/clients/assets/img/stickers/wow_fashion.svg', 'category' => 'emotion', 'sort_order' => 6],
            ['code' => 'super_cool', 'name' => 'Siêu Ngầu & Chất', 'asset_url' => '/clients/assets/img/stickers/super_cool.svg', 'category' => 'fashion', 'sort_order' => 7],
            ['code' => 'dress_love', 'name' => 'Váy Xinh Quyến Rũ', 'asset_url' => '/clients/assets/img/stickers/dress_love.svg', 'category' => 'fashion', 'sort_order' => 8],
            ['code' => 'fire_trend', 'name' => 'Hot Trend Cháy Phố', 'asset_url' => '/clients/assets/img/stickers/fire_trend.svg', 'category' => 'fashion', 'sort_order' => 9],
            ['code' => 'top_tier', 'name' => 'Thời Trang Đỉnh Cao', 'asset_url' => '/clients/assets/img/stickers/top_tier.svg', 'category' => 'fashion', 'sort_order' => 10],
            ['code' => 'vip_brand', 'name' => 'Chuẩn Hiệu Nobi', 'asset_url' => '/clients/assets/img/stickers/vip_brand.svg', 'category' => 'brand', 'sort_order' => 11],
            ['code' => 'glow_star', 'name' => 'Tỏa Sáng Rực Rỡ', 'asset_url' => '/clients/assets/img/stickers/glow_star.svg', 'category' => 'brand', 'sort_order' => 12],
        ];

        $inserted = 0;
        foreach ($presets as $item) {
            $existing = CommentSticker::where('code', $item['code'])->first();
            if (!$existing) {
                CommentSticker::create(array_merge($item, ['is_active' => true]));
                $inserted++;
            }
        }

        $this->adminService->logAudit('seed_sticker_presets', ['inserted_count' => $inserted]);

        return redirect()->route('admin.reels.stickers.index')
            ->with('success', "Đã nạp thành công {$inserted} sticker thời trang mẫu vào thư viện.");
    }

    /**
     * Lấy danh sách toàn bộ các bộ sticker trên Zalo (Tab: Tất cả)
     */
    public function zaloPacks(Request $request, ZaloStickerSyncService $zaloService): JsonResponse
    {
        $force = $request->boolean('refresh', false);
        $packs = $zaloService->getZaloPacks($force);

        return response()->json(['success' => true, 'total' => count($packs), 'packs' => $packs]);
    }

    /**
     * Đồng bộ & Cắt 1 bộ Sticker Zalo thành 9 ô con
     */
    public function syncZaloPack(Request $request, ZaloStickerSyncService $zaloService): JsonResponse
    {
        $packId = trim($request->input('pack_id', ''));
        if (empty($packId)) {
            return response()->json(['success' => false, 'message' => 'Thiếu ID bộ sticker Zalo.'], 422);
        }

        $result = $zaloService->syncPackById($packId);
        if (!empty($result['success'])) {
            $this->adminService->logAudit('sync_zalo_sticker_pack', [
                'pack_id' => $packId,
                'created' => $result['created_count'] ?? 0,
            ]);
        }

        return response()->json($result);
    }
}

