<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\SitemapService;

class CategoryObserver
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    public function created(Category $category): void
    {
        $this->clearSitemapCache();
    }

    public function updated(Category $category): void
    {
        $this->clearSitemapCache();
    }

    public function deleted(Category $category): void
    {
        $this->clearSitemapCache();
    }

    protected function clearSitemapCache(): void
    {
        if (config('sitemap.cache.enabled', true)) {
            $this->sitemapService->clearCache();
        }
    }
}

