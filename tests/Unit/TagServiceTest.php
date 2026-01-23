<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Post;
use App\Models\Product;
use App\Services\TagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TagService $tagService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagService = app(TagService::class);
    }

    /** @test */
    public function it_can_create_a_tag()
    {
        $post = Post::factory()->create();
        
        $data = [
            'name' => 'Fashion',
            'slug' => 'fashion',
            'description' => 'Fashion tag',
            'is_active' => true,
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ];

        $tag = $this->tagService->create($data);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals('Fashion', $tag->name);
        $this->assertEquals('fashion', $tag->slug);
        $this->assertEquals(Post::class, $tag->entity_type);
        $this->assertEquals($post->id, $tag->entity_id);
    }

    /** @test */
    public function it_auto_generates_slug_if_not_provided()
    {
        $post = Post::factory()->create();
        
        $data = [
            'name' => 'Test Tag Name',
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ];

        $tag = $this->tagService->create($data);

        $this->assertEquals('test-tag-name', $tag->slug);
    }

    /** @test */
    public function it_handles_duplicate_slugs()
    {
        $post = Post::factory()->create();
        
        $data1 = [
            'name' => 'Test',
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ];

        $data2 = [
            'name' => 'Test',
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ];

        $tag1 = $this->tagService->create($data1);
        $tag2 = $this->tagService->create($data2);

        $this->assertNotEquals($tag1->slug, $tag2->slug);
        $this->assertStringStartsWith('test', $tag2->slug);
    }

    /** @test */
    public function it_can_update_a_tag()
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create([
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ]);

        $data = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $updatedTag = $this->tagService->update($tag, $data);

        $this->assertEquals('Updated Name', $updatedTag->name);
        $this->assertEquals('Updated description', $updatedTag->description);
    }

    /** @test */
    public function it_can_delete_a_tag()
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create([
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ]);

        $result = $this->tagService->delete($tag);

        $this->assertTrue($result);
        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function it_can_suggest_tags_by_keyword()
    {
        Tag::factory()->create(['name' => 'Fashion', 'slug' => 'fashion', 'is_active' => true]);
        Tag::factory()->create(['name' => 'Style', 'slug' => 'style', 'is_active' => true]);
        Tag::factory()->create(['name' => 'Trend', 'slug' => 'trend', 'is_active' => true]);

        $suggestions = $this->tagService->suggest('fash', null, 10);

        $this->assertNotEmpty($suggestions);
        $this->assertArrayHasKey('name', $suggestions[0]);
        $this->assertStringContainsString('fash', strtolower($suggestions[0]['name']));
    }

    /** @test */
    public function it_can_suggest_tags_from_content()
    {
        Tag::factory()->create(['name' => 'Fashion', 'slug' => 'fashion', 'is_active' => true]);
        Tag::factory()->create(['name' => 'Style', 'slug' => 'style', 'is_active' => true]);
        Tag::factory()->create(['name' => 'Trend', 'slug' => 'trend', 'is_active' => true]);

        $content = 'This is about fashion and style trends in 2024.';
        $suggestions = $this->tagService->suggestFromContent($content, null, 5);

        $this->assertIsArray($suggestions);
    }

    /** @test */
    public function it_can_assign_tags_to_entity()
    {
        $post = Post::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $assigned = $this->tagService->assignToEntity(
            Post::class,
            $post->id,
            [$tag1->id, $tag2->id]
        );

        $this->assertCount(2, $assigned);
        $this->assertEquals(Post::class, $tag1->fresh()->entity_type);
        $this->assertEquals($post->id, $tag1->fresh()->entity_id);
    }

    /** @test */
    public function it_can_get_tags_by_entity()
    {
        $post = Post::factory()->create();
        $tag1 = Tag::factory()->create([
            'entity_type' => Post::class,
            'entity_id' => $post->id,
            'is_active' => true,
        ]);
        $tag2 = Tag::factory()->create([
            'entity_type' => Post::class,
            'entity_id' => $post->id,
            'is_active' => true,
        ]);

        $tags = $this->tagService->getByEntity(Post::class, $post->id);

        $this->assertCount(2, $tags);
    }
}
