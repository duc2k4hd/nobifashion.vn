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
        // 1. Bảng trung tâm reels
        if (!Schema::hasTable('reels')) {
            Schema::create('reels', function (Blueprint $table) {
                $table->id();
                $table->string('external_id', 100)->nullable()->unique()->comment('Mã ID video từ nguồn R2 chống trùng');
                $table->string('slug', 191)->unique();
                $table->string('title', 255);
                $table->text('caption')->nullable();
                $table->string('video_url', 1000);
                $table->string('poster_url', 1000)->nullable();
                $table->string('thumbnail_url', 1000)->nullable();
                $table->unsignedInteger('duration')->default(0)->comment('Thời lượng tính bằng giây');
                $table->unsignedInteger('width')->default(1080);
                $table->unsignedInteger('height')->default(1920);
                $table->string('aspect_ratio', 10)->default('9:16');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('mime_type', 50)->default('video/mp4');
                $table->string('playback_type', 30)->default('direct_mp4');
                $table->string('video_provider', 50)->default('cloudflare_r2');
                $table->string('audio_title', 255)->nullable();
                
                // Tác giả & Kênh hiển thị
                $table->string('author_name', 100)->default('Nobi Fashion');
                $table->string('author_handle', 100)->default('@nobifashion');
                $table->string('author_avatar', 1000)->nullable();
                $table->boolean('author_verified')->default(true);
                
                // Phân loại & Hashtags
                $table->string('category', 50)->default('trending');
                $table->json('hashtags')->nullable();
                
                // Bộ đếm tương tác
                $table->unsignedBigInteger('view_count')->default(0);
                $table->unsignedInteger('like_count')->default(0);
                $table->unsignedInteger('comment_count')->default(0);
                $table->unsignedInteger('share_count')->default(0);
                $table->unsignedInteger('save_count')->default(0);
                
                // SEO Metadata
                $table->string('seo_title', 255)->nullable();
                $table->text('seo_description')->nullable();
                $table->string('canonical_url', 1000)->nullable();
                
                // Trạng thái & Ưu tiên
                $table->string('status', 30)->default('published');
                $table->boolean('is_featured')->default(false);
                $table->integer('order_priority')->default(0);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes tối ưu hiệu năng cao cho Cursor Pagination & Trending
                $table->index(['status', 'published_at', 'id'], 'idx_reels_feed');
                $table->index(['category', 'status', 'published_at'], 'idx_reels_category_feed');
                $table->index(['status', 'like_count', 'view_count'], 'idx_reels_trending');
                $table->index(['is_featured', 'status', 'published_at'], 'idx_reels_featured');
            });
        }

        // 2. Bảng liên kết sản phẩm thời trang trong Reels
        if (!Schema::hasTable('reel_products')) {
            Schema::create('reel_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('order')->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['reel_id', 'product_id'], 'uq_reel_product');
                $table->index('product_id', 'idx_product_reels');
            });
        }

        // 3. Bảng lưu lượt Like
        if (!Schema::hasTable('reel_likes')) {
            Schema::create('reel_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip_address', 45);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['reel_id', 'ip_address'], 'idx_reel_like_ip');
                $table->index(['reel_id', 'user_id'], 'idx_reel_like_user');
            });
        }

        // 4. Bảng lưu lượt Save / Bookmark
        if (!Schema::hasTable('reel_saves')) {
            Schema::create('reel_saves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip_address', 45);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['reel_id', 'ip_address'], 'idx_reel_save_ip');
                $table->index(['reel_id', 'user_id'], 'idx_reel_save_user');
            });
        }

        // 5. Bảng bình luận Reels
        if (!Schema::hasTable('reel_comments')) {
            Schema::create('reel_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('author_name', 100);
                $table->text('content');
                $table->unsignedInteger('likes_count')->default(0);
                $table->string('status', 20)->default('approved');
                $table->timestamps();

                $table->index(['reel_id', 'status', 'created_at'], 'idx_reel_comments_feed');
                $table->index('parent_id', 'idx_reel_comment_parent');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_comments');
        Schema::dropIfExists('reel_saves');
        Schema::dropIfExists('reel_likes');
        Schema::dropIfExists('reel_products');
        Schema::dropIfExists('reels');
    }
};
