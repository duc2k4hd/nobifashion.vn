<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSlugRedirect;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductDetailController extends Controller
{
    public function index($slug)
    {
        $product = $this->findActiveProductBySlug($slug);

        // Nếu không tìm thấy product với slug hiện tại, kiểm tra redirect
        if (! $product) {
            $finalSlug = $this->resolveRedirectSlug($slug);

            if ($finalSlug && $finalSlug !== $slug) {
                return redirect()->route('client.product.detail', ['slug' => $finalSlug], 301);
            }

            return view('clients.pages.errors.404');
        }

        $vouchers = Cache::remember('product-detail:vouchers:v1', now()->addMinutes(10), function () {
            return Voucher::query()
                ->active()
                ->select([
                    'id',
                    'code',
                    'type',
                    'value',
                    'min_order_amount',
                    'max_discount_amount',
                    'created_at',
                ])
                ->latest('id')
                ->limit(4)
                ->get();
        });

        $productNew = Cache::remember('product-detail:new-products:v2', now()->addMinutes(10), function () {
            return Product::query()
                ->active()
                ->select(['id', 'name', 'slug', 'price', 'sale_price', 'created_at'])
                ->with(['primaryImage:id,product_id,url,alt,title'])
                ->latest('id')
                ->limit(9)
                ->get();
        });

        $productRelated = Cache::remember("product-detail:related:{$product->id}:v1", now()->addMinutes(10), function () use ($product) {
            return Product::query()->related($product);
        });

        $detailData = $this->buildDetailViewData($product, $vouchers);

        return view('clients.pages.single.index', array_merge(
            [
                'product' => $product,
                'vouchers' => $vouchers,
                'productNew' => $productNew,
                'productRelated' => $productRelated,
            ],
            $detailData
        ));
    }

    protected function findActiveProductBySlug(string $slug): ?Product
    {
        return Product::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'primaryImage:id,product_id,url,alt,title,is_primary,thumbnail_url',
                'images:id,product_id,url,alt,title,is_primary,order',
                'brand:id,name,slug',
                'primaryCategory:id,name,slug,parent_id',
                'tags:id,name,entity_id,entity_type',
                'faqs' => fn ($query) => $this->constrainProductFaqsRelation($query),
                'howTos' => fn ($query) => $this->constrainProductHowTosRelation($query),
                'variants:id,product_id,price,sale_price,stock_quantity,attributes,image_id,is_active',
                'variants.primaryVariantImage:id,url,thumbnail_url',
                'currentFlashSaleItem.flashSale:id,title,end_time,is_active,status,start_time',
            ])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDetailViewData(Product $product, Collection $vouchers): array
    {
        $breadcrumbPath = $this->buildBreadcrumbPath($product->primaryCategory);
        $flashSaleItem = $product->currentFlashSaleItem;
        $currentFlashSale = $flashSaleItem?->flashSale;
        $hasFlashSale = $flashSaleItem !== null && $currentFlashSale !== null;
        $item = $flashSaleItem ?? $product;

        $original = (float) ($item->original_price ?? ($item->price ?? 0));
        $sale = (float) ($item->sale_price ?? 0);
        $displayCurrentPrice = $original > 0 && $sale > 0 && $sale < $original ? $sale : $original;
        $displayOriginalPrice = $sale > 0 && $sale < $original ? $original : null;
        $discountPercent = $displayOriginalPrice
            ? (int) round((($displayOriginalPrice - $displayCurrentPrice) / $displayOriginalPrice) * 100)
            : null;
        $savedAmount = $displayOriginalPrice ? max(0, $displayOriginalPrice - $displayCurrentPrice) : 0;

        $galleryImages = $product->images->isNotEmpty()
            ? $product->images
            : collect([$product->primaryImage])->filter();

        $variantData = $this->buildVariantViewData($product);
        $voucherData = $this->buildVoucherViewData($vouchers, $displayCurrentPrice);
        $stockData = $this->buildStockViewData($product, $variantData['variants']);

        $flashSaleStock = max(1, (int) ($flashSaleItem->stock ?? 0));
        $flashSaleSold = max(0, (int) ($flashSaleItem->sold ?? 0));
        $flashSalePercent = $flashSaleItem
            ? min(100, (int) round(($flashSaleSold / max(1, $flashSaleStock)) * 100))
            : 0;

        return array_merge([
            'breadcrumbPath' => $breadcrumbPath,
            'hasFlashSale' => $hasFlashSale,
            'currentFlashSale' => $currentFlashSale,
            'item' => $item,
            'original' => $original,
            'sale' => $sale,
            'displayCurrentPrice' => $displayCurrentPrice,
            'displayOriginalPrice' => $displayOriginalPrice,
            'discountPercent' => $discountPercent,
            'savedAmount' => $savedAmount,
            'galleryImages' => $galleryImages,
            'voucherItems' => $vouchers,
            'flashSaleStock' => $flashSaleStock,
            'flashSaleSold' => $flashSaleSold,
            'flashSalePercent' => $flashSalePercent,
        ], $variantData, $voucherData, $stockData);
    }

    protected function buildBreadcrumbPath($category): Collection
    {
        $breadcrumbPath = collect();

        while ($category) {
            $breadcrumbPath->prepend($category);
            $category->loadMissing('parent');
            $category = $category->parent;
        }

        return $breadcrumbPath;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildVariantViewData(Product $product): array
    {
        $variants = $product->variants ?? collect();
        $attributeLabels = [
            'size' => 'Kích thước',
            'color' => 'Màu sắc',
            'weight' => 'Cân nặng',
            'material' => 'Chất liệu',
            'materials' => 'Chất liệu',
            'type' => 'Kiểu dáng',
            'types' => 'Kiểu dáng',
        ];

        $normalizedVariants = $variants->map(function ($variantItem) {
            $attrs = is_string($variantItem->attributes)
                ? json_decode($variantItem->attributes, true)
                : ($variantItem->attributes ?? []);
            $variantImage = optional($variantItem->primaryVariantImage);

            return [
                'id' => $variantItem->id,
                'stock' => (int) ($variantItem->stock_quantity ?? 0),
                'price' => (float) ($variantItem->price ?? 0),
                'attrs' => is_array($attrs) ? $attrs : [],
                'image_url' => $variantImage->url ?? $variantImage->thumbnail_url ?? null,
            ];
        })->values();

        $attributeOptionMap = [];
        foreach ($normalizedVariants as $variant) {
            foreach (($variant['attrs'] ?? []) as $key => $value) {
                $optionValue = trim((string) $value);
                if ($optionValue === '') {
                    continue;
                }

                if (! isset($attributeOptionMap[$key][$optionValue])) {
                    $attributeOptionMap[$key][$optionValue] = 0;
                }

                $attributeOptionMap[$key][$optionValue] += (int) ($variant['stock'] ?? 0);
            }
        }

        $variantGroups = collect($attributeOptionMap)
            ->map(function (array $options, string $key) use ($attributeLabels) {
                $normalizedKey = Str::lower($key);

                return [
                    'key' => $key,
                    'normalized_key' => $normalizedKey,
                    'label' => $attributeLabels[$normalizedKey] ?? ucfirst($key),
                    'option_class' => match ($normalizedKey) {
                        'size' => 'size-option',
                        'color' => 'color-option',
                        'material', 'materials' => 'material-option',
                        'weight' => 'weight-option',
                        default => 'type-option',
                    },
                    'type' => match ($normalizedKey) {
                        'color' => 'color',
                        'size' => 'size',
                        default => 'option',
                    },
                    'values' => collect($options)
                        ->map(function (int $stock, string $value) use ($normalizedKey) {
                            return [
                                'value' => $value,
                                'stock' => $stock,
                                'disabled' => $stock <= 0,
                                'swatch' => $normalizedKey === 'color'
                                    ? $this->resolveSwatchColor($value)
                                    : null,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return [
            'variants' => $variants,
            'variantGroups' => $variantGroups,
            'variantsJson' => $normalizedVariants->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildVoucherViewData(Collection $vouchers, float $displayCurrentPrice): array
    {
        $bestVoucherPrice = null;
        foreach ($vouchers as $voucher) {
            $minOrder = (float) ($voucher->min_order_amount ?? 0);
            if ($minOrder > 0 && $displayCurrentPrice < $minOrder) {
                continue;
            }

            $discount = 0;
            if (($voucher->type ?? '') === 'percentage') {
                $discount = $displayCurrentPrice * ((float) ($voucher->value ?? 0) / 100);
                $maxDiscount = (float) ($voucher->max_discount_amount ?? 0);
                if ($maxDiscount > 0) {
                    $discount = min($discount, $maxDiscount);
                }
            } elseif (($voucher->type ?? '') === 'fixed_amount') {
                $discount = (float) ($voucher->value ?? 0);
            }

            if ($discount > 0) {
                $candidate = max(0, $displayCurrentPrice - $discount);
                $bestVoucherPrice = $bestVoucherPrice === null ? $candidate : min($bestVoucherPrice, $candidate);
            }
        }

        $voucherCards = $vouchers->take(5)->map(function ($voucher) {
            $type = $voucher->type ?? '';
            $value = (float) ($voucher->value ?? 0);

            return [
                'code' => $voucher->code ?? '',
                'icon' => $type === 'free_ship' ? '🚚' : '%',
                'accent' => $type === 'free_ship' ? '#1a73e8' : '#e5252a',
                'label' => match ($type) {
                    'free_ship' => 'FreeShip',
                    'percentage' => 'Giảm ' . number_format($value, 0, ',', '.') . '%',
                    'fixed_amount' => 'Giảm ' . number_format($value, 0, ',', '.') . 'đ',
                    default => $voucher->code ?? 'Ưu đãi',
                },
            ];
        });

        return [
            'bestVoucherPrice' => $bestVoucherPrice,
            'voucherCards' => $voucherCards,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\ProductVariant>  $variants
     * @return array<string, mixed>
     */
    protected function buildStockViewData(Product $product, Collection $variants): array
    {
        $defaultStockValue = $variants->isNotEmpty()
            ? (int) ($variants->max('stock_quantity') ?? 0)
            : (int) ($product->stock_quantity ?? 0);
        $defaultStockBase = max(1, $defaultStockValue);
        $defaultStockPercent = min(100, max(8, (int) round(($defaultStockValue / $defaultStockBase) * 100)));
        $defaultStockNote = $variants->isNotEmpty()
            ? 'Chọn đủ thuộc tính để xem tồn kho chính xác.'
            : ($defaultStockValue > 0 ? 'Sản phẩm đang sẵn hàng, có thể đặt mua ngay.' : 'Sản phẩm đang tạm hết hàng.');

        return [
            'defaultStockValue' => $defaultStockValue,
            'defaultStockBase' => $defaultStockBase,
            'defaultStockPercent' => $defaultStockPercent,
            'defaultStockNote' => $defaultStockNote,
        ];
    }

    protected function resolveSwatchColor(?string $value): string
    {
        $colorHexMap = [
            'trang' => '#f5f5f4',
            'white' => '#f5f5f4',
            'den' => '#111827',
            'black' => '#111827',
            'xam' => '#9ca3af',
            'grey' => '#9ca3af',
            'gray' => '#9ca3af',
            'xanh' => '#2563eb',
            'blue' => '#2563eb',
            'navy' => '#1e3a8a',
            'do' => '#dc2626',
            'red' => '#dc2626',
            'hong' => '#ec4899',
            'pink' => '#ec4899',
            'vang' => '#f59e0b',
            'yellow' => '#f59e0b',
            'be' => '#d6b58a',
            'kem' => '#f3e8d0',
            'nau' => '#8b5e3c',
            'brown' => '#8b5e3c',
            'xanh la' => '#15803d',
            'green' => '#15803d',
            'olive' => '#556b2f',
            'cam' => '#f97316',
            'orange' => '#f97316',
            'tim' => '#7c3aed',
            'purple' => '#7c3aed',
        ];

        $normalized = mb_strtolower(Str::ascii(trim((string) $value)));
        foreach ($colorHexMap as $keyword => $hex) {
            if (str_contains($normalized, $keyword)) {
                return $hex;
            }
        }

        return 'linear-gradient(135deg, #e5e7eb 0%, #9ca3af 100%)';
    }

    protected function constrainProductFaqsRelation($query): void
    {
        $columns = ['id', 'product_id', 'question', 'answer'];

        if ($this->tableHasColumn('product_faqs', 'is_active')) {
            $query->where('is_active', true);
            $columns[] = 'is_active';
        }

        $query->select($columns);
    }

    protected function constrainProductHowTosRelation($query): void
    {
        $columns = ['id', 'product_id', 'title', 'description', 'supplies', 'steps'];

        if ($this->tableHasColumn('product_how_tos', 'is_active')) {
            $query->where('is_active', true);
            $columns[] = 'is_active';
        }

        $query->select($columns);
    }

    protected function tableHasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        return $cache[$key] = Schema::hasColumn($table, $column);
    }

    /**
     * Giải quyết redirect slug, xử lý trường hợp redirect nhiều lần
     * Ví dụ: slug1 -> slug2 -> slug3, sẽ trả về slug3
     */
    private function resolveRedirectSlug(string $slug, int $maxDepth = 10): ?string
    {
        $currentSlug = $slug;
        $visited = [];
        $depth = 0;

        while ($depth < $maxDepth) {
            if (in_array($currentSlug, $visited, true)) {
                break;
            }

            $visited[] = $currentSlug;

            $redirect = ProductSlugRedirect::where('old_slug', $currentSlug)->first();

            if (! $redirect) {
                return $currentSlug;
            }

            $product = Product::where('slug', $redirect->new_slug)->active()->first();
            if ($product) {
                return $redirect->new_slug;
            }

            $currentSlug = $redirect->new_slug;
            $depth++;
        }

        return null;
    }
}
