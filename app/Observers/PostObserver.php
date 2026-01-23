<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\SitemapService;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    public function created(Post $post): void
    {
        $this->rebuildSitemap('created');
    }

    public function updated(Post $post): void
    {
        // Only rebuild if status changed to published or published_at changed
        if ($post->wasChanged('status') || $post->wasChanged('published_at')) {
            $this->rebuildSitemap('updated');
        } else {
            $this->clearSitemapCache();
        }
    }

    public function deleted(Post $post): void
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
                // Generate posts sitemap (page 1) to warm cache
                $this->sitemapService->generatePosts(1);
                
                // Generate sitemap index
                $this->sitemapService->generateIndex();
            }
            
            Log::info("Sitemap rebuilt after post {$action}");
        } catch (\Exception $e) {
            Log::error("Failed to rebuild sitemap after post {$action}: " . $e->getMessage());
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
