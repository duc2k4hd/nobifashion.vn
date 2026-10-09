<?php

namespace App\Http\Controllers\Clients;

use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(Request $request, $url = null)
    {
        $settings = View::shared('settings') ?? Setting::first();

        // 1. Lấy toàn bộ màu sắc và kích cỡ thực tế từ database (Cache 1 giờ trong RAM O(1))
        $availableColors = Cache::remember('shop_available_colors_v2', 3600, function () {
            return DB::table('product_variants')
                ->where('is_active', 1)
                ->whereNotNull('attributes')
                ->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.color')) as color")
                ->pluck('color')
                ->map(fn($c) => trim((string) $c))
                ->filter(fn($c) => $c !== '' && $c !== 'null')
                ->unique(fn($c) => mb_strtolower($c))
                ->values()
                ->all();
        });

        $availableSizes = Cache::remember('shop_available_sizes_v2', 3600, function () {
            return DB::table('product_variants')
                ->where('is_active', 1)
                ->whereNotNull('attributes')
                ->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.size')) as size")
                ->pluck('size')
                ->map(fn($s) => trim((string) $s))
                ->filter(fn($s) => $s !== '' && $s !== 'null')
                ->unique(fn($s) => mb_strtoupper($s))
                ->values()
                ->all();
        });

        // 2. Xử lý danh mục (nếu có slug)
        $category = null;
        if ($url !== null) {
            $category = Category::active()->where('slug', $url)->first();

            if (!$category) {
                $redirect = app(\App\Services\RedirectService::class)->resolveRequest($request);
                if ($redirect) {
                    return redirect()->to($redirect['url'], $redirect['status_code'] ?? 301);
                }

                return response()->view('clients.pages.errors.404', [], 404);
            }

            // Cache danh sách category ID con/cháu (1 giờ)
            $descendantIds = Cache::remember("category_descendants_{$category->id}", 3600, function () use ($category) {
                $allCategories = Category::select('id', 'parent_id')->get();
                $ids = [$category->id];
                $queue = [$category->id];
                while (!empty($queue)) {
                    $pid = array_pop($queue);
                    $children = $allCategories->where('parent_id', $pid)->pluck('id')->all();
                    foreach ($children as $cid) {
                        if (!in_array($cid, $ids, true)) {
                            $ids[] = $cid;
                            $queue[] = $cid;
                        }
                    }
                }
                return $ids;
            });

            $productsQuery = Product::active()->inCategory($descendantIds);

            // SEO Meta
            $pageTitle = $category->meta_title ? $category->meta_title . ' – ' . renderMeta($settings->site_name) : "{$category->name} - {$settings->site_name}";
            $pageDescription = $category->meta_description ?? strip_tags($category->description ?: "Khám phá các sản phẩm {$category->name} thời trang phong cách, chất lượng và giá tốt tại {$settings->site_name}.");
            $pageKeywords = $category->meta_keywords ?? "{$category->name}, thời trang nam, thời trang nữ, phong cách trẻ trung, mua sắm online, thời trang hiện đại, nobi fashion";
            $canonicalUrl = $category->meta_canonical ?? $settings->site_url . '/' . $category->slug;
            $pageImage = $category->image ? asset('storage/categories/' . $category->image) : asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo));
        } else {
            $productsQuery = Product::active();

            $pageTitle = "Shop {$settings->site_name} - Thời trang trẻ trung và hiện đại";
            $pageDescription = "Khám phá bộ sưu tập thời trang mới nhất tại {$settings->site_name}, mang đến phong cách trẻ trung, năng động và cá tính cho mọi lứa tuổi. Mua sắm dễ dàng, giá tốt, chất lượng đảm bảo.";
            $pageKeywords = "shop {$settings->site_name}, thời trang, quần áo đẹp, đồ nam nữ, phong cách trẻ trung, thời trang hiện đại, shop uy tín, nobi fashion";
            $canonicalUrl = "{$settings->site_url}/shop";
            $pageImage = asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo));
        }

        // 3. Đọc các tham số filter
        $perPage = (int) $request->get('perPage', 30);
        if (!in_array($perPage, [24, 30, 36, 48, 60, 72, 84, 96], true)) {
            $perPage = 30;
        }

        $minPriceRange = $request->get('minPriceRange');
        $maxPriceRange = $request->get('maxPriceRange');
        $colorRange = $request->get('colorRange');
        $sizeRange = $request->get('sizeRange');
        $sort = (string) $request->get('sort', 'default');
        if (!in_array($sort, ['default', 'price-asc', 'price-desc', 'newest'], true)) {
            $sort = 'default';
        }

        // 4. Áp dụng bộ lọc Giá (tận dụng index và tính toán giá sale)
        if ($minPriceRange !== null && $minPriceRange !== '' || $maxPriceRange !== null && $maxPriceRange !== '') {
            $min = is_numeric($minPriceRange) ? (float) $minPriceRange : null;
            $max = is_numeric($maxPriceRange) ? (float) $maxPriceRange : null;
            if ($min !== null && ($min < 0 || $min > 1000000000)) $min = null;
            if ($max !== null && ($max < 0 || $max > 1000000000)) $max = null;

            if ($min !== null && $max !== null) {
                $productsQuery->whereRaw('COALESCE(products.sale_price, products.price) BETWEEN ? AND ?', [$min, $max]);
            } elseif ($min !== null) {
                $productsQuery->whereRaw('COALESCE(products.sale_price, products.price) >= ?', [$min]);
            } elseif ($max !== null) {
                $productsQuery->whereRaw('COALESCE(products.sale_price, products.price) <= ?', [$max]);
            }
        }

        // 5. Áp dụng bộ lọc Màu sắc (Subquery index cực nhanh O(log N) + Chặn spam)
        if (!empty($colorRange)) {
            $colorList = array_values(array_filter(array_map('trim', is_array($colorRange) ? $colorRange : explode(',', (string) $colorRange))));
            $colorList = array_slice(array_filter($colorList, fn($c) => mb_strlen($c) <= 50), 0, 10);

            if (!empty($colorList)) {
                $productsQuery->whereIn('products.id', function ($sub) use ($colorList) {
                    $sub->select('product_id')
                        ->from('product_variants')
                        ->where('is_active', 1)
                        ->where(function ($q) use ($colorList) {
                            foreach ($colorList as $color) {
                                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.color')) = ?", [$color])
                                  ->orWhereJsonContains('attributes->color', $color);
                            }
                        });
                });
            }
        }

        // 6. Áp dụng bộ lọc Kích cỡ (Subquery index cực nhanh + Chặn spam)
        if (!empty($sizeRange)) {
            $sizeList = array_values(array_filter(array_map('trim', is_array($sizeRange) ? $sizeRange : explode(',', (string) $sizeRange))));
            $sizeList = array_slice(array_filter($sizeList, fn($s) => mb_strlen($s) <= 20), 0, 10);

            if (!empty($sizeList)) {
                $productsQuery->whereIn('products.id', function ($sub) use ($sizeList) {
                    $sub->select('product_id')
                        ->from('product_variants')
                        ->where('is_active', 1)
                        ->where(function ($q) use ($sizeList) {
                            foreach ($sizeList as $size) {
                                $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.size')) = ?", [$size])
                                  ->orWhereJsonContains('attributes->size', $size);
                            }
                        });
                });
            }
        }

        // 7. Sắp xếp
        match ($sort) {
            'price-asc' => $productsQuery->orderByRaw('COALESCE(products.sale_price, products.price) ASC'),
            'price-desc' => $productsQuery->orderByRaw('COALESCE(products.sale_price, products.price) DESC'),
            'newest' => $productsQuery->orderByDesc('products.id'),
            default => $productsQuery->orderByDesc('products.id'),
        };

        // 8. Tối ưu Eager Loading và Select cột cần thiết (Chống N+1 query)
        $productsQuery->select([
            'products.id',
            'products.name',
            'products.slug',
            'products.price',
            'products.sale_price',
            'products.primary_category_id',
            'products.created_at',
            'products.is_active',
        ])->with([
            'primaryImage:id,product_id,url,alt,title',
            'primaryCategory:id,name,slug',
        ]);

        // 9. Thực hiện phân trang duy nhất 1 lần
        $productsMain = $productsQuery->paginate($perPage)->appends($request->query());

        // 10. Cache sidebar 4 sản phẩm mới nhất (10 phút)
        $sidebarNewProducts = Cache::remember('shop_sidebar_new_products_v2', 600, function () {
            return Product::active()
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'primary_category_id')
                ->with('primaryImage:id,product_id,url,alt,title')
                ->orderByDesc('id')
                ->limit(4)
                ->get();
        });

        // 11. Hỗ trợ AJAX Instant Filter (Phản hồi <50ms không reload trang)
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'html' => view('clients.pages.shop.index', compact(
                    'productsMain',
                    'availableColors',
                    'availableSizes',
                    'sidebarNewProducts',
                    'perPage',
                    'minPriceRange',
                    'maxPriceRange',
                    'colorRange',
                    'sizeRange',
                    'sort',
                    'category',
                    'pageTitle',
                    'pageDescription',
                    'pageKeywords',
                    'canonicalUrl',
                    'pageImage'
                ))->fragment('shop-product-list'),
                'total' => $productsMain->total(),
                'firstItem' => $productsMain->firstItem() ?? 0,
                'lastItem' => $productsMain->lastItem() ?? 0,
            ]);
        }

        return view('clients.pages.shop.index', compact(
            'productsMain',
            'availableColors',
            'availableSizes',
            'sidebarNewProducts',
            'perPage',
            'minPriceRange',
            'maxPriceRange',
            'colorRange',
            'sizeRange',
            'sort',
            'category',
            'pageTitle',
            'pageDescription',
            'pageKeywords',
            'canonicalUrl',
            'pageImage'
        ));
    }

    public function search(Request $request)
    {
        $keyword = trim($request->input('keyword', ''));

        if ($keyword === '') {
            return response()->json([]);
        }

        $products = Product::query()
            ->select('id', 'name', 'slug', 'price', 'sale_price')
            ->where('name', 'LIKE', "%{$keyword}%")
            ->limit(10)
            ->get();

        $products->transform(function ($product) {
            $product->name = renderMeta($product->name);
            return $product;
        });

        return response()->json($products);
    }

    public function searchKeyword(Request $request)
    {
        $settings = View::shared('settings') ?? Setting::first();
        $keyword = trim($request->input('keyword', ''));

        // ==========================
        // 🔹 Chuẩn hóa & tách từ
        // ==========================
        $words = array_filter(explode(' ', $keyword)); // Tách từng từ khóa nhỏ

        // ==========================
        // 🔹 Truy vấn theo 2 lớp ưu tiên
        // ==========================
        $products = Product::active()
            ->where(function ($q) use ($keyword, $words) {
                // Ưu tiên cụm từ chính xác (xếp đầu)
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('slug', 'LIKE', "%{$keyword}%")
                    ->orWhere('sku', 'LIKE', "%{$keyword}%");

                // Sau đó là từng từ tách nhỏ (mở rộng)
                foreach ($words as $word) {
                    $q->orWhere('name', 'LIKE', "%{$word}%")
                        ->orWhere('slug', 'LIKE', "%{$word}%")
                        ->orWhere('sku', 'LIKE', "%{$word}%");
                }
            })
            ->orderByRaw(
                "
            CASE
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END
        ",
                ["%{$keyword}%", "{$keyword}%"],
            ) // Ưu tiên cụm khớp đầu hoặc toàn phần
            ->orderBy('name'); // fallback alphabet

        // ==========================
        // 🔹 Các tham số filter
        // ==========================
        $perPage = $request->get('perPage', 30);
        $minPriceRange = $request->get('minPriceRange');
        $maxPriceRange = $request->get('maxPriceRange');
        $colorRange = $request->get('colorRange');
        $sizeRange = $request->get('sizeRange');

        // ==========================
        // 🔹 SEO Metadata
        // ==========================
        $pageTitle = "Kết quả tìm kiếm cho '{$keyword}' - " . $settings->site_name;
        $pageDescription = "Tìm thấy các sản phẩm liên quan đến '{$keyword}' tại {$settings->site_name}. Khám phá những mẫu thời trang mới nhất, đẹp và phù hợp với bạn.";
        $pageKeywords = implode(', ', array_merge([$keyword], $words)) . ", shop {$settings->site_name}, thời trang, sản phẩm đẹp";
        $canonicalUrl = "{$settings->site_url}/shop/search?keyword=" . urlencode($keyword);
        $pageImage = asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo));

        $category = null;

        return view('clients.pages.shop.index', compact('products', 'keyword', 'category', 'perPage', 'minPriceRange', 'maxPriceRange', 'colorRange', 'sizeRange', 'pageTitle', 'pageDescription', 'pageKeywords', 'canonicalUrl', 'pageImage'));
    }
}
