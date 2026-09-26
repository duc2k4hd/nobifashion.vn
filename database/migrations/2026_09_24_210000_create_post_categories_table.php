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
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->string('meta_canonical')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });

            try {
                Schema::table('post_categories', function (Blueprint $table) {
                    $table->foreign('parent_id')->references('id')->on('post_categories')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        // 2. Thêm danh mục mặc định nếu bảng post_categories trống
        if (DB::table('post_categories')->count() === 0) {
            DB::table('post_categories')->insert([
                'name' => 'Thời trang',
                'slug' => 'thoi-trang',
                'description' => 'Tin tức thời trang, phong cách và xu hướng mới nhất.',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Đổi Foreign Key trên bảng posts trỏ sang post_categories(id)
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'category_id')) {
            // Drop foreign key cũ trỏ sang categories nếu có
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->dropForeign(['category_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key không tồn tại trên MyISAM hoặc đã bị drop trước đó
            }

            // Đảm bảo có foreign key trỏ sang post_categories nếu CSDL hỗ trợ
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->foreign('category_id')
                        ->references('id')
                        ->on('post_categories')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // CSDL không hỗ trợ foreign key (MyISAM), index category_id vẫn đảm bảo tốc độ cao
            }

            // Composite index tăng tốc truy vấn danh mục bài viết cho hàng trăm nghìn bài
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->index(['category_id', 'status', 'published_at'], 'posts_category_status_published_idx');
                });
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'category_id')) {
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->dropIndex('posts_category_status_published_idx');
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->dropForeign(['category_id']);
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        Schema::dropIfExists('post_categories');
    }
};
