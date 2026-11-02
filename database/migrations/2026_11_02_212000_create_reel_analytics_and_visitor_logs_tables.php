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
        // 1. Bảng lưu trữ phiên truy cập của người dùng (cả khách vãng lai và đã đăng nhập)
        if (!Schema::hasTable('reel_visitor_sessions')) {
            Schema::create('reel_visitor_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_token', 64)->unique()->comment('Mã định danh phiên theo dõi duy nhất');
                $table->unsignedBigInteger('anonymous_user_id')->nullable()->index()->comment('ID khách vãng lai');
                $table->unsignedBigInteger('account_id')->nullable()->index()->comment('ID tài khoản nếu đã đăng nhập');
                $table->string('ip_address', 45)->index()->comment('Địa chỉ IP (IPv4 hoặc IPv6)');
                $table->text('user_agent')->nullable()->comment('Chuỗi User-Agent đầy đủ');
                $table->string('device_type', 20)->default('desktop')->index()->comment('mobile, tablet, desktop');
                $table->string('browser', 50)->nullable()->index()->comment('Tên trình duyệt (Chrome, Safari, Edge, Firefox, etc.)');
                $table->string('platform', 50)->nullable()->index()->comment('Hệ điều hành (iOS, Android, Windows, macOS, Linux)');
                $table->string('screen_resolution', 25)->nullable()->comment('Độ phân giải màn hình (vd: 390x844, 1920x1080)');
                $table->text('referrer_url')->nullable()->comment('Nguồn trang giới thiệu truy cập');
                $table->string('landing_page', 255)->nullable()->comment('Trang bắt đầu vào');
                $table->string('utm_source', 50)->nullable()->index();
                $table->string('utm_medium', 50)->nullable();
                $table->string('utm_campaign', 50)->nullable();
                $table->unsignedInteger('total_reels_viewed')->default(0)->comment('Tổng số video đã xem trong phiên');
                $table->decimal('total_watch_time_seconds', 10, 2)->default(0)->comment('Tổng thời gian xem trong phiên (giây)');
                $table->timestamp('first_activity_at')->nullable()->index();
                $table->timestamp('last_activity_at')->nullable()->index();
                $table->timestamps();

                $table->index(['created_at', 'device_type']);
                $table->index(['account_id', 'created_at']);
                $table->index(['anonymous_user_id', 'created_at']);
            });
        }

        // 2. Bảng nhật ký phân tích chi tiết từng lượt xem video (Reel Watch & View Analytics)
        if (!Schema::hasTable('reel_watch_logs')) {
            Schema::create('reel_watch_logs', function (Blueprint $table) {
                $table->id();
                $table->string('session_token', 64)->index()->comment('Mã định danh phiên');
                $table->unsignedBigInteger('reel_id')->index()->comment('ID video Reel');
                $table->unsignedBigInteger('account_id')->nullable()->index()->comment('ID tài khoản nếu đã đăng nhập');
                $table->unsignedBigInteger('anonymous_user_id')->nullable()->index()->comment('ID khách vãng lai');
                $table->decimal('watch_time_seconds', 8, 2)->default(0)->comment('Thời gian thực tế người dùng xem video (giây)');
                $table->decimal('video_duration', 8, 2)->default(0)->comment('Thời lượng tổng thể của video (giây)');
                $table->decimal('completion_rate', 5, 2)->default(0)->comment('Tỷ lệ hoàn thành (0% - 100%+)');
                $table->boolean('is_completed')->default(false)->index()->comment('Đã xem hết 100% video ít nhất 1 lần');
                $table->unsignedInteger('loop_count')->default(0)->comment('Số lần xem lặp lại video');
                $table->boolean('is_muted')->default(false)->comment('Xem ở chế độ tắt tiếng hay có tiếng');
                $table->decimal('playback_rate', 3, 2)->default(1.00)->comment('Tốc độ phát (1.0x, 1.25x, etc.)');
                $table->boolean('interacted_like')->default(false)->comment('Có thả tim trong lần xem này');
                $table->boolean('interacted_comment')->default(false)->comment('Có bình luận trong lần xem này');
                $table->boolean('interacted_share')->default(false)->comment('Có chia sẻ trong lần xem này');
                $table->boolean('interacted_save')->default(false)->comment('Có lưu video trong lần xem này');
                $table->boolean('interacted_product_click')->default(false)->comment('Có bấm vào xem sản phẩm');
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('device_type', 20)->default('desktop')->index();
                $table->timestamps();

                $table->index(['reel_id', 'created_at']);
                $table->index(['reel_id', 'watch_time_seconds']);
                $table->index(['created_at', 'is_completed']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_watch_logs');
        Schema::dropIfExists('reel_visitor_sessions');
    }
};

