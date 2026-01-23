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
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(Request $request, $url = null)
    {
        $settings = View::shared('settings') ?? Setting::first();

        // ==========================
        // 🔹 Xử lý khi có slug danh mục
        // ==========================
        if ($url !== null) {
            $category = Category::active()->where('slug', $url)->first();

            if (!$category) {
                return view('clients.pages.errors.404');
            }

            // Kiểm tra xem danh mục này có phải là cha hay không
            $hasChildren = Category::where('parent_id', $category->id)->exists();

            if ($hasChildren) {
                // ✅ Nếu là danh mục cha → lấy tất cả con (bao gồm cháu)
                $categoryIds = collect([$category->id]);
                $queue = [$category->id];

                while (!empty($queue)) {
                    $parentId = array_pop($queue);
                    $childrenIds = Category::where('parent_id', $parentId)->pluck('id')->all();
                    foreach ($childrenIds as $childId) {
                        if (!$categoryIds->contains($childId)) {
                            $categoryIds->push($childId);
                            $queue[] = $childId;
                        }
                    }
                }

                $ids = $categoryIds->all();
            } else {
                // ✅ Nếu là danh mục con → chỉ lấy chính nó
                $ids = [$category->id];
            }

            // Lấy sản phẩm (dùng scope tái sử dụng)
            $products = Product::active()->inCategory($ids);
            // ⚙️ SEO từ Category
            $pageTitle = $category->meta_title ? $category->meta_title . ' – ' . renderMeta($settings->site_name) : "{$category->name} - {$settings->site_name}";
            $pageDescription = $category->meta_description ?? strip_tags($category->description ?: "Khám phá các sản phẩm {$category->name} thời trang phong cách, chất lượng và giá tốt tại {$settings->site_name}.");
            $pageKeywords = $category->meta_keywords ?? "{$category->name}, thời trang nam, thời trang nữ, phong cách trẻ trung, mua sắm online, thời trang hiện đại, nobi fashion";
            $canonicalUrl = $category->meta_canonical ?? $settings->site_url . '/' . $category->slug;
            $pageImage = $category->image ? asset('storage/categories/' . $category->image) : asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo));
        }

        // ==========================
        // 🔹 Trang shop tổng
        // ==========================
        else {
            $products = Product::active();
            $category = null;

            $pageTitle = "Shop {$settings->site_name} - Thời trang trẻ trung và hiện đại";
            $pageDescription = "Khám phá bộ sưu tập thời trang mới nhất tại {$settings->site_name}, mang đến phong cách trẻ trung, năng động và cá tính cho mọi lứa tuổi. Mua sắm dễ dàng, giá tốt, chất lượng đảm bảo.";
            $pageKeywords = "shop {$settings->site_name}, thời trang, quần áo đẹp, đồ nam nữ, phong cách trẻ trung, thời trang hiện đại, shop uy tín, nobi fashion";
            $canonicalUrl = "{$settings->site_url}/shop";
            $pageImage = asset('clients/assets/img/business/' . ($settings->site_banner ?? $settings->site_logo));
        }

        // ==========================
        // 🔹 Các tham số filter
        // ==========================
        $perPage = $request->get('perPage', 30);
        $minPriceRange = $request->get('minPriceRange');
        $maxPriceRange = $request->get('maxPriceRange');
        $colorRange = $request->get('colorRange');
        $sizeRange = $request->get('sizeRange');

        return view('clients.pages.shop.index', compact('products', 'perPage', 'minPriceRange', 'maxPriceRange', 'colorRange', 'sizeRange', 'category', 'pageTitle', 'pageDescription', 'pageKeywords', 'canonicalUrl', 'pageImage'));
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
