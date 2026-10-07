<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'product_recommen'],
            [
                'value'       => null,
                'type'        => 'integer',
                'group'       => 'product',
                'label'       => 'Danh mục sản phẩm gợi ý',
                'description' => 'Chọn danh mục để hiển thị sản phẩm gợi ý trên các trang (Giỏ hàng, Giới thiệu, Liên hệ...). Nếu để trống hoặc không hợp lệ, hệ thống mặc định lấy 50% Thời trang nam và 50% Thời trang nữ.',
                'is_public'   => true,
                'is_required' => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'product_recommen')->delete();
    }
};
