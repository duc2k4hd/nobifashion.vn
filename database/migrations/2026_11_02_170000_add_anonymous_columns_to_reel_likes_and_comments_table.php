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
        // 1. Thêm cột vào bảng reel_likes
        if (Schema::hasTable('reel_likes')) {
            Schema::table('reel_likes', function (Blueprint $table) {
                if (!Schema::hasColumn('reel_likes', 'anonymous_user_id')) {
                    $table->unsignedBigInteger('anonymous_user_id')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('reel_likes', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });

            // Unique constraint cho reel_id + anonymous_user_id
            try {
                Schema::table('reel_likes', function (Blueprint $table) {
                    $table->unique(['reel_id', 'anonymous_user_id'], 'uq_reel_like_anon');
                });
            } catch (\Throwable $e) {}
        }

        // 2. Thêm cột vào bảng reel_comments
        if (Schema::hasTable('reel_comments')) {
            Schema::table('reel_comments', function (Blueprint $table) {
                if (!Schema::hasColumn('reel_comments', 'anonymous_user_id')) {
                    $table->unsignedBigInteger('anonymous_user_id')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('reel_comments', 'comment_type')) {
                    $table->string('comment_type', 20)->default('TEXT')->after('content')->comment('TEXT, STICKER');
                }
                if (!Schema::hasColumn('reel_comments', 'sticker_id')) {
                    $table->unsignedBigInteger('sticker_id')->nullable()->after('comment_type');
                }
                if (!Schema::hasColumn('reel_comments', 'replies_count')) {
                    $table->unsignedInteger('replies_count')->default(0)->after('likes_count');
                }
                if (!Schema::hasColumn('reel_comments', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });

            try {
                Schema::table('reel_comments', function (Blueprint $table) {
                    $table->index(['anonymous_user_id', 'created_at'], 'idx_comment_anon_user');
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reel_comments')) {
            Schema::table('reel_comments', function (Blueprint $table) {
                if (Schema::hasColumn('reel_comments', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('reel_comments', 'replies_count')) {
                    $table->dropColumn('replies_count');
                }
                if (Schema::hasColumn('reel_comments', 'sticker_id')) {
                    $table->dropColumn('sticker_id');
                }
                if (Schema::hasColumn('reel_comments', 'comment_type')) {
                    $table->dropColumn('comment_type');
                }
                if (Schema::hasColumn('reel_comments', 'anonymous_user_id')) {
                    $table->dropColumn('anonymous_user_id');
                }
            });
        }

        if (Schema::hasTable('reel_likes')) {
            Schema::table('reel_likes', function (Blueprint $table) {
                try {
                    $table->dropUnique('uq_reel_like_anon');
                } catch (\Throwable $e) {}

                if (Schema::hasColumn('reel_likes', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
                if (Schema::hasColumn('reel_likes', 'anonymous_user_id')) {
                    $table->dropColumn('anonymous_user_id');
                }
            });
        }
    }
};

