<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class HomeController extends Controller
{
    public function index()
    {
        $rootCategories = $this->resolveRootCategories();
        $menCategoryIds = $this->resolveBranchCategoryIds($rootCategories, 'thoi-trang-nam');
        $womenCategoryIds = $this->resolveBranchCategoryIds($rootCategories, 'thoi-trang-nu');
        $kidsCategoryIds = $this->resolveBranchCategoryIds($rootCategories, 'tre-em');
        $householdCategoryIds = $this->resolveBranchCategoryIds($rootCategories, 'do-gia-dung');

        $homeData = Cache::remember('home.page.payload.v3', now()->addMinutes(10), function () use (
            $rootCategories,
            $menCategoryIds,
            $womenCategoryIds,
            $kidsCategoryIds,
            $householdCategoryIds
        ) {
            $clothingCategoryIds = array_values(array_unique(array_merge(
                $menCategoryIds,
                $womenCategoryIds,
                $kidsCategoryIds
            )));

            return [
                'banners' => Banner::query()
                    ->select(['id', 'title', 'image_desktop', 'order'])
                    ->home()
                    ->active()
                    ->ordered()
                    ->get(),
                'home_banner' => Banner::query()
                    ->select(['id', 'title', 'image_desktop', 'link', 'taget'])
                    ->where('position', 'home_banner')
                    ->active()
                    ->ordered()
                    ->limit(2)
                    ->get(),
                'productsFeatured' => $this->baseHomeProductQuery()
                    ->featured()
                    ->orderByDesc('id')
                    ->limit(18)
                    ->get(),
                'productClothing' => $this->baseHomeProductQuery()
                    ->when($clothingCategoryIds !== [], function (Builder $query) use ($clothingCategoryIds) {
                        $query->inCategory($clothingCategoryIds);
                    })
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get(),
                'menProducts' => $this->loadHomeProductsByCategoryIds($menCategoryIds, 18),
                'womenProducts' => $this->loadHomeProductsByCategoryIds($womenCategoryIds, 18),
                'sportProducts' => $this->loadHomeProductsByCategoryIds($householdCategoryIds, 18),
                'featuredCategoryCounts' => $this->buildHomeCategoryProductCounts($rootCategories),
            ];
        });

        $flashSale = Cache::remember('home.flash_sale.payload.v2', now()->addMinute(), function () {
            return FlashSale::query()
                ->select(['id', 'title', 'start_time', 'end_time'])
                ->where('is_active', true)
                ->where('status', 'active')
                ->where('start_time', '<=', now())
                ->where('end_time', '>=', now())
                ->orderByDesc('start_time')
                ->with([
                    'items' => function ($query) {
                        $query->select([
                            'id',
                            'flash_sale_id',
                            'product_id',
                            'original_price',
                            'sale_price',
                            'stock',
                            'sold',
                            'is_active',
                            'sort_order',
                        ])
                            ->where('is_active', true)
                            ->whereRaw('stock > sold')
                            ->whereHas('product', function ($productQuery) {
                                $productQuery->where('is_active', true)
                                    ->where('stock_quantity', '>', 0);
                            })
                            ->orderBy('sort_order')
                            ->orderBy('id');
                    },
                    'items.product' => function ($productQuery) {
                        $productQuery->select([
                            'id',
                            'name',
                            'slug',
                            'price',
                            'sale_price',
                            'stock_quantity',
                            'is_active',
                            'primary_category_id',
                        ])
                            ->where('is_active', true)
                            ->where('stock_quantity', '>', 0);
                    },
                    'items.product.primaryImage:id,product_id,url,alt,title',
                    'items.product.primaryCategory:id,name',
                ])
                ->first();
        });

        return view('clients.pages.home.index', $homeData + [
            'flashSale' => $flashSale,
            'flashSaleEndsAtMs' => $flashSale?->end_time?->valueOf(),
        ]);
    }

    protected function resolveRootCategories(): Collection
    {
        $sharedCategories = View::shared('categories');

        if ($sharedCategories instanceof Collection) {
            return $sharedCategories;
        }

        return Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with([
                'children' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->with([
                            'children' => function ($subQuery) {
                                $subQuery->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->orderBy('name');
                            },
                        ]);
                },
            ])
            ->get();
    }

    protected function baseHomeProductQuery(): Builder
    {
        return Product::query()
            ->active()
            ->select([
                'id',
                'name',
                'slug',
                'price',
                'sale_price',
                'is_featured',
                'primary_category_id',
            ])
            ->with([
                'primaryImage:id,product_id,url,alt,title',
                'primaryCategory:id,name',
            ]);
    }

    protected function loadHomeProductsByCategoryIds(array $categoryIds, int $limit): Collection
    {
        return $this->baseHomeProductQuery()
            ->when($categoryIds !== [], function (Builder $query) use ($categoryIds) {
                $query->inCategory($categoryIds);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    protected function resolveBranchCategoryIds(Collection $rootCategories, string $rootSlug): array
    {
        $rootCategory = $rootCategories->firstWhere('slug', $rootSlug);

        if (! $rootCategory) {
            return [];
        }

        $ids = collect([$rootCategory->id]);

        foreach ($rootCategory->children ?? [] as $child) {
            $ids->push($child->id);

            foreach ($child->children ?? [] as $grandChild) {
                $ids->push($grandChild->id);
            }
        }

        return $ids
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function buildHomeCategoryProductCounts(Collection $rootCategories): array
    {
        $childCategoryIds = $rootCategories
            ->flatMap(fn ($category) => $category->children->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($childCategoryIds === []) {
            return [];
        }

        return Cache::remember('home.featured_category_counts.v2', now()->addMinutes(15), function () use ($childCategoryIds) {
            $counts = array_fill_keys($childCategoryIds, 0);

            Product::query()
                ->active()
                ->select(['id', 'primary_category_id', 'category_ids'])
                ->where(function (Builder $query) use ($childCategoryIds) {
                    $query->whereIn('primary_category_id', $childCategoryIds);

                    foreach ($childCategoryIds as $categoryId) {
                        $query->orWhereRaw('JSON_CONTAINS(category_ids, ?)', ['"' . (string) $categoryId . '"']);
                    }
                })
                ->chunkById(500, function ($products) use (&$counts) {
                    foreach ($products as $product) {
                        $matchedCategoryIds = [];

                        $primaryCategoryId = (int) $product->primary_category_id;
                        if ($primaryCategoryId > 0 && array_key_exists($primaryCategoryId, $counts)) {
                            $matchedCategoryIds[$primaryCategoryId] = true;
                        }

                        foreach ((array) $product->category_ids as $categoryId) {
                            $categoryId = (int) $categoryId;
                            if ($categoryId > 0 && array_key_exists($categoryId, $counts)) {
                                $matchedCategoryIds[$categoryId] = true;
                            }
                        }

                        foreach (array_keys($matchedCategoryIds) as $categoryId) {
                            $counts[$categoryId]++;
                        }
                    }
                });

            return $counts;
        });
    }
}
