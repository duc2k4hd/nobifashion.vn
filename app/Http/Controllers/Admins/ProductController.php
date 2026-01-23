<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\Admin\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function __construct(protected ProductService $productService)
    {
    }

    public function index(Request $request)
    {
        $products = Product::query()
            ->with('primaryCategory')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('sku', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.products.index', compact('products'));
    }

    public function create()
    {
        // Chỉ lấy tags của products (entity_type = Product::class)
        $productTags = Tag::where('entity_type', Product::class)
            ->select('id', 'name')
            ->distinct('name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return view('admins.products.form', [
            'product' => new Product(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => $productTags,
            'mediaImages' => $this->getMediaImages(),
            'siteUrl' => $this->getSiteUrl(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        try {
            $product = $this->productService->create($request->validated());

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', 'Tạo sản phẩm thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Không thể tạo sản phẩm: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        if ($response = $this->handleEditingLock($product, true)) {
            return $response;
        }

        $product->load(['images', 'variants', 'faqs', 'howTos', 'lockedByUser']);

        // Chỉ lấy tags của products (entity_type = Product::class)
        $productTags = Tag::where('entity_type', Product::class)
            ->select('id', 'name')
            ->distinct('name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return view('admins.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'tags' => $productTags,
            'mediaImages' => $this->getMediaImages(),
            'siteUrl' => $this->getSiteUrl(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        if ($response = $this->handleEditingLock($product, false)) {
            return $response;
        }

        try {
            $this->productService->update($product, $request->validated());
            $this->releaseEditingLock($product);

            return redirect()
                ->route('admin.products.edit', $product)
                ->with('success', 'Cập nhật sản phẩm thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        if ($response = $this->handleEditingLock($product, false)) {
            return $response;
        }

        $this->releaseEditingLock($product);
        $this->productService->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Đã chuyển sản phẩm sang trạng thái tạm ẩn');
    }

    public function restore(ProductRequest $request, Product $product)
    {
        try {
            $payload = $request->validated();
            if (empty($payload['sku']) || empty($payload['name'])) {
                $payload = array_merge($product->toArray(), [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                ]);
            }
            $this->productService->update($product, $payload);
            $product->update(['is_active' => true]);

            return redirect()
                ->route('admin.products.index', ['status' => 'inactive'])
                ->with('success', 'Đã khôi phục sản phẩm (đang ở trạng thái tạm ẩn, cần bật Đang bán nếu muốn hiển thị).');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể khôi phục: ' . $e->getMessage());
        }
    }

    private function getMediaImages(): array
    {
        $directories = [
            public_path('clients/assets/img/clothes'),
            public_path('clients/assets/img/other'),
        ];

        $baseUrl = $this->getSiteUrl();

        $files = [];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (File::files($dir) as $file) {
                $relative = str_replace(public_path(), '', $file->getRealPath());
                $relative = str_replace('\\', '/', $relative);
                $relative = ltrim($relative, '/');
                $fullUrl = rtrim($baseUrl, '/') . '/' . $relative;
                $files[] = [
                    'name' => $file->getFilename(),
                    'url' => $fullUrl,
                    'path' => $relative,
                ];
            }
        }

        return $files;
    }

    private function getSiteUrl(): string
    {
        $siteUrl = Setting::getValue('site_url', config('app.url'));
        if (!$siteUrl) {
            $siteUrl = config('app.url');
        }

        return rtrim($siteUrl, '/');
    }

    protected function handleEditingLock(Product $product, bool $acquireLock = true)
    {
        $currentUser = auth('web')->user();
        $lockTtl = now()->subMinutes((int) config('app.editor_lock_minutes', 15));

        $product->loadMissing('lockedByUser');

        // Tự động release lock nếu đã hết hạn (quan trọng: phải check trước)
        if ($product->locked_by && $product->locked_at) {
            if ($product->locked_at->lessThanOrEqualTo($lockTtl)) {
                // Lock đã hết hạn, tự động release
                $product->forceFill([
                    'locked_by' => null,
                    'locked_at' => null,
                ])->save();
                $product->refresh();
            }
        }

        // QUAN TRỌNG: Nếu lock là của chính user hiện tại, LUÔN cho phép (kể cả khi bấm Lưu)
        if ($product->locked_by && (int) $product->locked_by === (int) $currentUser->id) {
            // User đang sở hữu lock, cho phép tiếp tục
            if ($acquireLock) {
                // Refresh lock time nếu đang acquire
                $product->forceFill([
                    'locked_by' => $currentUser->id,
                    'locked_at' => now(),
                ])->save();
            }
            return null; // Cho phép tiếp tục (không redirect)
        }

        // Kiểm tra lock còn hiệu lực và không phải của user hiện tại
        if ($product->locked_by && (int) $product->locked_by !== (int) $currentUser->id) {
            $lockedAt = $product->locked_at;
            if ($lockedAt && $lockedAt->greaterThan($lockTtl)) {
                $lockedBy = optional($product->lockedByUser)->name ?? 'người dùng khác';
                return redirect()
                    ->route('admin.products.index')
                    ->with('error', "Sản phẩm đang được {$lockedBy} chỉnh sửa. Vui lòng thử lại sau vài phút.");
            }
        }

        // Tạo lock mới nếu cần (khi vào trang edit)
        if ($acquireLock) {
            $product->forceFill([
                'locked_by' => $currentUser->id,
                'locked_at' => now(),
            ])->save();
        }

        return null;
    }

    protected function releaseEditingLock(Product $product): void
    {
        if ($product->locked_by && $product->locked_by === auth('web')->id()) {
            $product->forceFill([
                'locked_by' => null,
                'locked_at' => null,
            ])->save();
        }
    }

    /**
     * Release lock via API (khi đóng trang hoặc navigate away)
     */
    public function releaseLock(Product $product)
    {
        $this->releaseEditingLock($product);
        
        return response()->json([
            'success' => true,
            'message' => 'Lock đã được release thành công'
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:products,id'],
            'bulk_action' => ['required', 'in:hide,delete'],
        ]);

        $productIds = $request->input('selected', []);
        $action = $request->input('bulk_action');

        if ($action === 'hide') {
            Product::whereIn('id', $productIds)->update(['is_active' => false]);
            return back()->with('success', 'Đã chuyển ' . count($productIds) . ' sản phẩm sang trạng thái tạm ẩn.');
        }

        if ($action === 'delete') {
            foreach (Product::whereIn('id', $productIds)->get() as $product) {
                $this->productService->delete($product);
            }
            return back()->with('success', 'Đã xóa mềm ' . count($productIds) . ' sản phẩm.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    /**
     * Trả về danh sách biến thể dạng JSON cho trang tạo đơn hàng admin.
     */
    public function variants(Request $request, Product $product)
    {
        // Nếu không yêu cầu JSON, trả về 404 để tránh bị truy cập nhầm.
        if ($request->query('format') !== 'json') {
            abort(404);
        }

        $product->load(['variants' => function ($q) {
            // Bảng product_variants không có cột is_active, dùng status = 1 giống các chỗ khác
            $q->where('status', 1)->orderBy('id');
        }]);

        return response()->json([
            'product' => [
                'id'    => $product->id,
                'name'  => $product->name,
                'sku'   => $product->sku,
                'price' => (float) ($product->sale_price ?? $product->price),
            ],
            'variants' => $product->variants->map(function (ProductVariant $variant) {
                return [
                    'id'         => $variant->id,
                    'sku'        => $variant->sku,
                    'name'       => $variant->name,
                    'price'      => (float) ($variant->sale_price ?? $variant->price),
                    'stock'      => $variant->stock_quantity,
                    'attributes' => $variant->attributes,
                ];
            })->values(),
        ]);
    }
}

