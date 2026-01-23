<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\SitemapService;

class TagObserver
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    public function created(Tag $tag): void
    {
        $this->clearSitemapCache();
    }

    public function updated(Tag $tag): void
    {
        $this->clearSitemapCache();
    }

    public function deleted(Tag $tag): void
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

