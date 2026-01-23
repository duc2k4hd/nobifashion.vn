<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\SitemapConfig;
use App\Models\SitemapExclude;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class SitemapService
{
    protected array $excludes = [];

    public function __construct()
    {
        $this->excludes = $this->loadExcludes();
    }

    /**
     * Clear cached sitemap fragments.
     */
    public function clearCache(): void
    {
        $listKey = $this->cacheKey('keys');
        $keys = Cache::pull($listKey, []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Rebuild sitemap cache and update timestamp.
     */
    public function rebuild(): void
    {
        $this->clearCache();

        // Generate index trước để tính toán số pages
        $this->generateIndex();

        // Generate tất cả pages cho posts
        if ($this->isEnabled('posts')) {
            $postCount = $this->postsQuery()->count();
            foreach ($this->paginate($postCount) as $page) {
                $this->generatePosts($page);
            }
        }

        // Generate tất cả pages cho products
        if ($this->isEnabled('products')) {
            $productCount = $this->productsQuery()->count();
            foreach ($this->paginate($productCount) as $page) {
                $this->generateProducts($page);
            }
        }

        if ($this->isEnabled('categories')) {
            $this->generateCategories();
        }

        if ($this->isEnabled('tags')) {
            $this->generateTagsProducts();
            $this->generateTagsPosts();
        }

        if ($this->isEnabled('pages')) {
            $this->generatePages();
        }

        if ($this->isEnabled('images')) {
            $this->generateImages();
        }

        SitemapConfig::setValue('last_generated_at', now(), 'datetime');
    }

    /**
     * Ping Google/Bing after rebuilding.
     */
    public function pingSearchEngines(): array
    {
        $results = [];
        $sitemapUrl = URL::to('/sitemap.xml');

        if (SitemapConfig::getValue('ping_google_enabled', true)) {
            $response = Http::get('https://www.google.com/ping', ['sitemap' => $sitemapUrl]);
            $results['google'] = [
                'success' => $response->successful(),
                'status' => $response->status(),
            ];
        }

        if (SitemapConfig::getValue('ping_bing_enabled', true)) {
            $response = Http::get('https://www.bing.com/ping', ['sitemap' => $sitemapUrl]);
            $results['bing'] = [
                'success' => $response->successful(),
                'status' => $response->status(),
            ];
        }

        return $results;
    }

    public function generateIndex(): string
    {
        if (! SitemapConfig::getValue('enabled', true)) {
            return $this->buildIndex([]);
        }

        return $this->remember('index', function () {
            $entries = [];
            $seenUrls = []; // Track URLs để tránh duplicate
            $baseUrl = URL::to('/');
            $lastmod = Carbon::now()->toAtomString();

            if ($this->isEnabled('posts')) {
                $count = $this->postsQuery()->count();
                foreach ($this->paginate($count) as $page) {
                    foreach ($this->buildPageUrls('posts', $page) as $loc) {
                        // Chỉ thêm nếu chưa có trong danh sách
                        if (! in_array($loc, $seenUrls, true)) {
                            $entries[] = [
                                'loc' => $loc,
                                'lastmod' => $lastmod,
                            ];
                            $seenUrls[] = $loc;
                        }
                    }
                }
            }

            if ($this->isEnabled('products')) {
                $count = $this->productsQuery()->count();
                foreach ($this->paginate($count) as $page) {
                    foreach ($this->buildPageUrls('products', $page) as $loc) {
                        // Chỉ thêm nếu chưa có trong danh sách
                        if (! in_array($loc, $seenUrls, true)) {
                            $entries[] = [
                                'loc' => $loc,
                                'lastmod' => $lastmod,
                            ];
                            $seenUrls[] = $loc;
                        }
                    }
                }
            }

            if ($this->isEnabled('categories')) {
                $entries[] = [
                    'loc' => url('/sitemap-categories.xml'),
                    'lastmod' => $lastmod,
                ];
            }

            if ($this->isEnabled('tags')) {
                $entries[] = [
                    'loc' => url('/sitemap-tags-products.xml'),
                    'lastmod' => $lastmod,
                ];
                $entries[] = [
                    'loc' => url('/sitemap-tags-posts.xml'),
                    'lastmod' => $lastmod,
                ];
            }

            if ($this->isEnabled('pages')) {
                $entries[] = [
                    'loc' => url('/sitemap-pages.xml'),
                    'lastmod' => $lastmod,
                ];
            }

            if ($this->isEnabled('images')) {
                $entries[] = [
                    'loc' => url('/sitemap-images.xml'),
                    'lastmod' => $lastmod,
                ];
            }

            return $this->buildIndex($entries);
        });
    }

    public function generatePosts(int $page = 1): string
    {
        if (! $this->isEnabled('posts')) {
            return $this->buildUrlSet([]);
        }

        return $this->remember("posts_{$page}", function () use ($page) {
            $posts = $this->paginateQuery($this->postsQuery(), $page)->get();

            $urls = [];
            foreach ($posts as $post) {
                if ($this->isExcludedId('post_id', $post->id)) {
                    continue;
                }

                $loc = route('client.blog.show', $post->slug);
                if ($this->isUrlExcluded($loc)) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => optional($post->updated_at)->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            }

            return $this->buildUrlSet($urls);
        });
    }

    public function generateProducts(int $page = 1): string
    {
        if (! $this->isEnabled('products')) {
            return $this->buildUrlSet([]);
        }

        return $this->remember("products_{$page}", function () use ($page) {
            $products = $this->paginateQuery($this->productsQuery(), $page)->get();

            $urls = [];
            foreach ($products as $product) {
                if ($this->isExcludedId('product_id', $product->id)) {
                    continue;
                }

                // Ưu tiên dùng meta_canonical nếu có (URL chuẩn SEO), fallback route detail
                if (! empty($product->meta_canonical)) {
                    $loc = $product->meta_canonical;
                    // Nếu meta_canonical không phải full URL thì chuẩn hóa với base URL
                    if (! filter_var($loc, FILTER_VALIDATE_URL)) {
                        $base = rtrim(URL::to('/'), '/');
                        $loc = $base.'/'.ltrim($loc, '/');
                    }
                } else {
                    $loc = route('client.product.detail', $product->slug);
                }

                if ($this->isUrlExcluded($loc)) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => optional($product->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }

            return $this->buildUrlSet($urls);
        });
    }

    public function generateCategories(): string
    {
        if (! $this->isEnabled('categories')) {
            return $this->buildUrlSet([]);
        }

        return $this->remember('categories', function () {
            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $urls = [];
            foreach ($categories as $category) {
                if ($this->isExcludedId('category_id', $category->id)) {
                    continue;
                }

                $loc = url('/' . $category->slug);
                if ($this->isUrlExcluded($loc)) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => optional($category->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            }

            return $this->buildUrlSet($urls);
        });
    }

    public function generateTagsProducts(): string
    {
        if (! $this->isEnabled('tags')) {
            return $this->buildUrlSet([]);
        }

        return $this->remember('tags_products', function () {
            // Lấy tất cả tags được sử dụng trong products
            $tagIds = Product::query()
                ->where('is_active', true)
                ->whereNotNull('tag_ids')
                ->pluck('tag_ids')
                ->flatten()
                ->filter()
                ->unique()
                ->toArray();

            if (empty($tagIds)) {
                return $this->buildUrlSet([]);
            }

            $tags = Tag::query()
                ->where('is_active', true)
                ->whereIn('id', $tagIds)
                ->orderBy('id')
                ->get();

            $urls = [];
            foreach ($tags as $tag) {
                // Format: /cua-hang?tags=slug
                $loc = route('client.shop.index', ['tags' => $tag->slug]);
                if ($this->isUrlExcluded($loc)) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'changefreq' => 'weekly',
                    'priority' => '0.5',
                ];
            }

            return $this->buildUrlSet($urls);
        });
    }

    public function generateTagsPosts(): string
    {
        if (! $this->isEnabled('tags')) {
            return $this->buildUrlSet([]);
        }

        return $this->remember('tags_posts', function () {
            // Lấy tất cả tags được sử dụng trong posts
            $tagIds = Post::query()
                ->where('status', 'published')
                ->whereNotNull('tag_ids')
                ->pluck('tag_ids')
                ->flatten()
                ->filter()
                ->unique()
                ->toArray();

            if (empty($tagIds)) {
                return $this->buildUrlSet([]);
            }

            $tags = Tag::query()
                ->where('is_active', true)
                ->whereIn('id', $tagIds)
                ->orderBy('id')
                ->get();

            $urls = [];
            foreach ($tags as $tag) {
                $loc = url('/tu-dong-hoa?tags='.$tag->slug);
                if ($this->isUrlExcluded($loc)) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'changefreq' => 'weekly',
                    'priority' => '0.5',
                ];
            }

            return $this->buildUrlSet($urls);
        });
    }

    public function generatePages(): string
    {
        if (! $this->isEnabled('pages')) {
            return $this->buildUrlSet([]);
        }

        return $this->remember('pages', function () {
            $routeNames = [
                'client.home.index',
                'client.product.shop.index',
                'client.page.contact',
                'client.page.introduction',
                'client.policy.return',
                'client.policy.sale',
                'client.policy.warranty',
                'client.policy.terms',
                'client.policy.delivery',
                'client.policy.privacy',
                'client.policy.payment',
            ];

            $urls = [];
            foreach ($routeNames as $name) {
                try {
                    $loc = route($name);
                } catch (\Throwable $e) {
                    // Nếu route không tồn tại thì bỏ qua, tránh lỗi sitemap
                    continue;
                }

                if ($this->isUrlExcluded($loc)) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'priority' => '0.5',
                    'changefreq' => 'monthly',
                ];
            }

            return $this->buildUrlSet($urls);
        });
    }

    public function generateImages(): string
    {
        if (! $this->isEnabled('images')) {
            return $this->buildImageSet([]);
        }

        return $this->remember('images', function () {
            $items = [];

            // Products images
            Product::active()
                ->orderBy('id')
                ->chunk(200, function ($products) use (&$items) {
                    Product::preloadImages($products);

                    foreach ($products as $product) {
                        $loc = route('client.product.detail', $product->slug);

                        if ($this->isUrlExcluded($loc) || $this->isExcludedId('product_id', $product->id)) {
                            continue;
                        }

                        $images = [];
                        foreach ($product->images as $image) {
                            if (! $image->url) {
                                continue;
                            }

                            $images[] = [
                                'loc' => asset('clients/assets/img/clothes/'.$image->url),
                                'title' => $image->title ?: $product->name,
                                'caption' => $image->notes ?: null,
                            ];
                        }

                        if (! empty($images)) {
                            $items[] = [
                                'loc' => $loc,
                                'images' => $images,
                            ];
                        }
                    }
                });

            // Posts images
            Post::published()
                ->orderBy('id')
                ->chunk(200, function ($posts) use (&$items) {
                    Post::preloadImages($posts);

                    foreach ($posts as $post) {
                        $loc = route('client.blog.show', $post->slug);

                        if ($this->isUrlExcluded($loc) || $this->isExcludedId('post_id', $post->id)) {
                            continue;
                        }

                        $images = [];

                        // ưu tiên primaryImage nếu có
                        if ($post->primaryImage && $post->primaryImage->url) {
                            $images[] = [
                                'loc' => asset('clients/assets/img/clothes/'.$post->primaryImage->url),
                                'title' => $post->title,
                                'caption' => $post->excerpt_text,
                            ];
                        } elseif ($cover = $post->coverImagePath()) {
                            $images[] = [
                                'loc' => asset($cover),
                                'title' => $post->title,
                                'caption' => $post->excerpt_text,
                            ];
                        }

                        if (! empty($images)) {
                            $items[] = [
                                'loc' => $loc,
                                'images' => $images,
                            ];
                        }
                    }
                });

            return $this->buildImageSet($items);
        });
    }

    /**
     * Determine if a sitemap section is enabled.
     */
    protected function isEnabled(string $key): bool
    {
        return (bool) SitemapConfig::getValue("{$key}_enabled", true);
    }

    protected function postsQuery()
    {
        return Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('id');
    }

    protected function productsQuery()
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('id');
    }

    /**
     * Return an array of page numbers based on total rows.
     */
    protected function paginate(int $total): array
    {
        if ($total <= 0) {
            return [];
        }

        $perFile = $this->getUrlsPerFile();
        $pages = (int) ceil($total / $perFile);

        return range(1, max($pages, 1));
    }

    protected function paginateQuery($query, int $page)
    {
        $perFile = $this->getUrlsPerFile();

        return $query->skip(($page - 1) * $perFile)->take($perFile);
    }

    protected function getUrlsPerFile(): int
    {
        return (int) SitemapConfig::getValue('urls_per_file', 10000);
    }

    protected function buildIndex(array $entries): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($entries as $entry) {
            $xml[] = '  <sitemap>';
            $xml[] = '    <loc>'.e($entry['loc']).'</loc>';
            if (! empty($entry['lastmod'])) {
                $xml[] = '    <lastmod>'.$entry['lastmod'].'</lastmod>';
            }
            $xml[] = '  </sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return implode("\n", $xml);
    }

    protected function buildUrlSet(array $urls): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e($url['loc']).'</loc>';
            if (! empty($url['lastmod'])) {
                $xml[] = '    <lastmod>'.$url['lastmod'].'</lastmod>';
            }
            if (! empty($url['changefreq'])) {
                $xml[] = '    <changefreq>'.$url['changefreq'].'</changefreq>';
            }
            if (! empty($url['priority'])) {
                $xml[] = '    <priority>'.$url['priority'].'</priority>';
            }
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }

    /**
     * Build an image sitemap urlset.
     *
     * @param  array<int,array{loc:string,images:array<int,array{loc:string,title?:string,caption?:string}>}>  $items
     */
    protected function buildImageSet(array $items): string
    {
        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        foreach ($items as $item) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e($item['loc']).'</loc>';

            foreach ($item['images'] as $image) {
                $xml[] = '    <image:image>';
                $xml[] = '      <image:loc>'.e($image['loc']).'</image:loc>';
                if (! empty($image['title'])) {
                    $xml[] = '      <image:title>'.e($image['title']).'</image:title>';
                }
                if (! empty($image['caption'])) {
                    $xml[] = '      <image:caption>'.e($image['caption']).'</image:caption>';
                }
                $xml[] = '    </image:image>';
            }

            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }

    protected function remember(string $key, callable $callback): string
    {
        if (! config('sitemap.cache.enabled', true)) {
            return $callback();
        }

        $cacheKey = $this->cacheKey($key);
        $ttl = config('sitemap.cache.ttl', 3600);
        $this->trackCacheKey($cacheKey);

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    protected function cacheKey(string $key): string
    {
        return config('sitemap.cache.prefix', 'sitemap:').$key;
    }

    protected function trackCacheKey(string $key): void
    {
        $listKey = $this->cacheKey('keys');
        $keys = Cache::get($listKey, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever($listKey, $keys);
        }
    }

    protected function loadExcludes(): array
    {
        $groups = [
            'url' => [],
            'pattern' => [],
            'post_id' => [],
            'product_id' => [],
            'category_id' => [],
        ];

        SitemapExclude::active()->orderBy('id')->chunk(200, function ($items) use (&$groups) {
            foreach ($items as $item) {
                $groups[$item->type][] = $item->value;
            }
        });

        return $groups;
    }

    protected function isExcludedId(string $type, int $id): bool
    {
        return in_array($id, $this->excludes[$type] ?? [], true);
    }

    protected function isUrlExcluded(string $url): bool
    {
        if (in_array($url, $this->excludes['url'] ?? [], true)) {
            return true;
        }

        foreach ($this->excludes['pattern'] ?? [] as $pattern) {
            if ($this->matchesPattern($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesPattern(string $pattern, string $url): bool
    {
        if ($pattern === '') {
            return false;
        }

        $delimiter = substr($pattern, 0, 1);
        $isRegex = in_array($delimiter, ['/', '#', '~'], true) && strrpos($pattern, $delimiter) !== 0;

        if (! $isRegex) {
            $escaped = preg_quote($pattern, '#');
            $pattern = '#'.$escaped.'#i';
        }

        return @preg_match($pattern, $url) === 1;
    }

    /**
     * Build sitemap URLs for paginated types.
     * Page 1: /sitemap-{type}.xml
     * Page 2+: /sitemap-{type}-{page}.xml
     */
    protected function buildPageUrls(string $type, int $page): array
    {
        if ($page === 1) {
            return [url("/sitemap-{$type}.xml")];
        }

        return [url("/sitemap-{$type}-{$page}.xml")];
    }
}
