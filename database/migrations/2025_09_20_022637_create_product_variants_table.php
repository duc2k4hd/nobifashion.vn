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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('price', 10, 0)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->json('attributes')->nullable(); // { "size": "M", "color": "Black" }
            // Ảnh được tạo ở migration sau và runtime hiện tại cũng không dùng FK thật cho bảng này.
            $table->unsignedBigInteger('image_id')->nullable()->index();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
