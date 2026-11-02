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
        // 1. Bảng Anonymous Users
        if (!Schema::hasTable('anonymous_users')) {
            Schema::create('anonymous_users', function (Blueprint $table) {
                $table->id();
                $table->string('token_hash', 64)->unique()->comment('SHA-256 hash của anonymous cookie token');
                $table->string('public_id', 40)->unique()->comment('Mã công khai hiển thị cho client nếu cần');
                $table->string('nickname', 100)->unique()->comment('Biệt danh cố định duy nhất (VD: Sóc Nâu 94)');
                $table->string('avatar_key', 50)->default('cat')->comment('Khóa biểu tượng avatar (cat, bear, fox...)');
                $table->string('avatar_color', 20)->default('#3b82f6')->comment('Mã màu nền avatar pastel');
                $table->string('status', 20)->default('active')->comment('active, muted, banned');
                $table->boolean('is_banned')->default(false);
                $table->string('ban_reason', 255)->nullable();
                $table->timestamp('banned_until')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'is_banned'], 'idx_anon_status_banned');
            });
        }

        // 2. Thư viện Sticker có sẵn
        if (!Schema::hasTable('comment_stickers')) {
            Schema::create('comment_stickers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique()->comment('Mã sticker duy nhất');
                $table->string('name', 100);
                $table->string('asset_url', 500)->comment('Đường dẫn icon/svg/webp an toàn của hệ thống');
                $table->string('category', 50)->default('cute')->comment('cute, reaction, fashion, meme');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['category', 'is_active', 'sort_order'], 'idx_stickers_list');
            });
        }

        // 3. Quy tắc kiểm duyệt nội dung (Moderation Rules)
        if (!Schema::hasTable('comment_moderation_rules')) {
            Schema::create('comment_moderation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('language', 10)->default('all')->comment('vi, en, all');
                $table->string('rule_type', 30)->default('profanity')->comment('profanity, link, harassment, spam, custom');
                $table->string('pattern', 255)->comment('Từ khóa hoặc biểu thức chính quy');
                $table->string('severity', 20)->default('high')->comment('low, medium, high');
                $table->string('action', 20)->default('block')->comment('block, review, warn');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['rule_type', 'is_active', 'language'], 'idx_mod_rules');
            });
        }

        // 4. Danh sách ngoại lệ kiểm duyệt (Whitelist để tránh false positive)
        if (!Schema::hasTable('comment_moderation_whitelists')) {
            Schema::create('comment_moderation_whitelists', function (Blueprint $table) {
                $table->id();
                $table->string('word', 100)->unique();
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        // 5. Nhật ký kiểm duyệt (Moderation Logs)
        if (!Schema::hasTable('comment_moderation_logs')) {
            Schema::create('comment_moderation_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anonymous_user_id')->nullable();
                $table->unsignedBigInteger('comment_id')->nullable();
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->string('reason_code', 50);
                $table->text('flagged_content')->nullable();
                $table->string('moderation_result', 20)->comment('blocked, sent_to_review, allowed');
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['anonymous_user_id', 'created_at'], 'idx_mod_logs_user');
                $table->index(['reason_code', 'created_at'], 'idx_mod_logs_reason');
            });
        }

        // 6. Bảng Thả tim bình luận (Reel Comment Likes)
        if (!Schema::hasTable('reel_comment_likes')) {
            Schema::create('reel_comment_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('comment_id')->constrained('reel_comments')->cascadeOnDelete();
                $table->unsignedBigInteger('anonymous_user_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['comment_id', 'anonymous_user_id'], 'uq_comment_like_anon');
                $table->index(['comment_id', 'user_id'], 'idx_comment_like_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_comment_likes');
        Schema::dropIfExists('comment_moderation_logs');
        Schema::dropIfExists('comment_moderation_whitelists');
        Schema::dropIfExists('comment_moderation_rules');
        Schema::dropIfExists('comment_stickers');
        Schema::dropIfExists('anonymous_users');

        if (Schema::hasTable('reel_comments')) {
            Schema::table('reel_comments', function (Blueprint $table) {
                if (Schema::hasColumn('reel_comments', 'anonymous_user_id')) {
                    $table->dropColumn(['anonymous_user_id', 'comment_type', 'sticker_id', 'replies_count', 'deleted_at']);
                }
            });
        }

        if (Schema::hasTable('reel_likes')) {
            Schema::table('reel_likes', function (Blueprint $table) {
                if (Schema::hasColumn('reel_likes', 'anonymous_user_id')) {
                    $table->dropUnique('uq_reel_like_anon');
                    $table->dropColumn(['anonymous_user_id', 'updated_at']);
                }
            });
        }
    }
};

