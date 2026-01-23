<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SitemapService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    public function created(Product $product): void
    {
        $this->rebuildSitemap('created');
    }

    public function updated(Product $product): void
    {
        // Only rebuild if is_active changed
        if ($product->wasChanged('is_active')) {
            $this->rebuildSitemap('updated');
        } else {
            $this->clearSitemapCache();
        }
    }

    public function deleted(Product $product): void
    {
        $this->rebuildSitemap('deleted');
    }

    protected function rebuildSitemap(string $action): void
    {
        try {
            // Clear all sitemap cache
            $this->sitemapService->clearCache();
            
            // Pre-generate main sitemaps in background (optional - can be disabled for performance)
            if (config('sitemap.auto_rebuild', true)) {
                // Generate products sitemap (page 1) to warm cache
                $this->sitemapService->generateProducts(1);
                
                // Generate sitemap index
                $this->sitemapService->generateIndex();
            }
            
            Log::info("Sitemap rebuilt after product {$action}");
        } catch (\Exception $e) {
            Log::error("Failed to rebuild sitemap after product {$action}: " . $e->getMessage());
        }
    }

    protected function clearSitemapCache(): void
    {
        try {
            if (config('sitemap.cache.enabled', true)) {
                $this->sitemapService->clearCache();
            }
        } catch (\Exception $e) {
            Log::error("Failed to clear sitemap cache: " . $e->getMessage());
        }
    }
}

