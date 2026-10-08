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
        $this->clearCategoryCache();
        $this->clearSitemapCache();
    }

    public function updated(Category $category): void
    {
        $this->clearCategoryCache();
        $this->clearSitemapCache();
    }

    public function deleted(Category $category): void
    {
        $this->clearCategoryCache();
        $this->clearSitemapCache();
    }

    protected function clearCategoryCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('view.categories.tree.v1');
        \Illuminate\Support\Facades\Cache::forget('view.categories.tree.v2');
        \Illuminate\Support\Facades\Cache::forget('view.categories.tree.v3');
    }

    protected function clearSitemapCache(): void
    {
        if (config('sitemap.cache.enabled', true)) {
            $this->sitemapService->clearCache();
        }
    }
}

