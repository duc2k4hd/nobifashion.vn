<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReelStoreRequest;
use App\Http\Requests\Admin\ReelUpdateRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Reel;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReelManageController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Danh sách video Reels với bộ lọc & phân trang server-side
     */
    public function index(Request $request): View
    {
        $query = Reel::query()->with(['brand'])->withCount(['products', 'comments']);

        // 1. Tìm kiếm từ khóa
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('external_id', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhere('author_handle', 'like', "%{$search}%")
                  ->orWhere('caption', 'like', "%{$search}%");
            });
        }

        // 2. Lọc theo trạng thái
        $status = $request->input('status', 'all');
        if ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status !== 'all' && in_array($status, ['published', 'draft', 'hidden', 'archived'])) {
            $query->where('status', $status);
        }

        // 3. Lọc theo thương hiệu
        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }

        // 4. Lọc theo danh mục
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // 5. Lọc nổi bật
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // 6. Lọc có gắn sản phẩm hay không
        if ($request->filled('has_products')) {
            if ($request->input('has_products') === 'yes') {
                $query->has('products');
            } elseif ($request->input('has_products') === 'no') {
                $query->doesntHave('products');
            }
        }

        // 7. Lọc theo khoảng ngày đăng
        if ($request->filled('published_from')) {
            $query->where('published_at', '>=', $request->input('published_from') . ' 00:00:00');
        }
        if ($request->filled('published_to')) {
            $query->where('published_at', '<=', $request->input('published_to') . ' 23:59:59');
        }

        // 8. Sắp xếp
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'id', 'view_count', 'like_count', 'comment_count', 'share_count', 'save_count',
            'duration', 'published_at', 'created_at', 'order_priority'
        ];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderByDesc('id');
        }

        // Phân trang
        $limit = max(10, min(100, (int) $request->input('limit', 20)));
        $reels = $query->paginate($limit)->withQueryString();

        // Danh sách brands phục vụ filter
        $brands = Brand::orderBy('name')->get(['id', 'name', 'logo']);

        // Đếm nhanh theo trạng thái
        $counts = [
            'all' => Reel::count(),
            'published' => Reel::where('status', 'published')->count(),
            'draft' => Reel::where('status', 'draft')->count(),
            'hidden' => Reel::where('status', 'hidden')->count(),
            'trashed' => Reel::onlyTrashed()->count(),
        ];

        return view('admins.reels.videos.index', compact('reels', 'brands', 'counts'));
    }

    /**
     * Màn hình tạo video mới (7 Tabs)
     */
    public function create(): View
    {
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        return view('admins.reels.videos.create', compact('brands'));
    }

    /**
     * Lưu video Reel mới
     */
    public function store(ReelStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // 1. Tự động sinh slug nếu chưa nhập
        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $uniqueSlug = $slug;
        $counter = 1;
        while (Reel::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $uniqueSlug;

        // 2. Xử lý hashtags
        $validated['hashtags'] = $this->parseHashtags($request->input('hashtags'));

        // 3. External ID fallback
        if (empty($validated['external_id'])) {
            $validated['external_id'] = 'NOBI_' . strtoupper(Str::random(10));
        }

        // 4. Published at
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        DB::beginTransaction();
        try {
            $reel = Reel::create($validated);

            // Gắn sản phẩm nếu có
            if ($request->filled('products') && is_array($request->input('products'))) {
                $syncData = [];
                foreach ($request->input('products') as $index => $productId) {
                    $syncData[$productId] = ['order' => $index + 1];
                }
                $reel->products()->sync($syncData);
            }

            $this->adminService->logAudit('create_reel', [
                'reel_id' => $reel->id,
                'title' => $reel->title,
                'status' => $reel->status,
            ]);

            DB::commit();

            return redirect()->route('admin.reels.videos.edit', $reel->id)
                ->with('success', "Đã tạo thành công video Reel: {$reel->title}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Lỗi khi lưu video: ' . $e->getMessage());
        }
    }

    /**
     * Xem chi tiết video Reel
     */
    public function show(int $id): View
    {
        $reel = Reel::withTrashed()->with(['brand', 'products', 'comments.anonymousUser'])->findOrFail($id);

        // Thống kê xem thực tế từ watch logs
        $watchStats = DB::table('reel_watch_logs')
            ->where('reel_id', $id)
            ->selectRaw('
                COUNT(*) as total_watches,
                SUM(is_completed) as completed_watches,
                SUM(loop_count) as total_loops,
                ROUND(AVG(watch_time_seconds), 1) as avg_watch_time,
                ROUND(AVG(completion_rate), 1) as avg_completion_rate,
                SUM(interacted_like) as logs_likes,
                SUM(interacted_comment) as logs_comments,
                SUM(interacted_product_click) as logs_product_clicks
            ')->first();

        return view('admins.reels.videos.show', compact('reel', 'watchStats'));
    }

    /**
     * Màn hình chỉnh sửa video Reel (7 Tabs)
     */
    public function edit(int $id): View
    {
        $reel = Reel::withTrashed()->with(['brand', 'products'])->findOrFail($id);
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        // Thống kê chỉ đọc
        $watchStats = DB::table('reel_watch_logs')
            ->where('reel_id', $id)
            ->selectRaw('
                COUNT(*) as total_watches,
                SUM(is_completed) as completed_watches,
                SUM(loop_count) as total_loops,
                ROUND(AVG(watch_time_seconds), 1) as avg_watch_time,
                ROUND(AVG(completion_rate), 1) as avg_completion_rate
            ')->first();

        return view('admins.reels.videos.edit', compact('reel', 'brands', 'watchStats'));
    }

    /**
     * Cập nhật video Reel
     */
    public function update(ReelUpdateRequest $request, int $id): RedirectResponse
    {
        $reel = Reel::withTrashed()->findOrFail($id);
        $validated = $request->validated();

        $oldSlug = $reel->slug;
        $newSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['slug'] = $newSlug;

        // Xử lý 301 Redirect nếu slug của video đang xuất bản bị thay đổi
        if ($reel->status === 'published' && $oldSlug !== $newSlug) {
            $this->createSlugRedirect($oldSlug, $newSlug);
        }

        $validated['hashtags'] = $this->parseHashtags($request->input('hashtags'));

        // Cập nhật ngày xuất bản nếu chuyển sang published mà chưa có
        if ($validated['status'] === 'published' && empty($reel->published_at) && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        DB::beginTransaction();
        try {
            $reel->update($validated);

            // Đồng bộ sản phẩm liên quan
            if ($request->has('products')) {
                $productIds = (array) $request->input('products');
                $syncData = [];
                foreach ($productIds as $index => $pid) {
                    if (!empty($pid)) {
                        $syncData[$pid] = ['order' => $index + 1];
                    }
                }
                $reel->products()->sync($syncData);
            }

            $this->adminService->logAudit('update_reel', [
                'reel_id' => $reel->id,
                'title' => $reel->title,
                'status' => $reel->status,
            ]);

            DB::commit();

            return redirect()->route('admin.reels.videos.edit', $reel->id)
                ->with('success', 'Đã cập nhật thông tin video thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Lỗi khi cập nhật video: ' . $e->getMessage());
        }
    }

    /**
     * Xóa mềm video
     */
    public function destroy(int $id): RedirectResponse
    {
        $reel = Reel::findOrFail($id);
        $title = $reel->title;
        $reel->delete();

        $this->adminService->logAudit('soft_delete_reel', ['reel_id' => $id, 'title' => $title]);

        return back()->with('success', "Đã chuyển video \"{$title}\" vào thùng rác.");
    }

    /**
     * Khôi phục video từ thùng rác
     */
    public function restore(int $id): RedirectResponse
    {
        $reel = Reel::onlyTrashed()->findOrFail($id);
        $reel->restore();

        $this->adminService->logAudit('restore_reel', ['reel_id' => $id, 'title' => $reel->title]);

        return back()->with('success', "Đã khôi phục video \"{$reel->title}\".");
    }

    /**
     * Xóa vĩnh viễn video khỏi CSDL
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $reel = Reel::onlyTrashed()->findOrFail($id);
        $title = $reel->title;

        // Xóa liên kết sản phẩm, likes, saves
        $reel->products()->detach();
        $reel->forceDelete();

        $this->adminService->logAudit('force_delete_reel', ['reel_id' => $id, 'title' => $title]);

        return back()->with('success', "Đã xóa vĩnh viễn video \"{$title}\".");
    }

    /**
     * Xử lý thao tác hàng loạt (Bulk actions)
     */
    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $action = $request->input('action');
        $ids = (array) $request->input('ids', []);

        if (empty($ids)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Chưa chọn video nào.']);
            }
            return back()->with('error', 'Vui lòng chọn ít nhất một video để thực hiện.');
        }

        $count = count($ids);

        switch ($action) {
            case 'publish':
                Reel::whereIn('id', $ids)->update(['status' => 'published', 'published_at' => now()]);
                $msg = "Đã xuất bản {$count} video thành công.";
                break;

            case 'unpublish':
                Reel::whereIn('id', $ids)->update(['status' => 'draft']);
                $msg = "Đã chuyển {$count} video về trạng thái Nháp.";
                break;

            case 'feature':
                Reel::whereIn('id', $ids)->update(['is_featured' => true]);
                $msg = "Đã đánh dấu {$count} video là Nổi bật.";
                break;

            case 'unfeature':
                Reel::whereIn('id', $ids)->update(['is_featured' => false]);
                $msg = "Đã bỏ dấu Nổi bật cho {$count} video.";
                break;

            case 'delete':
                Reel::whereIn('id', $ids)->delete();
                $msg = "Đã chuyển {$count} video vào thùng rác.";
                break;

            case 'restore':
                Reel::onlyTrashed()->whereIn('id', $ids)->restore();
                $msg = "Đã khôi phục {$count} video thành công.";
                break;

            default:
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Hành động không hợp lệ.']);
                }
                return back()->with('error', 'Hành động không hợp lệ.');
        }

        $this->adminService->logAudit('bulk_' . $action, ['count' => $count, 'ids' => $ids]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /**
     * API kiểm tra nguồn video URL (HEAD request: status, Content-Type, Content-Length, Byte-Range)
     */
    public function checkVideoUrl(Request $request): JsonResponse
    {
        $url = trim((string) $request->input('url'));
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'success' => false,
                'message' => 'Đường dẫn URL không hợp lệ.',
            ]);
        }

        try {
            $response = Http::timeout(6)->withHeaders([
                'User-Agent' => 'NobiFashion-Reels-Checker/1.0',
                'Range' => 'bytes=0-1024',
            ])->head($url);

            $status = $response->status();
            $contentType = $response->header('Content-Type') ?: 'unknown';
            $contentLength = (int) $response->header('Content-Length');
            $acceptRanges = $response->header('Accept-Ranges') ?: 'none';

            $isAccessible = $status >= 200 && $status < 400;
            $isPlayable = str_contains(strtolower($contentType), 'video') || str_contains(strtolower($contentType), 'mp4') || str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.mp4');

            return response()->json([
                'success' => true,
                'accessible' => $isAccessible,
                'status_code' => $status,
                'content_type' => $contentType,
                'content_length_bytes' => $contentLength,
                'content_length_mb' => $contentLength > 0 ? round($contentLength / 1048576, 2) : null,
                'accept_ranges' => $acceptRanges,
                'is_mp4_or_video' => $isPlayable,
                'message' => $isAccessible ? 'Nguồn video phản hồi tốt, sẵn sàng phát' : "Nguồn video trả về mã lỗi HTTP {$status}",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'accessible' => false,
                'message' => 'Không thể kết nối đến máy chủ video: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * API tìm kiếm sản phẩm nhanh để gắn vào video Reel
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $query = Product::where('is_active', true)->with(['primaryImage']);

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                if (is_numeric($q)) {
                    $sub->orWhere('id', (int) $q);
                }
                $sub->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $products = $query->take(20)->get()->map(function ($p) {
            $img = $p->primaryImage ? $p->primaryImage->file_path : null;
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
                'sale_price' => $p->sale_price ? (float) $p->sale_price : null,
                'image' => $img ? (str_starts_with($img, 'http') ? $img : '/' . ltrim($img, '/')) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Chuẩn hóa mảng hashtag từ chuỗi nhập vào
     */
    protected function parseHashtags($raw): array
    {
        if (is_array($raw)) return $raw;
        if (empty($raw)) return [];

        $parts = preg_split('/[\s,]+/', trim($raw));
        $tags = [];
        foreach ($parts as $part) {
            $p = trim($part);
            if (!empty($p)) {
                $tags[] = str_starts_with($p, '#') ? $p : '#' . $p;
            }
        }
        return array_values(array_unique($tags));
    }

    /**
     * Tự động tạo 301 Redirect khi slug video thay đổi
     */
    protected function createSlugRedirect(string $oldSlug, string $newSlug): void
    {
        try {
            $source = '/reels/' . ltrim($oldSlug, '/');
            $target = '/reels/' . ltrim($newSlug, '/');

            if (class_exists(Redirect::class)) {
                Redirect::updateOrCreate(
                    ['source_url' => $source],
                    [
                        'target_url' => $target,
                        'status_code' => 301,
                        'is_active' => true,
                    ]
                );
            }
        } catch (\Throwable $e) {}
    }
}

