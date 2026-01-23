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
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Tên chương trình Flash Sale');
            $table->text('description')->nullable()->comment('Mô tả chương trình');
            $table->string('banner')->nullable()->comment('Ảnh banner chương trình');
            $table->timestamp('start_time')->comment('Thời gian bắt đầu');
            $table->timestamp('end_time')->comment('Thời gian kết thúc');
            $table->enum('status', ['draft', 'active', 'expired'])->default('draft')->comment('Trạng thái');
            $table->boolean('is_active')->default(true)->comment('Bật/tắt chương trình');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
