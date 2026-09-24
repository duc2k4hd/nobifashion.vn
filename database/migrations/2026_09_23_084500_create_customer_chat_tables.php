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
        if (!Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 64)->index()->comment('UUID định danh phiên khách hàng');
                $table->unsignedBigInteger('account_id')->nullable()->index()->comment('ID tài khoản nếu đã đăng nhập');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('status', 20)->default('active')->index()->comment('active, closed');
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
            });

            // Tách Foreign Key sang try/catch để không bị lỗi errno 150 nếu accounts dùng MyISAM hoặc khác engine/collation
            try {
                Schema::table('chat_conversations', function (Blueprint $table) {
                    $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Bỏ qua nếu CSDL không hỗ trợ foreign key (MyISAM), index đã được tạo ở trên đảm bảo tốc độ cao
            }
        }

        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->string('sender_type', 20)->default('user')->comment('user, bot, admin');
                $table->text('message')->comment('Nội dung tin nhắn');
                $table->json('metadata')->nullable()->comment('Lưu danh sách articles, highlights');
                $table->timestamps();
                $table->index(['conversation_id', 'created_at']);
            });

            try {
                Schema::table('chat_messages', function (Blueprint $table) {
                    $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Bỏ qua nếu CSDL không hỗ trợ foreign key
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
