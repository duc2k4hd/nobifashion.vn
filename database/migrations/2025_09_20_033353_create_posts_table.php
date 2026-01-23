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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Nội dung chính
            $table->string('title');
            $table->string('slug')->unique();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('meta_canonical')->nullable();
            $table->json('tag_ids')->nullable();

            // Nội dung bài viết
            $table->text('excerpt')->nullable();   // tóm tắt
            $table->longText('content')->nullable();

            // Hình ảnh
            $table->string('thumbnail')->nullable();          // link ảnh chính
            $table->string('thumbnail_alt_text')->nullable(); // alt cho SEO

            // Trạng thái
            $table->enum('status', ['draft', 'pending', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false); // bài nổi bật

            // Thống kê
            $table->unsignedBigInteger('views')->default(0);

            // Quan hệ
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            // Thời gian
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
