<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            // Liên kết với bảng products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment('ID sản phẩm liên kết');

            $table->string('title')->nullable()->comment('Tiêu đề ảnh');
            $table->text('notes')->nullable()->comment('Ghi chú thêm cho ảnh');
            $table->string('alt')->nullable()->comment('Alt text cho SEO');
            
            $table->string('url')->comment('Link ảnh gốc');
            $table->string('thumbnail_url')->nullable()->comment('Link ảnh thumbnail');
            $table->string('medium_url')->nullable()->comment('Link ảnh kích thước trung bình');

            $table->boolean('is_primary')->default(false)->comment('Ảnh chính của sản phẩm');
            $table->unsignedInteger('order')->default(0)->comment('Thứ tự hiển thị');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
