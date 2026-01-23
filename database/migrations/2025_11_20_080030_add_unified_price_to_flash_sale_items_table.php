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
        Schema::table('flash_sale_items', function (Blueprint $table) {
            // Thêm unified_price: Giá đồng nhất cho tất cả variants của sản phẩm
            // NULL = dùng sale_price riêng cho từng variant
            // Có giá trị = tất cả variants đều dùng giá này
            if (!Schema::hasColumn('flash_sale_items', 'unified_price')) {
                $table->decimal('unified_price', 15, 2)
                    ->nullable()
                    ->after('sale_price')
                    ->comment('Giá đồng nhất cho tất cả variants. NULL = dùng sale_price riêng');
            }
            
            // Thêm original_variant_price để lưu giá gốc của variant (dùng khi flash sale kết thúc)
            if (!Schema::hasColumn('flash_sale_items', 'original_variant_price')) {
                $table->decimal('original_variant_price', 15, 2)
                    ->nullable()
                    ->after('unified_price')
                    ->comment('Giá gốc của variant (backup để restore khi flash sale kết thúc)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('flash_sale_items', 'original_variant_price')) {
                $table->dropColumn('original_variant_price');
            }
            if (Schema::hasColumn('flash_sale_items', 'unified_price')) {
                $table->dropColumn('unified_price');
            }
        });
    }
};
