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
        Schema::table('images', function (Blueprint $table) {
            // Thêm các cột metadata còn thiếu vào bảng images
            // Nếu cột nào đã tồn tại, ta sẽ bỏ qua bước thêm cột đó (dùng hasColumn để an toàn)
            if (!Schema::hasColumn('images', 'path')) {
                $table->string('path')->nullable()->comment('Đường dẫn tương đối của file');
            }
            if (!Schema::hasColumn('images', 'extension')) {
                $table->string('extension', 10)->nullable();
            }
            if (!Schema::hasColumn('images', 'mime_type')) {
                $table->string('mime_type')->nullable();
            }
            if (!Schema::hasColumn('images', 'size')) {
                $table->unsignedBigInteger('size')->default(0);
            }
            if (!Schema::hasColumn('images', 'width')) {
                $table->unsignedInteger('width')->nullable();
            }
            if (!Schema::hasColumn('images', 'height')) {
                $table->unsignedInteger('height')->nullable();
            }
            if (!Schema::hasColumn('images', 'context')) {
                $table->string('context')->default('product')->index();
            }
            if (!Schema::hasColumn('images', 'file_modified_at')) {
                $table->timestamp('file_modified_at')->nullable();
            }

            // Đồng thời làm product_id nullable
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->dropColumn([
                'path', 'extension', 'mime_type', 'size', 'width', 'height', 'context', 'file_modified_at', 'temp_lib_check'
            ]);
        });
    }
};
