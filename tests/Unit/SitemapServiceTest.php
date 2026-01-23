<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\SitemapConfig;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SitemapService $sitemapService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sitemapService = app(SitemapService::class);
    }

    public function test_generate_index_returns_xml(): void
    {
        $xml = $this->sitemapService->generateIndex();
        
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('sitemapindex', $xml);
    }

    public function test_generate_posts_returns_xml(): void
    {
        Post::factory()->count(5)->create([
            'status' => 'published',
            'is_active' => true,
        ]);

        $xml = $this->sitemapService->generatePosts();
        
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('urlset', $xml);
    }

    public function test_generate_products_returns_xml(): void
    {
        Product::factory()->count(5)->create([
            'status' => 'active',
            'is_active' => true,
        ]);

        $xml = $this->sitemapService->generateProducts();
        
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('urlset', $xml);
    }

    public function test_clear_cache_works(): void
    {
        $this->sitemapService->generateIndex();
        
        $this->sitemapService->clearCache();
        
        // Cache should be cleared, no exception should be thrown
        $this->assertTrue(true);
    }

    public function test_rebuild_works(): void
    {
        Post::factory()->count(3)->create([
            'status' => 'published',
            'is_active' => true,
        ]);

        $this->sitemapService->rebuild();
        
        $lastGenerated = SitemapConfig::getValue('last_generated_at');
        $this->assertNotNull($lastGenerated);
    }

    public function test_ping_search_engines_returns_results(): void
    {
        $results = $this->sitemapService->pingSearchEngines();
        
        $this->assertIsArray($results);
    }
}

