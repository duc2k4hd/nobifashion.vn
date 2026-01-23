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
        Schema::table('profiles', function (Blueprint $table) {
            // 通知偏好设置
            $table->boolean('notify_order_created')->default(true)->after('is_public');
            $table->boolean('notify_order_updated')->default(true)->after('notify_order_created');
            $table->boolean('notify_order_shipped')->default(true)->after('notify_order_updated');
            $table->boolean('notify_order_completed')->default(true)->after('notify_order_shipped');
            $table->boolean('notify_promotions')->default(true)->after('notify_order_completed');
            $table->boolean('notify_flash_sale')->default(true)->after('notify_promotions');
            $table->boolean('notify_new_products')->default(false)->after('notify_flash_sale');
            $table->boolean('notify_stock_alert')->default(false)->after('notify_new_products');
            $table->boolean('notify_security')->default(true)->after('notify_stock_alert');
            
            // 通知方式
            $table->boolean('notify_via_email')->default(true)->after('notify_security');
            $table->boolean('notify_via_sms')->default(false)->after('notify_via_email');
            $table->boolean('notify_via_in_app')->default(true)->after('notify_via_sms');
            
            // 隐私设置
            $table->boolean('show_order_history')->default(true)->after('notify_via_in_app');
            $table->boolean('show_favorites')->default(true)->after('show_order_history');
            
            // 偏好设置
            $table->string('preferred_language', 10)->default('vi')->after('show_favorites');
            $table->string('preferred_timezone', 50)->default('Asia/Ho_Chi_Minh')->after('preferred_language');
            $table->string('preferred_currency', 10)->default('VND')->after('preferred_timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'notify_order_created',
                'notify_order_updated',
                'notify_order_shipped',
                'notify_order_completed',
                'notify_promotions',
                'notify_flash_sale',
                'notify_new_products',
                'notify_stock_alert',
                'notify_security',
                'notify_via_email',
                'notify_via_sms',
                'notify_via_in_app',
                'show_order_history',
                'show_favorites',
                'preferred_language',
                'preferred_timezone',
                'preferred_currency',
            ]);
        });
    }
};
