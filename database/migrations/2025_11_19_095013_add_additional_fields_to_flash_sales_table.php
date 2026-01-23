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
            // Tag/Label cho Flash Sale
            $table->string('tag', 50)->nullable()->after('banner');
            
            // Giới hạn mua mỗi khách (global)
            $table->unsignedInteger('max_per_user')->nullable()->after('is_active');
            
            // Số lượng hiển thị trên frontend
            $table->unsignedInteger('display_limit')->default(20)->after('max_per_user');
            
            // Lock mechanism
            $table->boolean('is_locked')->default(false)->after('is_active');
            
            // Soft delete
            $table->softDeletes()->after('updated_at');
            
            // Account tạo
            $table->foreignId('created_by')->nullable()->after('is_active')
                  ->constrained('accounts')->onDelete('set null');
            
            // Lượt xem (nếu cần)
            $table->unsignedBigInteger('views')->default(0)->after('display_limit');
            
            // Indexes
            $table->index('status');
            $table->index('start_time');
            $table->index('end_time');
            $table->index(['status', 'is_active', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_active', 'start_time', 'end_time']);
            $table->dropIndex(['end_time']);
            $table->dropIndex(['start_time']);
            $table->dropIndex(['status']);
            
            $table->dropColumn([
                'tag',
                'max_per_user',
                'display_limit',
                'is_locked',
                'deleted_at',
                'created_by',
                'views'
            ]);
        });
    }
};
