<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Services\Admin\ProgressiveSearchService;
use App\Services\Admin\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ProgressiveSearchService $progressiveSearchService
    )
    {
    }

    public function index(Request $request)
    {
        $perPageOptions = [50, 100, 200, 500, 2000];
        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 50;
        }

        $brandId = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $sortBy = (string) $request->input('sort_by', 'latest');

        // Thống kê đếm siêu nhanh qua 1 truy vấn duy nhất
        $stats = Product::withTrashed()
            ->selectRaw("
                COUNT(CASE WHEN deleted_at IS NULL THEN 1 END) as total,
                COUNT(CASE WHEN deleted_at IS NULL AND is_active = 1 THEN 1 END) as active,
                COUNT(CASE WHEN deleted_at IS NULL AND is_active = 0 THEN 1 END) as inactive,
                COUNT(CASE WHEN deleted_at IS NULL AND stock_quantity <= 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as trash
            ")
            ->first();

        $query = Product::query();

        // Xử lý lọc theo trạng thái (bao gồm Thùng rác)
        if ($request->status === 'trash') {
            $query->onlyTrashed();
        } else {
            $query->withTrashed();
            if ($request->status === 'active') {
                $query->where('is_active', true)->whereNull('deleted_at');
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false)->whereNull('deleted_at');
            } elseif ($request->status === 'out_of_stock') {
                $query->whereNull('deleted_at')->where('stock_quantity', '<=', 0);
            } else {
                $query->whereNull('deleted_at');
            }
        }

        $query->select([
            'products.id',
            'products.sku',
            'products.name',
            'products.slug',
            'products.price',
            'products.sale_price',
            'products.stock_quantity',
            'products.brand_id',
            'products.primary_category_id',
            'products.category_ids',
            'products.is_active',
            'products.is_featured',
            'products.has_variants',
            'products.created_at',
            'products.updated_at',
            'products.deleted_at',
        ])
        ->with([
            'primaryCategory:id,name,slug',
            'brand:id,name,slug',
            'primaryImage:id,product_id,url,path',
            'currentFlashSaleItem.flashSale:id,title,end_time,status,is_active',
        ])
        ->when($brandId, function ($query) use ($brandId) {
            $query->where('brand_id', $brandId);
        })
        ->when($request->filled('category_id'), function ($query) use ($request) {
            $query->inCategory($request->integer('category_id'));
        })
        ->when($request->filled('stock_status') && $request->status !== 'out_of_stock', function ($query) use ($request) {
            if ($request->stock_status === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            } elseif ($request->stock_status === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            }
        })
        ->when($request->filled('is_featured'), function ($query) use ($request) {
            $query->where('is_featured', $request->boolean('is_featured'));
        })
        ->when($request->filled('has_variants'), function ($query) use ($request) {
            $query->where('has_variants', $request->boolean('has_variants'));
        })
        ->when($request->filled('flash_sale_status'), function ($query) use ($request) {
            if ($request->input('flash_sale_status') === '1') {
                $query->whereHas('currentFlashSaleItem');
            } elseif ($request->input('flash_sale_status') === '0') {
                $query->whereDoesntHave('currentFlashSaleItem');
            }
        })
        ->when($request->filled('image_status'), function ($query) use ($request) {
            $this->applyImageStatusFilter($query, (string) $request->input('image_status'));
        });

        $searchMeta = $this->progressiveSearchService->apply(
            $query,
            $request->input('keyword'),
            ['products.name'],
            ['products.sku', 'products.slug']
        );

        match ($sortBy) {
            'oldest' => $query->orderBy('products.id'),
            'name_asc' => $query->orderBy('products.name'),
            'name_desc' => $query->orderByDesc('products.name'),
            'price_asc' => $query->orderByRaw('COALESCE(products.sale_price, products.price) ASC'),
            'price_desc' => $query->orderByRaw('COALESCE(products.sale_price, products.price) DESC'),
            'stock_asc' => $query->orderBy('products.stock_quantity'),
            'stock_desc' => $query->orderByDesc('products.stock_quantity'),
            default => $query->orderByDesc('products.id'),
        };

        $products = $query
            ->paginate($perPage)
            ->appends($request->query());

        $rawCategories = Category::select('id', 'name', 'parent_id', 'sort_order')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $categories = $this->buildCategoryTree($rawCategories);
        $brands = Brand::select('id', 'name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $validImageMap = array_flip($this->getValidImageProductIds());

        return view('admins.products.index', compact('products', 'categories', 'brands', 'perPageOptions', 'perPage', 'searchMeta', 'stats', 'validImageMap'));
    }

    /**
     * Xây dựng danh sách danh mục theo dạng cây phân cấp (cha -> con -> cháu)
     */
    protected function buildCategoryTree($categories, $parentId = null, $depth = 0): array
    {
        $branch = [];
        $items = $categories->where('parent_id', $parentId);

        foreach ($items as $category) {
            $prefix = '';
            if ($depth === 1) {
                $prefix = '↳ ';
            } elseif ($depth === 2) {
                $prefix = '   ↳ ';
            } elseif ($depth > 2) {
                $prefix = str_repeat('   ', $depth - 1) . '↳ ';
            }

            $category->depth = $depth;
            $category->hierarchical_name = $prefix . $category->name;
            $branch[] = $category;

            $children = $this->buildCategoryTree($categories, $category->id, $depth + 1);
            foreach ($children as $child) {
                $branch[] = $child;
            }
        }

        if ($parentId === null) {
            $usedIds = collect($branch)->pluck('id')->all();
            $orphans = $categories->whereNotIn('id', $usedIds);
            foreach ($orphans as $orphan) {
                $orphan->depth = 0;
                $orphan->hierarchical_name = $orphan->name;
                $branch[] = $orphan;
            }
        }

        return $branch;
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
            'brands' => Brand::orderBy('sort_order')->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => $productTags,
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
            'brands' => Brand::orderBy('sort_order')->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tags' => $productTags,
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
            ->with('success', 'Đã xóa mềm sản phẩm thành công.');
    }

    public function restore(int $id)
    {
        try {
            $this->productService->restore($id);

            return redirect()
                ->route('admin.products.index', ['status' => 'trash'])
                ->with('success', 'Đã khôi phục sản phẩm thành công.');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể khôi phục: ' . $e->getMessage());
        }
    }

    public function forceDelete(int $id)
    {
        try {
            $this->productService->forceDelete($id);

            return redirect()
                ->route('admin.products.index', ['status' => 'trash'])
                ->with('success', 'Đã xóa vĩnh viễn sản phẩm thành công.');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể xóa vĩnh viễn: ' . $e->getMessage());
        }
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
            'bulk_action' => ['required', 'in:hide,show,delete,restore,force_delete'],
        ]);

        $productIds = $request->input('selected', []);
        $action = $request->input('bulk_action');

        if ($action === 'hide') {
            Product::whereIn('id', $productIds)->update(['is_active' => false]);
            return back()->with('success', 'Đã tạm ẩn ' . count($productIds) . ' sản phẩm.');
        }

        if ($action === 'show') {
            Product::whereIn('id', $productIds)->update(['is_active' => true]);
            return back()->with('success', 'Đã hiển thị (Active) ' . count($productIds) . ' sản phẩm.');
        }

        if ($action === 'delete') {
            $products = Product::whereIn('id', $productIds)->get();
            foreach ($products as $product) {
                $this->productService->delete($product);
            }
            return back()->with('success', 'Đã xóa mềm ' . count($productIds) . ' sản phẩm.');
        }

        if ($action === 'restore') {
            Product::onlyTrashed()->whereIn('id', $productIds)->restore();
            return back()->with('success', 'Đã khôi phục ' . count($productIds) . ' sản phẩm.');
        }

        if ($action === 'force_delete') {
            $products = Product::onlyTrashed()->whereIn('id', $productIds)->get();
            foreach ($products as $product) {
                $this->productService->forceDelete($product->id);
            }
            return back()->with('success', 'Đã xóa vĩnh viễn ' . count($productIds) . ' sản phẩm.');
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

    /**
     * Lấy danh sách ID các sản phẩm có ảnh chính hợp lệ (tồn tại file vật lý trên đĩa hoặc là URL online).
     */
    protected function getValidImageProductIds(): array
    {
        $dirClothes = public_path('clients/assets/img/clothes');
        $files = is_dir($dirClothes) ? scandir($dirClothes) : [];
        $existingFiles = array_flip($files);

        $dirImports = public_path('clients/assets/img/imports');
        if (is_dir($dirImports)) {
            $existingFiles += array_flip(scandir($dirImports));
        }

        $primaryImages = DB::table('images')
            ->where('is_primary', true)
            ->whereNotNull('product_id')
            ->select('product_id', 'url')
            ->get();

        $validProductIds = [];
        foreach ($primaryImages as $img) {
            $url = trim((string) $img->url);
            if ($url === '') {
                continue;
            }
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                $validProductIds[$img->product_id] = true;
                continue;
            }
            $baseName = basename($url);
            if (isset($existingFiles[$baseName])) {
                $validProductIds[$img->product_id] = true;
            }
        }

        return array_keys($validProductIds);
    }

    /**
     * Áp dụng điều kiện lọc theo tình trạng ảnh sản phẩm.
     */
    protected function applyImageStatusFilter($query, string $status): void
    {
        $validIds = $this->getValidImageProductIds();

        if ($status === 'missing_or_invalid') {
            $query->whereNotIn('products.id', $validIds);
        } elseif ($status === 'has_valid') {
            $query->whereIn('products.id', $validIds);
        }
    }
}
