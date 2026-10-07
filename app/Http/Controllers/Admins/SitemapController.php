<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\SitemapConfig;
use App\Models\SitemapExclude;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Display sitemap management dashboard
     */
    public function index()
    {
        $configKeys = [
            'enabled',
            'posts_enabled',
            'products_enabled',
            'categories_enabled',
            'brands_enabled',
            'tags_enabled',
            'pages_enabled',
            'images_enabled',
            'ping_google_enabled',
            'ping_bing_enabled',
            'urls_per_file',
        ];

        $configs = [];
        foreach ($configKeys as $key) {
            $default = in_array($key, ['images_enabled'], true) ? false : true;
            if ($key === 'urls_per_file') {
                $default = 10000;
            }

            $configs[$key] = SitemapConfig::getValue($key, $default);
        }

        $excludes = SitemapExclude::orderByDesc('id')->get();

        $stats = [
            'total_urls' => $this->getTotalUrls(),
            'last_generated' => SitemapConfig::getValue('last_generated_at'),
            'cache_enabled' => config('sitemap.cache.enabled', true),
        ];

        $siteName = config('app.name');
        $siteUrl = config('app.url');
        $sitemapUrl = route('admin.sitemap.index');
        $sitemapXmlUrl = url('/sitemap.xml');

        $metaTitle = 'Sitemap - '.$siteName;
        $metaDescription = 'Sitemap của '.$siteName.' - Tìm kiếm và khám phá tất cả các trang, sản phẩm, bài viết và danh mục trên website của chúng tôi.';
        $metaKeywords = 'sitemap, '.$siteName.', bản đồ trang web, tìm kiếm nội dung';

        $sitemaps = [
            [
                'name' => 'Sitemap Index',
                'description' => 'Tổng hợp tất cả các sitemap con (posts, products, categories, tags, pages, images).',
                'url' => $sitemapXmlUrl,
                'icon' => '🗂️',
            ],
            [
                'name' => 'Bài viết (Posts)',
                'description' => 'Danh sách tất cả bài viết đã xuất bản trên website.',
                'url' => url('/sitemap-posts.xml'),
                'icon' => '📝',
            ],
            [
                'name' => 'Sản phẩm (Products)',
                'description' => 'Danh sách các sản phẩm đang hiển thị.',
                'url' => url('/sitemap-products.xml'),
                'icon' => '🛒',
            ],
            [
                'name' => 'Danh mục (Categories)',
                'description' => 'Danh sách các danh mục chính trên website.',
                'url' => url('/sitemap-categories.xml'),
                'icon' => '📂',
            ],
            [
                'name' => 'Thương hiệu (Brands)',
                'description' => 'Danh sách tất cả các thương hiệu chính hãng trên website.',
                'url' => url('/sitemap-brands.xml'),
                'icon' => '🏷️',
            ],
            [
                'name' => 'Tags - Sản phẩm',
                'description' => 'Danh sách các thẻ sản phẩm.',
                'url' => url('/sitemap-tags-products.xml'),
                'icon' => '🏷️',
            ],
            [
                'name' => 'Tags - Bài viết',
                'description' => 'Danh sách các thẻ bài viết.',
                'url' => url('/sitemap-tags-posts.xml'),
                'icon' => '🏷️',
            ],
            [
                'name' => 'Trang tĩnh (Pages)',
                'description' => 'Các trang tĩnh quan trọng như giới thiệu, chính sách, điều khoản...',
                'url' => url('/sitemap-pages.xml'),
                'icon' => '📄',
            ],
            [
                'name' => 'Hình ảnh (Images)',
                'description' => 'Danh sách các hình ảnh quan trọng được dùng trong website.',
                'url' => url('/sitemap-images.xml'),
                'icon' => '🖼️',
            ],
        ];

        return view('admins.sitemap.index', compact(
            'configs',
            'excludes',
            'stats',
            'sitemaps',
            'siteName',
            'siteUrl',
            'sitemapUrl',
            'sitemapXmlUrl',
            'metaTitle',
            'metaDescription',
            'metaKeywords'
        ));
    }

    /**
     * Update sitemap configuration
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'enabled' => 'nullable|boolean',
            'posts_enabled' => 'nullable|boolean',
            'products_enabled' => 'nullable|boolean',
            'categories_enabled' => 'nullable|boolean',
            'brands_enabled' => 'nullable|boolean',
            'tags_enabled' => 'nullable|boolean',
            'pages_enabled' => 'nullable|boolean',
            'images_enabled' => 'nullable|boolean',
            'ping_google_enabled' => 'nullable|boolean',
            'ping_bing_enabled' => 'nullable|boolean',
            'urls_per_file' => 'nullable|integer|min:1|max:50000',
        ]);

        $toggleKeys = [
            'enabled',
            'posts_enabled',
            'products_enabled',
            'categories_enabled',
            'brands_enabled',
            'tags_enabled',
            'pages_enabled',
            'images_enabled',
            'ping_google_enabled',
            'ping_bing_enabled',
        ];

        foreach ($toggleKeys as $key) {
            SitemapConfig::setValue($key, $request->boolean($key), 'boolean');
        }

        if ($request->has('urls_per_file')) {
            SitemapConfig::setValue('urls_per_file', $request->urls_per_file, 'integer');
        }

        $this->sitemapService->clearCache();

        return redirect()->route('admin.sitemap.index')
            ->with('success', 'Cấu hình sitemap đã được cập nhật.');
    }

    /**
     * Rebuild sitemap
     */
    public function rebuild()
    {
        try {
            $this->sitemapService->rebuild();

            // Ping search engines if enabled
            if (SitemapConfig::getValue('ping_google_enabled', true) ||
                SitemapConfig::getValue('ping_bing_enabled', true)) {
                $pingResults = $this->sitemapService->pingSearchEngines();
            }

            return redirect()->route('admin.sitemap.index')
                ->with('success', 'Sitemap đã được tạo lại thành công.');
        } catch (\Exception $e) {
            return redirect()->route('admin.sitemap.index')
                ->with('error', 'Lỗi khi tạo lại sitemap: '.$e->getMessage());
        }
    }

    /**
     * Clear sitemap cache
     */
    public function clearCache()
    {
        $this->sitemapService->clearCache();

        return redirect()->route('admin.sitemap.index')
            ->with('success', 'Cache sitemap đã được xóa.');
    }

    /**
     * Preview sitemap XML
     */
    public function preview(Request $request)
    {
        $type = $request->get('type', 'index');
        $page = (int) $request->get('page', 1);

        try {
            $xml = match ($type) {
                'index' => $this->sitemapService->generateIndex(),
                'posts' => $this->sitemapService->generatePosts($page),
                'products' => $this->sitemapService->generateProducts($page),
                'categories' => $this->sitemapService->generateCategories(),
                'brands' => $this->sitemapService->generateBrands(),
                'tags-products' => $this->sitemapService->generateTagsProducts(),
                'tags-posts' => $this->sitemapService->generateTagsPosts(),
                'pages' => $this->sitemapService->generatePages(),
                'images' => $this->sitemapService->generateImages(),
                default => throw new \InvalidArgumentException('Invalid sitemap type'),
            };

            return response($xml, 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Ping search engines
     */
    public function ping()
    {
        try {
            $results = $this->sitemapService->pingSearchEngines();

            $message = 'Đã ping search engines: ';
            $messages = [];
            if (isset($results['google'])) {
                $messages[] = 'Google: '.($results['google']['success'] ? 'Thành công' : 'Thất bại');
            }
            if (isset($results['bing'])) {
                $messages[] = 'Bing: '.($results['bing']['success'] ? 'Thành công' : 'Thất bại');
            }

            return redirect()->route('admin.sitemap.index')
                ->with('success', $message.implode(', ', $messages));
        } catch (\Exception $e) {
            return redirect()->route('admin.sitemap.index')
                ->with('error', 'Lỗi khi ping search engines: '.$e->getMessage());
        }
    }

    /**
     * Store exclude rule
     */
    public function storeExclude(Request $request)
    {
        $request->validate([
            'type' => 'required|in:url,post_id,product_id,category_id,brand_id,pattern',
            'value' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        SitemapExclude::create([
            'type' => $request->type,
            'value' => $request->value,
            'description' => $request->description,
            'is_active' => true,
        ]);

        $this->sitemapService->clearCache();

        return redirect()->route('admin.sitemap.index')
            ->with('success', 'Quy tắc loại trừ đã được thêm.');
    }

    /**
     * Delete exclude rule
     */
    public function deleteExclude($id)
    {
        $exclude = SitemapExclude::findOrFail($id);
        $exclude->delete();

        $this->sitemapService->clearCache();

        return redirect()->route('admin.sitemap.index')
            ->with('success', 'Quy tắc loại trừ đã được xóa.');
    }

    /**
     * Toggle exclude rule status
     */
    public function toggleExclude($id)
    {
        $exclude = SitemapExclude::findOrFail($id);
        $exclude->is_active = ! $exclude->is_active;
        $exclude->save();

        $this->sitemapService->clearCache();

        return redirect()->route('admin.sitemap.index')
            ->with('success', 'Trạng thái quy tắc đã được cập nhật.');
    }

    /**
     * Get total URLs count
     */
    protected function getTotalUrls(): int
    {
        $total = 0;

        if (SitemapConfig::getValue('posts_enabled', true)) {
            $total += \App\Models\Post::where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->count();
        }
        if (SitemapConfig::getValue('products_enabled', true)) {
            $total += \App\Models\Product::where('is_active', true)->count();
        }
        if ($SitemapCategoriesEnabled = SitemapConfig::getValue('categories_enabled', true)) {
            $total += \App\Models\Category::where('is_active', true)->count();
        }
        if (SitemapConfig::getValue('brands_enabled', true)) {
            $total += \App\Models\Brand::where('is_active', true)->count() + 1; // Số brand + 1 trang danh sách /brands
        }
        if (SitemapConfig::getValue('tags_enabled', true)) {
            $total += \App\Models\Tag::where('is_active', true)->count();
        }
        if (SitemapConfig::getValue('pages_enabled', true)) {
            $total += 1; // Homepage
        }

        return $total;
    }
}
