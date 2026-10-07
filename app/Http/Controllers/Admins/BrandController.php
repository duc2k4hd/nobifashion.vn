<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query()->withCount('products');

        if ($keyword = trim((string) $request->get('keyword'))) {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('slug', 'like', '%' . $keyword . '%');
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $brands = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        // Đếm chính xác sản phẩm cho NOBIFASHION (bao gồm cả sản phẩm nội bộ brand_id IS NULL)
        $brands->getCollection()->transform(function ($b) {
            if ($b->slug === 'nobifashion') {
                $b->products_count = \App\Models\Product::where(function ($q) use ($b) {
                    $q->whereNull('brand_id')->orWhere('brand_id', $b->id);
                })->count();
            }
            return $b;
        });

        // Thống kê nhanh
        $stats = [
            'total' => Brand::count(),
            'active' => Brand::where('is_active', true)->count(),
            'inactive' => Brand::where('is_active', false)->count(),
        ];

        return view('admins.brands.index', compact('brands', 'stats'));
    }

    public function create()
    {
        $categories = Category::active()->select(['id', 'name'])->orderBy('name')->get();

        return view('admins.brands.form', [
            'brand' => new Brand(),
            'categories' => $categories,
            'initialProducts' => collect([]),
            'totalBrandProducts' => 0,
        ]);
    }

    public function store(BrandRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadLogo($request->file('logo'));
        }

        $data['banner_slides'] = $this->processBannerSlides($request, $data['banner_slides'] ?? []);

        $brand = Brand::create($data);

        // Gán các sản phẩm nếu có chọn khi tạo mới
        if (!empty($data['assigned_product_ids'])) {
            Product::whereIn('id', $data['assigned_product_ids'])->update(['brand_id' => $brand->id]);
        }

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Tạo thương hiệu thành công.');
    }

    public function edit(Brand $brand)
    {
        $categories = Category::active()->select(['id', 'name'])->orderBy('name')->get();

        $productsQuery = Product::query()
            ->select(['id', 'name', 'sku', 'price', 'sale_price', 'brand_id'])
            ->with(['primaryImage:id,product_id,url']);

        if ($brand->slug === 'nobifashion') {
            $productsQuery->where(function ($q) use ($brand) {
                $q->whereNull('brand_id')->orWhere('brand_id', $brand->id);
            });
        } else {
            $productsQuery->where('brand_id', $brand->id);
        }

        $totalBrandProducts = (clone $productsQuery)->count();
        $initialProducts = $productsQuery->orderByDesc('id')->paginate(10);

        return view('admins.brands.form', compact('brand', 'categories', 'initialProducts', 'totalBrandProducts'));
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile($brand->logo);
            $data['logo'] = $this->uploadLogo($request->file('logo'));
        }

        $data['banner_slides'] = $this->processBannerSlides($request, $data['banner_slides'] ?? []);

        $brand->update($data);

        // Gán các sản phẩm nếu có truyền qua form
        if (!empty($data['assigned_product_ids'])) {
            Product::whereIn('id', $data['assigned_product_ids'])->update(['brand_id' => $brand->id]);
        }

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Cập nhật thương hiệu thành công.');
    }

    /**
     * API Tìm kiếm sản phẩm siêu tốc (cho Modal popup)
     */
    public function searchProducts(Request $request)
    {
        $keyword = trim((string) $request->get('keyword', ''));
        $brandId = (int) $request->get('brand_id', 0);
        $categoryId = (int) $request->get('category_id', 0);

        $query = Product::query()
            ->select(['id', 'name', 'sku', 'price', 'sale_price', 'brand_id'])
            ->with(['primaryImage:id,product_id,url', 'brand:id,name']);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('sku', 'like', "%{$keyword}%");
            });
        }

        if ($categoryId > 0) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('primary_category_id', $categoryId)
                  ->orWhereRaw('JSON_CONTAINS(category_ids, ?)', [json_encode($categoryId)]);
            });
        }

        $products = $query->orderByDesc('id')->paginate(15);

        $items = collect($products->items())->map(function ($p) use ($brandId) {
            $imgUrl = $p->primaryImage?->url
                ? asset('clients/assets/img/clothes/' . $p->primaryImage->url)
                : asset('clients/assets/img/clothes/no-image.webp');

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku ?: '--',
                'price' => (float) ($p->sale_price ?: $p->price),
                'price_formatted' => number_format((float) ($p->sale_price ?: $p->price), 0, ',', '.') . ' ₫',
                'image' => $imgUrl,
                'image_url' => $imgUrl,
                'brand_id' => $p->brand_id,
                'brand_name' => $p->brand?->name,
                'is_current_brand' => $brandId > 0 && (int) $p->brand_id === $brandId,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * API Lấy danh sách sản phẩm hiện tại của thương hiệu
     */
    public function getBrandProducts(Brand $brand)
    {
        $query = Product::query()
            ->select(['id', 'name', 'sku', 'price', 'sale_price', 'brand_id'])
            ->with(['primaryImage:id,product_id,url']);

        if ($brand->slug === 'nobifashion') {
            $query->where(function ($q) use ($brand) {
                $q->whereNull('brand_id')->orWhere('brand_id', $brand->id);
            });
        } else {
            $query->where('brand_id', $brand->id);
        }

        $products = $query->orderByDesc('id')->paginate(10);

        $items = collect($products->items())->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku ?: '--',
                'price' => number_format((float) ($p->sale_price ?: $p->price), 0, ',', '.') . ' ₫',
                'image_url' => $p->primaryImage?->url
                    ? asset('clients/assets/img/clothes/' . $p->primaryImage->url)
                    : asset('clients/assets/img/clothes/no-image.webp'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
        ]);
    }

    /**
     * API Gán các sản phẩm đã chọn vào thương hiệu
     */
    public function attachProducts(Request $request, Brand $brand)
    {
        $productIds = $request->input('product_ids', []);
        if (!is_array($productIds) || empty($productIds)) {
            return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 sản phẩm.'], 422);
        }

        Product::whereIn('id', $productIds)->update(['brand_id' => $brand->id]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm thành công ' . count($productIds) . ' sản phẩm vào thương hiệu ' . $brand->name . '.',
            'count' => count($productIds),
        ]);
    }

    /**
     * API Gỡ sản phẩm khỏi thương hiệu
     */
    public function detachProduct(Request $request, Brand $brand)
    {
        $productId = (int) $request->input('product_id');
        if ($productId <= 0) {
            return response()->json(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.'], 422);
        }

        Product::where('id', $productId)->update(['brand_id' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gỡ sản phẩm khỏi thương hiệu thành công.',
        ]);
    }

    public function destroy(Brand $brand)
    {
        $logo = $brand->logo;

        DB::transaction(function () use ($brand) {
            $brand->delete();
        });

        $this->deleteLogoFile($logo);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Đã xóa hãng thành công. Các sản phẩm liên quan sẽ tự bỏ gán hãng.');
    }

    public function toggleStatus(Brand $brand)
    {
        $brand->update([
            'is_active' => ! $brand->is_active,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái hãng.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:brands,id'],
            'bulk_action' => ['required', 'in:hide,show,delete'],
        ]);

        $ids = $request->input('selected', []);
        $action = $request->input('bulk_action');
        $brands = Brand::whereIn('id', $ids)->get();

        if ($action === 'hide') {
            Brand::whereIn('id', $ids)->update(['is_active' => false]);

            return back()->with('success', 'Đã ẩn ' . count($ids) . ' hãng.');
        }

        if ($action === 'show') {
            Brand::whereIn('id', $ids)->update(['is_active' => true]);

            return back()->with('success', 'Đã hiển thị ' . count($ids) . ' hãng.');
        }

        if ($action === 'delete') {
            $logos = $brands->pluck('logo')->filter()->values()->all();

            DB::transaction(function () use ($ids) {
                Brand::whereIn('id', $ids)->delete();
            });

            foreach ($logos as $logo) {
                $this->deleteLogoFile($logo);
            }

            return back()->with('success', 'Đã xóa ' . count($ids) . ' hãng. Các sản phẩm liên quan sẽ tự bỏ gán hãng.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    private function processBannerSlides(Request $request, array $slides): array
    {
        $processed = [];
        foreach ($slides as $idx => $slide) {
            $imageName = $slide['image'] ?? null;

            if ($request->hasFile("banner_slides.{$idx}.image_file")) {
                $imageFile = $request->file("banner_slides.{$idx}.image_file");
                $imageName = $this->uploadSlideImage($imageFile);
            }

            $processed[] = [
                'tag' => $slide['tag'] ?? '',
                'title' => $slide['title'] ?? '',
                'highlight' => $slide['highlight'] ?? '',
                'image' => $imageName ?: null,
            ];
        }

        return $processed;
    }

    private function uploadSlideImage($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'banner-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . now()->format('YmdHis')
            . '-' . Str::random(6)
            . '.' . $extension;
        $directory = public_path('clients/assets/img/brands');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);
        @chmod($directory . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    private function uploadLogo($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . now()->format('YmdHis')
            . '-' . Str::random(6)
            . '.' . $extension;
        $directory = public_path(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/'));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);
        @chmod($directory . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    private function deleteLogoFile(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $path = public_path(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/') . '/' . $filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
