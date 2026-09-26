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
            // Thêm index cho name và path để tối ưu hóa truy vấn tìm kiếm và đối soát ảnh siêu tốc
            $indexes = Schema::getIndexes('images');
            $indexNames = array_column($indexes, 'name');

            if (!in_array('images_name_index', $indexNames, true)) {
                $table->index('name', 'images_name_index');
            }

            if (!in_array('images_path_index', $indexNames, true)) {
                $table->index('path', 'images_path_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $indexes = Schema::getIndexes('images');
            $indexNames = array_column($indexes, 'name');

            if (in_array('images_name_index', $indexNames, true)) {
                $table->dropIndex('images_name_index');
            }

            if (in_array('images_path_index', $indexNames, true)) {
                $table->dropIndex('images_path_index');
            }
        });
    }
};
