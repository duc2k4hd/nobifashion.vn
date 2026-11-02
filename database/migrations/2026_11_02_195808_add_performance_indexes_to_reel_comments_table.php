<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reel_comments', function (Blueprint $table) {
            // Index tối ưu sắp xếp comment cha theo độ phổ biến (Nhiều tim nhất -> Nhiều reply nhất -> Mới nhất)
            $table->index(['reel_id', 'status', 'parent_id', 'likes_count', 'replies_count', 'id'], 'idx_comments_popular_feed');
            
            // Index tối ưu truy vấn nạp phân trang 10 replies con theo parent_id
            $table->index(['parent_id', 'status', 'id'], 'idx_comments_parent_feed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reel_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_popular_feed');
            $table->dropIndex('idx_comments_parent_feed');
        });
    }
};
