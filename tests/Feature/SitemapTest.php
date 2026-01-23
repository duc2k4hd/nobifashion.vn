<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\SitemapConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_index_is_accessible(): void
    {
        $response = $this->get('/sitemap.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('sitemapindex');
    }

    public function test_sitemap_posts_is_accessible(): void
    {
        Post::factory()->create([
            'status' => 'published',
            'is_active' => true,
            'slug' => 'test-post',
        ]);

        $response = $this->get('/sitemap-posts.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('urlset');
    }

    public function test_sitemap_products_is_accessible(): void
    {
        Product::factory()->create([
            'status' => 'active',
            'is_active' => true,
            'slug' => 'test-product',
        ]);

        $response = $this->get('/sitemap-products.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('urlset');
    }

    public function test_sitemap_categories_is_accessible(): void
    {
        Category::factory()->create([
            'is_active' => true,
            'slug' => 'test-category',
        ]);

        $response = $this->get('/sitemap-categories.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('urlset');
    }

    public function test_sitemap_tags_is_accessible(): void
    {
        Tag::factory()->create([
            'is_active' => true,
            'slug' => 'test-tag',
        ]);

        $response = $this->get('/sitemap-tags.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('urlset');
    }

    public function test_sitemap_pages_is_accessible(): void
    {
        $response = $this->get('/sitemap-pages.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('urlset');
    }

    public function test_sitemap_images_is_accessible(): void
    {
        $response = $this->get('/sitemap-images.xml');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('urlset');
    }

    public function test_robots_txt_is_accessible(): void
    {
        $response = $this->get('/robots.txt');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain');
        $response->assertSee('Sitemap:');
    }

    public function test_sitemap_excludes_inactive_posts(): void
    {
        Post::factory()->create([
            'status' => 'draft',
            'is_active' => false,
            'slug' => 'inactive-post',
        ]);

        $response = $this->get('/sitemap-posts.xml');
        
        $response->assertStatus(200);
        $response->assertDontSee('inactive-post');
    }

    public function test_sitemap_respects_exclude_rules(): void
    {
        \App\Models\SitemapExclude::create([
            'type' => 'post_id',
            'value' => '1',
            'is_active' => true,
        ]);

        Post::factory()->create([
            'id' => 1,
            'status' => 'published',
            'is_active' => true,
            'slug' => 'excluded-post',
        ]);

        $response = $this->get('/sitemap-posts.xml');
        
        $response->assertStatus(200);
        $response->assertDontSee('excluded-post');
    }
}

