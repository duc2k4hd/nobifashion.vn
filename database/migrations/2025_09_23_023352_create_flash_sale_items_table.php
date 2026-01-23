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
        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->decimal('original_price', 15, 2)->nullable()->comment('Giá gốc');
            $table->decimal('sale_price', 15, 2)->comment('Giá khuyến mãi');
            $table->unsignedInteger('stock')->default(0)->comment('Số lượng trong Flash Sale');
            $table->unsignedInteger('sold')->default(0)->comment('Số lượng đã bán');
            $table->unsignedInteger('max_per_user')->nullable()->comment('Giới hạn mua mỗi user');

            $table->boolean('is_active')->default(true)->comment('Bật/tắt sản phẩm này trong Flash Sale');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sale_items');
    }
};
