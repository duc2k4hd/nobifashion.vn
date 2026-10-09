<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

use App\Models\Category;
use App\Models\Product;

class BrandController extends Controller
{
    /**
     * Danh sách các thương hiệu /brands
     */
    public function index(Request $request)
    {
        $settings = View::shared('settings') ?? Setting::first();

        // Lấy danh sách thương hiệu đang hoạt động cùng số lượng sản phẩm thật
        $brands = Brand::active()
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Với NOBIFASHION, cộng dồn các sản phẩm chưa gán brand_id
        $nobifashionUnassignedCount = Product::active()->whereNull('brand_id')->count();
        $brands->each(function ($b) use ($nobifashionUnassignedCount) {
            if ($b->slug === 'nobifashion') {
                $b->products_count += $nobifashionUnassignedCount;
            }
        });

        // Thống kê toàn cảnh
        $totalBrandsCount = $brands->count();
        $totalProductsCount = Product::active()->count();
        $avgRating = $brands->avg('rating_score') ?: 4.9;

        // Top thương hiệu nổi bật nhất (dựa trên tương tác và số lượng sản phẩm)
        $topBrands = $brands->sortByDesc(function ($b) {
            return ($b->followers_count ?? 0) * 10 + ($b->products_count ?? 0);
        })->take(4)->values();

        // Danh sách các ký tự chữ cái đầu tiên có thương hiệu
        $availableLetters = $brands->map(function ($b) {
            $firstChar = mb_strtoupper(mb_substr(trim($b->name), 0, 1, 'UTF-8'), 'UTF-8');
            return preg_match('/^[A-Z]$/u', $firstChar) ? $firstChar : '#';
        })->unique()->sort()->values();

        return view('clients.pages.brand.list', compact(
            'settings',
            'brands',
            'topBrands',
            'totalBrandsCount',
            'totalProductsCount',
            'avgRating',
            'availableLetters'
        ));
    }

    /**
     * Chi tiết gian hàng thương hiệu /brand/{slug}
     */
    public function show(Request $request, string $slug)
    {
        $settings = View::shared('settings') ?? Setting::first();

        $brand = Brand::active()->where('slug', $slug)->first();

        if (!$brand && $slug !== 'nobifashion') {
            abort(404);
        }

        // Nếu là slug nobifashion nhưng chưa có record trong bảng, lấy record mặc định hoặc tạo
        if (!$brand && $slug === 'nobifashion') {
            $brand = Brand::where('slug', 'nobifashion')->first() ?? new Brand([
                'name' => 'NOBIFASHION',
                'slug' => 'nobifashion',
                'description' => 'Thương hiệu thời trang nam hiện đại, chất lượng chuẩn Việt Nam.',
                'campaign' => '#SUMMER 2026',
                'followers_count' => 2000,
                'rating_score' => 4.90,
                'joined_years' => 9,
            ]);
        }

        // Query cơ sở của sản phẩm thuộc thương hiệu
        $baseQuery = $this->getBrandProductQuery($brand);
        $totalProductsCount = (clone $baseQuery)->count();

        // 1. Sản phẩm bán chạy (Bestsellers: ưu tiên is_featured, top 10)
        $bestsellerProducts = (clone $baseQuery)
            ->with(['primaryImage:id,product_id,url,title'])
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // 2. Danh mục sản phẩm có thật của thương hiệu (Từ con xuống cháu chắt chít, bỏ qua cha)
        $brandProductsCats = (clone $baseQuery)
            ->select(['id', 'primary_category_id', 'category_ids'])
            ->get();

        $categoryCounts = [];
        foreach ($brandProductsCats as $prod) {
            $catIds = [];
            if ($prod->primary_category_id) {
                $catIds[] = (int) $prod->primary_category_id;
            }
            if (is_array($prod->category_ids)) {
                foreach ($prod->category_ids as $cid) {
                    if (is_numeric($cid)) {
                        $catIds[] = (int) $cid;
                    }
                }
            }
            foreach (array_unique($catIds) as $cid) {
                $categoryCounts[$cid] = ($categoryCounts[$cid] ?? 0) + 1;
            }
        }

        $validCategoryIds = array_keys(array_filter($categoryCounts, fn ($cnt) => $cnt > 0));

        $brandCategories = empty($validCategoryIds)
            ? collect()
            : Category::active()
                ->whereNotNull('parent_id') // BỎ QUA CHA (CHA LÀ ROOT, PARENT_ID LÀ NULL)
                ->whereIn('id', $validCategoryIds)
                ->select(['id', 'name', 'slug', 'image', 'sort_order', 'parent_id'])
                ->get()
                ->map(function ($cat) use ($categoryCounts) {
                    $cat->products_count = $categoryCounts[$cat->id] ?? 0;
                    $cat->display_image = $this->getCategoryImageUrl($cat);
                    return $cat;
                })
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['products_count', 'desc'],
                    ['name', 'asc'],
                ])
                ->values();

        // 3. Gợi ý cho bạn (Recommend: trang 1, 15 sản phẩm)
        $recommendProducts = (clone $baseQuery)
            ->with(['primaryImage:id,product_id,url,title'])
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'page', 1);

        // 4. Khởi tạo SEO Meta với dữ liệu từ bảng brands và cơ chế Fallback
        $siteName = $settings->site_name ?? ($settings->subname ?? 'NOBI FASHION');
        $brandName = $brand->name ?? 'NOBIFASHION';
        $brandSlug = $slug ?: ($brand->slug ?? 'nobifashion');

        $pageTitle = !empty($brand->meta_title)
            ? $brand->meta_title
            : ($brandName . ' – Gian Hàng Thương Hiệu Chính Hãng | ' . $siteName);

        $pageDescription = !empty($brand->meta_description)
            ? $brand->meta_description
            : \Illuminate\Support\Str::limit(strip_tags($brand->description ?: ('Khám phá gian hàng thương hiệu ' . $brandName . ' chính hãng tại ' . $siteName . ': thời trang cao cấp, uy tín, chính hãng, giá tốt và giao hàng toàn quốc.')), 160);

        $pageKeywords = !empty($brand->meta_keywords)
            ? $brand->meta_keywords
            : ('thương hiệu ' . mb_strtolower($brandName, 'UTF-8') . ', gian hàng ' . mb_strtolower($brandName, 'UTF-8') . ', ' . mb_strtolower($brandName, 'UTF-8') . ' chính hãng, thời trang ' . mb_strtolower($brandName, 'UTF-8') . ', mua sắm online, ' . mb_strtolower($siteName, 'UTF-8'));

        $canonicalUrl = !empty($brand->meta_canonical)
            ? $brand->meta_canonical
            : route('client.brand.show', $brandSlug);

        return view('clients.pages.brand.index', [
            'settings' => $settings,
            'brand' => $brand,
            'currentSlug' => $slug,
            'totalProductsCount' => $totalProductsCount,
            'bestsellerProducts' => $bestsellerProducts,
            'brandCategories' => $brandCategories,
            'recommendProducts' => $recommendProducts,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageKeywords' => $pageKeywords,
            'canonicalUrl' => $canonicalUrl,
        ]);
    }

    /**
     * AJAX API lấy thêm sản phẩm hoặc lọc danh mục /brand/{slug}/products
     */
    public function getProducts(Request $request, string $slug)
    {
        $brand = Brand::active()->where('slug', $slug)->first();
        if (!$brand && $slug !== 'nobifashion') {
            return response()->json(['success' => false, 'message' => 'Thương hiệu không tồn tại.'], 404);
        }

        if (!$brand && $slug === 'nobifashion') {
            $brand = new Brand(['slug' => 'nobifashion']);
        }

        $query = $this->getBrandProductQuery($brand);

        // Lọc theo category nếu có (lọc theo danh mục và toàn bộ con cháu bên dưới nó nếu có)
        $categoryId = (int) $request->query('category_id');
        if ($categoryId > 0) {
            $targetIds = $this->getCategoryBranchIds($categoryId);

            $query->where(function ($q) use ($targetIds) {
                $q->whereIn('primary_category_id', $targetIds);
                foreach ($targetIds as $tid) {
                    $q->orWhereRaw('JSON_CONTAINS(category_ids, ?)', [json_encode($tid)])
                      ->orWhereRaw('JSON_CONTAINS(category_ids, ?)', ['"' . $tid . '"']);
                }
            });
        }

        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $paginator = $query->with(['primaryImage:id,product_id,url,title'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(fn ($p) => $this->formatProduct($p));

        return response()->json([
            'success' => true,
            'data' => $items,
            'current_page' => $paginator->currentPage(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Xây dựng query sản phẩm thuộc thương hiệu
     */
    protected function getBrandProductQuery(Brand $brand)
    {
        $query = Product::query()
            ->active()
            ->where('stock_quantity', '>', 0);

        if ($brand->slug === 'nobifashion') {
            $brandId = $brand->id ?? 0;
            $query->where(function ($q) use ($brandId) {
                $q->whereNull('brand_id');
                if ($brandId > 0) {
                    $q->orWhere('brand_id', $brandId);
                }
            });
        } else {
            $query->where('brand_id', $brand->id);
        }

        return $query;
    }

    /**
     * Định dạng dữ liệu sản phẩm chuẩn hóa
     */
    protected function formatProduct(Product $product): array
    {
        $hasSale = $product->sale_price && (float) $product->sale_price > 0 && (float) $product->sale_price < (float) $product->price;
        $currentPrice = $hasSale ? (float) $product->sale_price : (float) $product->price;
        $oldPrice = $hasSale ? (float) $product->price : null;

        $imageUrl = $product->primaryImage?->url
            ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
            : asset('clients/assets/img/clothes/no-image.webp');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => route('client.product.detail', $product->slug),
            'image_url' => $imageUrl,
            'price' => number_format($currentPrice, 0, ',', '.') . ' ₫',
            'old_price' => $oldPrice ? number_format($oldPrice, 0, ',', '.') . ' ₫' : null,
            'discount_percent' => $oldPrice ? '-' . round((($oldPrice - $currentPrice) / $oldPrice) * 100) . '%' : null,
            'category_id' => $product->primary_category_id,
        ];
    }

    /**
     * Gán icon emoji tương ứng theo slug danh mục
     */
    protected function getCategoryIcon(?string $slug): string
    {
        $slug = (string) $slug;
        $map = [
            'thoi-trang' => '🛍️',
            'ao-khoac' => '🧥',
            'ao-phao' => '🧥',
            'ao-gio' => '🧥',
            'ao-vest' => '👔',
            'ao-mang-to' => '🧥',
            'ao-thun' => '👕',
            'ao-polo' => '🎽',
            'ao-so-mi' => '👔',
            'ao-len' => '🧶',
            'ao-hoodie' => '🥼',
            'ao-ni' => '🥼',
            'ao-giu-nhiet' => '🧣',
            'ao-chong-nang' => '🧥',
            'ao-nu' => '👚',
            'ao-nam' => '👕',
            'ao' => '👕',
            'quan-jean' => '👖',
            'quan-dai' => '👖',
            'quan-short' => '🩳',
            'quan-au' => '👖',
            'quan-kaki' => '👖',
            'quan-ni' => '👖',
            'quan' => '👖',
            'do-bo' => '🥋',
            'the-thao' => '⚽',
            'dam' => '👗',
            'vay' => '👗',
            'do-lot' => '🩲',
            'do-mac-trong' => '🩲',
            'quan-lot' => '🩲',
            'ao-bra' => '👙',
            'phu-kien' => '🧢',
            'giay' => '👟',
            'dep' => '🩴',
            'that-lung' => '👞',
            'vi' => '👛',
            'balo' => '🎒',
            'tui' => '👜',
            'non' => '🧢',
            'mu' => '🧢',
            'tat' => '🧦',
            'tre-em' => '🧸',
            'gia-dung' => '🏠',
        ];

        foreach ($map as $key => $icon) {
            if (str_contains($slug, $key)) {
                return $icon;
            }
        }

        return '🏷️';
    }

    /**
     * Lấy URL ảnh danh mục hoặc map thông minh theo slug
     */
    protected function getCategoryImageUrl(Category $cat): string
    {
        // 1. Kiểm tra ảnh lưu trực tiếp trong DB (phải có tên file và là file thật)
        $imageName = trim((string) $cat->image);
        if ($imageName !== '') {
            $path = public_path('clients/assets/img/categories/' . $imageName);
            if (is_file($path)) {
                return asset('clients/assets/img/categories/' . $imageName);
            }
        }

        // 2. Map thông minh theo slug sang ảnh sẵn có
        $slug = (string) $cat->slug;
        $map = [
            'ao-phao' => 'ao-khoac.jpg',
            'ao-gio' => 'ao-khoac.jpg',
            'ao-khoac' => 'ao-khoac.jpg',
            'ao-vest' => 'ao-so-mi.png',
            'ao-so-mi' => 'ao-so-mi.png',
            'ao-len' => 'ao-len.png',
            'ao-hoodie' => 'ao-ni.png',
            'ao-ni' => 'ao-ni.png',
            'ao-giu-nhiet' => 'ao-giu-nhiet.jpg',
            'ao-thun' => 'ao-thun.png',
            'ao-polo' => 'ao-thun.png',
            'ao-nu' => 'ao-so-mi.png',
            'ao' => 'ao-thun.png',
            'quan-ni' => 'quan.jpg',
            'quan-jean' => 'quan.jpg',
            'quan-short' => 'quan.jpg',
            'quan-au' => 'quan.jpg',
            'quan-kaki' => 'quan.jpg',
            'quan' => 'quan.jpg',
            'do-bo' => 'do-bo.png',
            'the-thao' => 'do-the-thao.jpg',
            'dam' => 'dam-vay.png',
            'vay' => 'dam-vay.png',
            'do-lot' => 'do-lot-nu.png',
            'do-mac-trong' => 'do-mac-trong.png',
            'phu-kien' => 'phu-kien.png',
            'thoi-trang' => 'ao-thun.png',
        ];

        foreach ($map as $key => $imgName) {
            if (str_contains($slug, $key)) {
                return asset('clients/assets/img/categories/' . $imgName);
            }
        }

        return asset('clients/assets/img/categories/no-image.webp');
    }

    /**
     * Lấy ID của danh mục và tất cả ID con cháu chắt chít bên dưới nó (nếu có)
     */
    protected function getCategoryBranchIds(int $categoryId): array
    {
        $allCats = Category::active()->select(['id', 'parent_id'])->get();
        $childrenOf = [];
        foreach ($allCats as $c) {
            if ($c->parent_id) {
                $childrenOf[$c->parent_id][] = $c->id;
            }
        }

        $targetIds = [$categoryId];
        $queue = [$categoryId];
        while (!empty($queue)) {
            $curr = array_shift($queue);
            if (!empty($childrenOf[$curr])) {
                foreach ($childrenOf[$curr] as $childId) {
                    $targetIds[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return array_values(array_unique($targetIds));
    }
}
