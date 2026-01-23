<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SitemapController extends Controller
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Display sitemap index
     */
    public function index(): Response
    {
        try {
        $xml = $this->sitemapService->generateIndex();
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>', 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display posts sitemap
     */
    public function posts(Request $request, ?int $page = null): Response
    {
        try {
        // Get page from route parameter or query string, default to 1
        $page = $page ?? (int) $request->get('page', 1);
        $page = max(1, $page); // Ensure page is at least 1
        
        $xml = $this->sitemapService->generatePosts($page);
        
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap posts error', [
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response($this->sitemapService->generatePosts(1), 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display products sitemap
     */
    public function products(Request $request, ?int $page = null): Response
    {
        try {
        // Get page from route parameter or query string, default to 1
        $page = $page ?? (int) $request->get('page', 1);
        $page = max(1, $page); // Ensure page is at least 1
        
        $xml = $this->sitemapService->generateProducts($page);
        
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap products error', [
                'page' => $page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response($this->sitemapService->generateProducts(1), 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display categories sitemap
     */
    public function categories(): Response
    {
        try {
        $xml = $this->sitemapService->generateCategories();
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap categories error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response($this->sitemapService->generateCategories(), 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display tags sitemap
     */
    public function tags(): Response
    {
        try {
        $xml = $this->sitemapService->generateTagsProducts();
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap tags error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response($this->sitemapService->generateTagsProducts(), 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display pages sitemap
     */
    public function pages(): Response
    {
        try {
        $xml = $this->sitemapService->generatePages();
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap pages error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Fallback: trả về urlset rỗng hợp lệ từ service
            return response($this->sitemapService->generatePages(), 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display images sitemap
     */
    public function images(): Response
    {
        try {
        $xml = $this->sitemapService->generateImages();
        return response($xml, 200)->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('Sitemap images error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Fallback: trả về image urlset rỗng hợp lệ từ service
            return response($this->sitemapService->generateImages(), 200)
                ->header('Content-Type', 'application/xml');
        }
    }

    /**
     * Display sitemap HTML page for clients
     */
    public function html()
    {
        $sitemaps = [
            [
                'name' => 'Sitemap Index',
                'url' => url('/sitemap.xml'),
                'description' => 'Trang tổng quan tất cả sitemap',
                'icon' => '🗺️',
            ],
            [
                'name' => 'Bài viết',
                'url' => url('/sitemap-posts.xml'),
                'description' => 'Danh sách tất cả bài viết blog',
                'icon' => '📝',
            ],
            [
                'name' => 'Sản phẩm',
                'url' => url('/sitemap-products.xml'),
                'description' => 'Danh sách tất cả sản phẩm',
                'icon' => '🛍️',
            ],
            [
                'name' => 'Danh mục',
                'url' => url('/sitemap-categories.xml'),
                'description' => 'Danh sách tất cả danh mục sản phẩm',
                'icon' => '📂',
            ],
            [
                'name' => 'Tags',
                'url' => url('/sitemap-tags.xml'),
                'description' => 'Danh sách tất cả tags',
                'icon' => '🏷️',
            ],
            [
                'name' => 'Trang tĩnh',
                'url' => url('/sitemap-pages.xml'),
                'description' => 'Danh sách các trang tĩnh',
                'icon' => '📄',
            ],
            [
                'name' => 'Hình ảnh',
                'url' => url('/sitemap-images.xml'),
                'description' => 'Danh sách hình ảnh trong sitemap',
                'icon' => '🖼️',
            ],
        ];

        // Prepare SEO data
        $siteName = config('app.name');
        $siteUrl = config('app.url');
        $sitemapUrl = route('client.sitemap.html');
        $sitemapXmlUrl = url('/sitemap.xml');
        
        $metaTitle = "Sitemap - {$siteName} | Tìm kiếm và khám phá toàn bộ nội dung";
        $metaDescription = "Sitemap của {$siteName} - Khám phá tất cả các trang, sản phẩm, bài viết, danh mục và tags trên website. Tìm kiếm nội dung một cách dễ dàng và nhanh chóng.";
        $metaKeywords = "sitemap, {$siteName}, bản đồ trang web, tìm kiếm nội dung, sản phẩm, bài viết, danh mục, tags, SEO";

        return view('clients.sitemap.index', compact('sitemaps', 'metaTitle', 'metaDescription', 'metaKeywords', 'siteName', 'siteUrl', 'sitemapUrl', 'sitemapXmlUrl'));
    }
}
