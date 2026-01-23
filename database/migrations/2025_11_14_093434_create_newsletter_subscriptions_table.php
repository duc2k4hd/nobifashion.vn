<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();

            // Email đăng ký
            $table->string('email')->unique();

            // Trạng thái
            // pending: vừa đăng ký
            // subscribed: đã xác nhận hoặc được chấp nhận
            // unsubscribed: đã hủy
            $table->enum('status', ['pending', 'subscribed', 'unsubscribed'])
                  ->default('pending')
                  ->index();

            // Token xác nhận
            $table->string('verify_token')->nullable()->index();

            // IP & user agent
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Nguồn đăng ký (popup, footer, banner, campaign…)
            $table->string('source')->nullable();

            // Ngày xác nhận email
            $table->timestamp('verified_at')->nullable();

            // Ghi chú thêm nếu cần
            $table->text('note')->nullable();

            // Tự động
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
