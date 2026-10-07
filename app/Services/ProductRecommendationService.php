<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductRecommendationService
{
    public const CACHE_TTL = 1800; // 30 phút

    /**
     * Lấy danh sách sản phẩm gợi ý load siêu tốc (có cache & tối ưu select/relations)
     *
     * @param int $limit
     * @return Collection
     */
    public static function getRecommendedProducts(int $limit = 8): Collection
    {
        $recommenCatId = (int) (config('settings.product_recommen') 
            ?? Setting::getValue('product_recommen', 0));

        $cacheKey = "products_recommended_v2_{$recommenCatId}_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($recommenCatId, $limit) {
            // 1. Nếu có ID danh mục cấu hình, kiểm tra xem danh mục có tồn tại & đang hoạt động không
            if ($recommenCatId > 0) {
                $categoryExists = Category::where('id', $recommenCatId)
                    ->where('is_active', true)
                    ->exists();

                if ($categoryExists) {
                    $catIds = self::getCategoryAndDescendantIds($recommenCatId);
                    $products = self::fetchProductsByCategoryIds($catIds, $limit);

                    if ($products->isNotEmpty()) {
                        return $products;
                    }
                }
            }

            // 2. Mặc định nếu không có product_recommen hoặc giá trị không chính xác:
            // Lấy 50% thời trang nam và 50% thời trang nữ
            return self::getFallbackNamNuProducts($limit);
        });
    }

    /**
     * Lấy 50% sản phẩm Thời trang nam và 50% sản phẩm Thời trang nữ
     *
     * @param int $limit
     * @return Collection
     */
    protected static function getFallbackNamNuProducts(int $limit): Collection
    {
        $halfLimit = (int) ceil($limit / 2);

        // Lấy ID danh mục nam & nữ
        $menCatId = (int) Cache::remember('cat_slug_id_thoi_trang_nam', 86400, fn() => 
            Category::where('slug', 'thoi-trang-nam')->value('id') ?? 0
        );

        $womenCatId = (int) Cache::remember('cat_slug_id_thoi_trang_nu', 86400, fn() => 
            Category::where('slug', 'thoi-trang-nu')->value('id') ?? 0
        );

        $menProducts = collect();
        if ($menCatId > 0) {
            $menIds = self::getCategoryAndDescendantIds($menCatId);
            $menProducts = self::fetchProductsByCategoryIds($menIds, $halfLimit);
        }

        $womenProducts = collect();
        if ($womenCatId > 0) {
            $womenIds = self::getCategoryAndDescendantIds($womenCatId);
            $womenProducts = self::fetchProductsByCategoryIds($womenIds, $halfLimit);
        }

        // Xen kẽ 50/50: 1 nam - 1 nữ tạo trải nghiệm thị giác đa dạng
        $merged = collect();
        $maxCount = max($menProducts->count(), $womenProducts->count());
        for ($i = 0; $i < $maxCount; $i++) {
            if ($menProducts->has($i)) {
                $merged->push($menProducts->get($i));
            }
            if ($womenProducts->has($i)) {
                $merged->push($womenProducts->get($i));
            }
        }

        // Nếu tổng 2 nhóm chưa đủ $limit (do ít sản phẩm), lấy thêm các sản phẩm active khác bù vào
        if ($merged->count() < $limit) {
            $excludeIds = $merged->pluck('id')->all();
            $fillers = Product::active()
                ->whereNotIn('id', $excludeIds)
                ->with([
                    'primaryImage' => fn($q) => $q->select(['id', 'product_id', 'url']),
                    'brand' => fn($q) => $q->select(['id', 'name', 'slug'])
                ])
                ->select([
                    'id', 'sku', 'name', 'slug', 'price', 'sale_price', 
                    'brand_id', 'primary_category_id', 'category_ids', 'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->limit($limit - $merged->count())
                ->get();

            $merged = $merged->merge($fillers);
        }

        return $merged->slice(0, $limit)->values();
    }

    /**
     * Query sản phẩm theo danh sách ID danh mục tối ưu tốc độ
     *
     * @param array $categoryIds
     * @param int $limit
     * @return Collection
     */
    protected static function fetchProductsByCategoryIds(array $categoryIds, int $limit): Collection
    {
        if (empty($categoryIds)) {
            return collect();
        }

        return Product::active()
            ->inCategory($categoryIds)
            ->with([
                'primaryImage' => fn($q) => $q->select(['id', 'product_id', 'url']),
                'brand' => fn($q) => $q->select(['id', 'name', 'slug'])
            ])
            ->select([
                'id', 'sku', 'name', 'slug', 'price', 'sale_price', 
                'brand_id', 'primary_category_id', 'category_ids', 'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Lấy ID của danh mục và tất cả danh mục con cháu
     *
     * @param int $categoryId
     * @return array
     */
    public static function getCategoryAndDescendantIds(int $categoryId): array
    {
        return Cache::remember("cat_descendant_ids_{$categoryId}", 3600, function () use ($categoryId) {
            $allIds = [$categoryId];
            $queue = [$categoryId];

            while (!empty($queue)) {
                $parentId = array_shift($queue);
                $childIds = Category::where('parent_id', $parentId)->pluck('id')->all();
                foreach ($childIds as $cId) {
                    $cId = (int) $cId;
                    if (!in_array($cId, $allIds, true)) {
                        $allIds[] = $cId;
                        $queue[] = $cId;
                    }
                }
            }

            return $allIds;
        });
    }

    /**
     * Xóa cache gợi ý khi cập nhật sản phẩm / category / setting
     */
    public static function clearCache(): void
    {
        Cache::forget('cat_slug_id_thoi_trang_nam');
        Cache::forget('cat_slug_id_thoi_trang_nu');
        for ($limit = 4; $limit <= 20; $limit += 2) {
            Cache::forget("products_recommended_v2_0_{$limit}");
        }
        Cache::forget('settings');
    }
}
