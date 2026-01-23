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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // Thông tin người gửi
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            // Nội dung liên hệ
            $table->string('subject')->nullable();
            $table->text('message')->nullable();

            // File đính kèm (nếu có)
            $table->string('attachment')->nullable();

            // Trạng thái xử lý
            $table->enum('status', [
                'new',        // Mới gửi
                'processing', // Đang xử lý
                'done',       // Đã xử lý
                'spam',       // Đánh dấu spam
            ])->default('new');

            // Thông tin gửi
            $table->string('source')->nullable();      // web, landingpage, mobile app...
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Nếu người dùng đã đăng nhập khi gửi liên hệ
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('accounts')->onDelete('set null');

            // Ghi chú nội bộ (admin xử lý)
            $table->text('admin_note')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Hỗ trợ xóa mềm
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};


// name, email, phone	Thông tin người gửi
// subject	Tiêu đề liên hệ
// message	Nội dung chi tiết
// attachment	File đính kèm (nếu có upload)
// status	Trạng thái xử lý của admin
// source	Nguồn gửi (hữu ích cho tracking)
// ip_address, user_agent	Ghi lại để chống spam hoặc tracking
// user_id	Nếu người dùng có tài khoản
// admin_note	Admin ghi chú khi xử lý
// softDeletes()	Hỗ trợ khôi phục khi xóa nhầm
