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
        Schema::create('product_how_tos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade'); // Xóa sản phẩm thì xóa luôn how-to

            $table->string('title');          // Tiêu đề hướng dẫn
            $table->text('description')->nullable(); // Mô tả ngắn về hướng dẫn

            $table->json('steps')->nullable();    // Các bước hướng dẫn (mảng JSON)
            $table->json('supplies')->nullable(); // Vật tư/dụng cụ cần thiết (mảng JSON)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_how_tos');
    }
};
