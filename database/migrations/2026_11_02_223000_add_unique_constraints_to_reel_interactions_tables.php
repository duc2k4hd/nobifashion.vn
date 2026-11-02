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
        // 1. Ràng buộc toàn vẹn cho reel_likes: Chống like trùng từ người dùng đã đăng nhập
        if (Schema::hasTable('reel_likes')) {
            // Dọn dẹp bản ghi like trùng của cùng 1 user trên 1 reel (giữ bản ghi id nhỏ nhất)
            DB::statement("
                DELETE t1 FROM reel_likes t1
                INNER JOIN reel_likes t2 
                WHERE t1.id > t2.id 
                  AND t1.reel_id = t2.reel_id 
                  AND t1.user_id = t2.user_id 
                  AND t1.user_id IS NOT NULL
            ");

            try {
                Schema::table('reel_likes', function (Blueprint $table) {
                    $table->unique(['reel_id', 'user_id'], 'uq_reel_like_user');
                });
            } catch (\Throwable $e) {}
        }

        // 2. Ràng buộc toàn vẹn cho reel_saves: Chống bookmark / save trùng từ người dùng đã đăng nhập
        if (Schema::hasTable('reel_saves')) {
            DB::statement("
                DELETE t1 FROM reel_saves t1
                INNER JOIN reel_saves t2 
                WHERE t1.id > t2.id 
                  AND t1.reel_id = t2.reel_id 
                  AND t1.user_id = t2.user_id 
                  AND t1.user_id IS NOT NULL
            ");

            try {
                Schema::table('reel_saves', function (Blueprint $table) {
                    $table->unique(['reel_id', 'user_id'], 'uq_reel_save_user');
                });
            } catch (\Throwable $e) {}
        }

        // 3. Ràng buộc toàn vẹn cho reel_comment_likes: Chống like trùng bình luận từ người dùng đã đăng nhập
        if (Schema::hasTable('reel_comment_likes')) {
            DB::statement("
                DELETE t1 FROM reel_comment_likes t1
                INNER JOIN reel_comment_likes t2 
                WHERE t1.id > t2.id 
                  AND t1.comment_id = t2.comment_id 
                  AND t1.user_id = t2.user_id 
                  AND t1.user_id IS NOT NULL
            ");

            try {
                Schema::table('reel_comment_likes', function (Blueprint $table) {
                    $table->unique(['comment_id', 'user_id'], 'uq_comment_like_user');
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reel_likes')) {
            try {
                Schema::table('reel_likes', function (Blueprint $table) {
                    $table->dropUnique('uq_reel_like_user');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('reel_saves')) {
            try {
                Schema::table('reel_saves', function (Blueprint $table) {
                    $table->dropUnique('uq_reel_save_user');
                });
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('reel_comment_likes')) {
            try {
                Schema::table('reel_comment_likes', function (Blueprint $table) {
                    $table->dropUnique('uq_comment_like_user');
                });
            } catch (\Throwable $e) {}
        }
    }
};

