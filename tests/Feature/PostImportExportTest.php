<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostImportExportTest extends TestCase
{
    use DatabaseTransactions;

    protected Account $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Account::factory()->create([
            'role' => 'admin',
            'email' => 'admin_test@nobifashion.vn',
        ]);
    }

    /** @test */
    public function export_returns_only_selected_columns()
    {
        $category = Category::factory()->create(['name' => 'Thời trang nam', 'slug' => 'thoi-trang-nam']);
        $post = Post::factory()->create([
            'title' => 'Bài viết mẫu xuất CSV',
            'slug' => 'bai-viet-mau-xuat-csv',
            'excerpt' => 'Mô tả ngắn gọn',
            'category_id' => $category->id,
            'account_id' => $this->admin->id,
            'status' => 'published',
        ]);

        $selectedColumns = ['ID', 'Tiêu đề', 'Slug', 'Trạng thái'];

        $response = $this->actingAs($this->admin, 'web')
            ->getJson(route('admin.posts.export-data', [
                'columns' => $selectedColumns,
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total' => 1,
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $firstRow = $data[0];

        // Đảm bảo chỉ chứa đúng các cột được chọn
        $this->assertEquals($post->id, $firstRow['ID']);
        $this->assertEquals($post->title, $firstRow['Tiêu đề']);
        $this->assertEquals($post->slug, $firstRow['Slug']);
        $this->assertEquals('published', $firstRow['Trạng thái']);
        $this->assertArrayNotHasKey('Nội dung', $firstRow);
        $this->assertArrayNotHasKey('Tóm tắt', $firstRow);
    }

    /** @test */
    public function import_with_existing_id_updates_selected_columns()
    {
        $post = Post::factory()->create([
            'title' => 'Tiêu đề gốc',
            'slug' => 'tieu-de-goc',
            'excerpt' => 'Tóm tắt gốc',
            'content' => 'Nội dung ban đầu',
            'status' => 'draft',
            'account_id' => $this->admin->id,
        ]);

        $items = [
            [
                'ID' => $post->id,
                'Tiêu đề' => 'Tiêu đề đã được cập nhật',
                'Tóm tắt' => 'Tóm tắt mới',
                'Trạng thái' => 'published',
            ],
        ];

        // Chỉ chọn cập nhật Tiêu đề và Trạng thái, bỏ qua Tóm tắt
        $selectedColumns = ['Tiêu đề', 'Trạng thái'];

        $response = $this->actingAs($this->admin, 'web')
            ->postJson(route('admin.posts.import-batch'), [
                'items' => $items,
                'selected_columns' => $selectedColumns,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'success_count' => 1,
        ]);

        $post->refresh();
        $this->assertEquals('Tiêu đề đã được cập nhật', $post->title);
        $this->assertEquals('published', $post->status);
        // Tóm tắt không nằm trong selected_columns nên phải giữ nguyên giá trị gốc
        $this->assertEquals('Tóm tắt gốc', $post->excerpt);
        $this->assertEquals('Nội dung ban đầu', $post->content);
    }

    /** @test */
    public function import_with_non_existing_id_fails_and_does_not_create()
    {
        $nonExistingId = 999999;

        $items = [
            [
                'ID' => $nonExistingId,
                'Tiêu đề' => 'Bài viết có ID không tồn tại',
                'Slug' => 'bai-viet-co-id-khong-ton-tai',
            ],
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->postJson(route('admin.posts.import-batch'), [
                'items' => $items,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'success_count' => 0,
        ]);

        $errors = $response->json('errors');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('không tồn tại trên hệ thống', $errors[0]);

        $this->assertDatabaseMissing('posts', [
            'slug' => 'bai-viet-co-id-khong-ton-tai',
        ]);
    }

    /** @test */
    public function import_without_id_matches_existing_slug_and_updates()
    {
        $post = Post::factory()->create([
            'title' => 'Bài viết kiểm tra slug',
            'slug' => 'bai-viet-kiem-tra-slug',
            'excerpt' => 'Tóm tắt cũ',
            'account_id' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        $items = [
            [
                'ID' => '', // Không có ID
                'Slug' => 'bai-viet-kiem-tra-slug',
                'Tiêu đề' => 'Bài viết kiểm tra slug đã đổi tên',
                'Tóm tắt' => 'Tóm tắt mới theo slug',
            ],
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->postJson(route('admin.posts.import-batch'), [
                'items' => $items,
                'selected_columns' => ['Tiêu đề', 'Tóm tắt'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'success_count' => 1,
        ]);

        $post->refresh();
        $this->assertEquals('Bài viết kiểm tra slug đã đổi tên', $post->title);
        $this->assertEquals('Tóm tắt mới theo slug', $post->excerpt);
        // Không tạo thêm bản ghi mới
        $this->assertEquals(1, Post::where('slug', 'bai-viet-kiem-tra-slug')->count());
    }

    /** @test */
    public function import_without_id_and_slug_not_exists_creates_new_post()
    {
        $items = [
            [
                'ID' => '',
                'Slug' => 'bai-viet-hoan-toan-moi-123',
                'Tiêu đề' => 'Bài viết hoàn toàn mới 123',
                'Nội dung' => '<p>Nội dung bài viết mới</p>',
                'Tóm tắt' => 'Mô tả ngắn',
                'Trạng thái' => 'published',
            ],
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->postJson(route('admin.posts.import-batch'), [
                'items' => $items,
                'selected_columns' => ['Tiêu đề', 'Slug', 'Nội dung', 'Tóm tắt', 'Trạng thái'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'success_count' => 1,
        ]);

        $this->assertDatabaseHas('posts', [
            'slug' => 'bai-viet-hoan-toan-moi-123',
            'title' => 'Bài viết hoàn toàn mới 123',
            'status' => 'published',
        ]);
    }
}
