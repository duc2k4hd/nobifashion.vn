<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kiểm tra xem bảng products có tồn tại không
        if (!Schema::hasTable('products')) {
            throw new \Exception('Bảng products chưa tồn tại. Vui lòng chạy migration tạo bảng products trước.');
        }

        Schema::create('product_slug_redirects', function (Blueprint $table) {
            $table->id();
            // Đảm bảo product_id có cùng kiểu với products.id (bigIncrements = unsignedBigInteger)
            $table->unsignedBigInteger('product_id');
            $table->string('old_slug')->unique();
            $table->string('new_slug');
            $table->timestamps();

            // Tạo index
            $table->index('old_slug');
            $table->index('product_id');
        });

        // Thử tạo foreign key constraint
        // Nếu không được (do engine MyISAM hoặc charset không khớp), sẽ bỏ qua
        // Logic xử lý cascade delete sẽ được xử lý ở application level
        try {
            DB::statement('ALTER TABLE `product_slug_redirects` 
                ADD CONSTRAINT `product_slug_redirects_product_id_foreign` 
                FOREIGN KEY (`product_id`) 
                REFERENCES `products` (`id`) 
                ON DELETE CASCADE');
        } catch (\Illuminate\Database\QueryException $e) {
            // Nếu lỗi do foreign key constraint, bỏ qua và tiếp tục
            // Foreign key sẽ được xử lý ở application level thông qua Model events
            if (str_contains($e->getMessage(), 'Foreign key constraint') || 
                str_contains($e->getMessage(), 'errno: 150')) {
                // Chỉ log warning, không throw exception
                Log::warning('Không thể tạo foreign key cho product_slug_redirects. Sẽ xử lý ở application level.');
            } else {
                // Nếu lỗi khác, throw lại
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_slug_redirects');
    }
};
