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
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->enum('product_add_mode', ['auto_by_category', 'manual'])->nullable()->after('display_limit')->comment('Chế độ thêm sản phẩm: tự động theo danh mục hoặc thủ công');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn('product_add_mode');
        });
    }
};
