<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tác động: Bổ sung composite index (status, deleted_at, views, published_at) trên bảng posts.
     * Tối ưu truy vấn top views cho hàng triệu bài viết mà không tốn CPU sort (loại bỏ filesort).
     */
    public function up(): void
    {
        if (Schema::hasTable('posts')) {
            $existingIndexes = collect(Schema::getIndexes('posts'))->pluck('name')->toArray();
            if (!in_array('posts_status_views_idx', $existingIndexes)) {
                Schema::table('posts', function (Blueprint $table) {
                    $table->index(['status', 'deleted_at', 'views', 'published_at'], 'posts_status_views_idx');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('posts')) {
            $existingIndexes = collect(Schema::getIndexes('posts'))->pluck('name')->toArray();
            if (in_array('posts_status_views_idx', $existingIndexes)) {
                Schema::table('posts', function (Blueprint $table) {
                    $table->dropIndex('posts_status_views_idx');
                });
            }
        }
    }
};
