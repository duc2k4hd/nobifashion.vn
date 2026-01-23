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
            // Thứ tự hiển thị (chỉ thêm nếu chưa có)
            if (!Schema::hasColumn('flash_sale_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
            
            // Product variant (nếu cần - optional)
            if (!Schema::hasColumn('flash_sale_items', 'product_variant_id')) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')
                      ->constrained('product_variants')->onDelete('cascade');
            }
        });

        // Thêm indexes và unique constraint riêng để dễ xử lý lỗi
        try {
            Schema::table('flash_sale_items', function (Blueprint $table) {
                // Unique constraint: không trùng product trong cùng flash sale
                $table->unique(['flash_sale_id', 'product_id'], 'unique_flash_sale_product');
            });
        } catch (\Exception $e) {
            // Index đã tồn tại, bỏ qua
        }

        try {
            Schema::table('flash_sale_items', function (Blueprint $table) {
                // Indexes
                $table->index('flash_sale_id');
                $table->index('product_id');
                $table->index('is_active');
                $table->index(['flash_sale_id', 'is_active']);
            });
        } catch (\Exception $e) {
            // Indexes đã tồn tại, bỏ qua
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_sale_items', function (Blueprint $table) {
            // Drop indexes nếu tồn tại
            try {
                $table->dropIndex(['flash_sale_id', 'is_active']);
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex(['is_active']);
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex(['product_id']);
            } catch (\Exception $e) {}
            
            try {
                $table->dropIndex(['flash_sale_id']);
            } catch (\Exception $e) {}
            
            try {
                $table->dropUnique('unique_flash_sale_product');
            } catch (\Exception $e) {}
            
            // Drop columns nếu tồn tại
            if (Schema::hasColumn('flash_sale_items', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('flash_sale_items', 'product_variant_id')) {
                $table->dropColumn('product_variant_id');
            }
        });
    }
};
