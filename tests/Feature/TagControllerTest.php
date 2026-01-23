<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Post;
use App\Models\Product;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Account $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Account::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function admin_can_view_tags_index()
    {
        Tag::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.tags.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admins.tags.index');
    }

    /** @test */
    public function admin_can_view_create_tag_form()
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.tags.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admins.tags.create');
    }

    /** @test */
    public function admin_can_create_a_tag()
    {
        $post = Post::factory()->create();

        $data = [
            'name' => 'Test Tag',
            'description' => 'Test description',
            'entity_type' => 'post',
            'entity_id' => $post->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.tags.store'), $data);

        $response->assertRedirect(route('admin.tags.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tags', [
            'name' => 'Test Tag',
            'entity_type' => Post::class,
            'entity_id' => $post->id,
        ]);
    }

    /** @test */
    public function admin_can_view_edit_tag_form()
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.tags.edit', $tag));

        $response->assertStatus(200);
        $response->assertViewIs('admins.tags.edit');
    }

    /** @test */
    public function admin_can_update_a_tag()
    {
        $tag = Tag::factory()->create();

        $data = [
            'name' => 'Updated Tag',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->put(route('admin.tags.update', $tag), $data);

        $response->assertRedirect(route('admin.tags.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Tag',
        ]);
    }

    /** @test */
    public function admin_can_delete_a_tag()
    {
        $tag = Tag::factory()->create(['usage_count' => 0]);

        $response = $this->actingAs($this->admin, 'web')
            ->delete(route('admin.tags.destroy', $tag));

        $response->assertRedirect(route('admin.tags.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function admin_can_get_tag_suggestions()
    {
        Tag::factory()->create(['name' => 'Fashion', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.tags.suggest', ['keyword' => 'fash']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'slug', 'entity_type', 'usage_count'],
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_tags()
    {
        $user = Account::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'web')
            ->get(route('admin.tags.index'));

        $response->assertStatus(403);
    }
}
