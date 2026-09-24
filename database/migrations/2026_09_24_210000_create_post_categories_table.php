<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tạo bảng post_categories
        if (!Schema::hasTable('post_categories')) {
            Schema::create('post_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->text('image')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('post_categories')->nullOnDelete();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->string('meta_canonical')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        // 2. Di chuyển dữ liệu danh mục mà các bài viết hiện tại đang trỏ tới (bảo toàn ID)
        $usedCategoryIds = DB::table('posts')
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id')
            ->filter()
            ->values();

        if ($usedCategoryIds->isNotEmpty()) {
            $existingCategories = DB::table('categories')
                ->whereIn('id', $usedCategoryIds)
                ->get();

            foreach ($existingCategories as $cat) {
                $exists = DB::table('post_categories')->where('id', $cat->id)->exists();
                if (!$exists) {
                    DB::table('post_categories')->insert([
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $cat->slug,
                        'description' => $cat->description ?? null,
                        'image' => $cat->image ?? null,
                        'meta_title' => $cat->meta_title ?? null,
                        'meta_description' => $cat->meta_description ?? null,
                        'meta_keywords' => $cat->meta_keywords ?? null,
                        'meta_canonical' => $cat->meta_canonical ?? null,
                        'is_active' => $cat->is_active ?? true,
                        'sort_order' => $cat->sort_order ?? 0,
                        'created_at' => $cat->created_at ?? now(),
                        'updated_at' => $cat->updated_at ?? now(),
                    ]);
                }
            }
        }

        // Thêm danh mục mặc định nếu bảng post_categories trống
        if (DB::table('post_categories')->count() === 0) {
            DB::table('post_categories')->insert([
                'name' => 'Tin tức & Xu hướng',
                'slug' => 'tin-tuc-xu-huong',
                'description' => 'Tin tức thời trang, phong cách và xu hướng mới nhất.',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Đổi Foreign Key trên bảng posts trỏ sang post_categories(id)
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'category_id')) {
            Schema::table('posts', function (Blueprint $table) {
                // Drop foreign key cũ trỏ sang categories
                try {
                    $table->dropForeign(['category_id']);
                } catch (\Throwable $e) {
                    // Foreign key có thể đã bị drop hoặc có tên khác
                }
            });

            Schema::table('posts', function (Blueprint $table) {
                // Đảm bảo có foreign key trỏ sang post_categories
                $table->foreign('category_id')
                    ->references('id')
                    ->on('post_categories')
                    ->nullOnDelete();

                // Composite index tăng tốc truy vấn danh mục bài viết cho hàng trăm nghìn bài
                try {
                    $table->index(['category_id', 'status', 'published_at'], 'posts_category_status_published_idx');
                } catch (\Throwable $e) {
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'category_id')) {
            Schema::table('posts', function (Blueprint $table) {
                try {
                    $table->dropIndex('posts_category_status_published_idx');
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropForeign(['category_id']);
                } catch (\Throwable $e) {
                }
                
                try {
                    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            });
        }

        Schema::dropIfExists('post_categories');
    }
};
