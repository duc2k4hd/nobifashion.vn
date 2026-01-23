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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'is_flash_sale')) {
                $table->boolean('is_flash_sale')
                    ->default(false)
                    ->after('status');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'is_flash_sale')) {
                $table->boolean('is_flash_sale')
                    ->default(false)
                    ->after('product_variant_id');
            }

            if (!Schema::hasColumn('order_items', 'flash_sale_item_id')) {
                $table->foreignId('flash_sale_item_id')
                    ->nullable()
                    ->after('is_flash_sale')
                    ->constrained('flash_sale_items')
                    ->nullOnDelete();
            }
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_items', 'is_flash_sale')) {
                $table->boolean('is_flash_sale')
                    ->default(false)
                    ->after('status');
            }

            if (!Schema::hasColumn('cart_items', 'flash_sale_item_id')) {
                $table->foreignId('flash_sale_item_id')
                    ->nullable()
                    ->after('is_flash_sale')
                    ->constrained('flash_sale_items')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'is_flash_sale')) {
                $table->dropColumn('is_flash_sale');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'flash_sale_item_id')) {
                $table->dropConstrainedForeignId('flash_sale_item_id');
            }
            if (Schema::hasColumn('order_items', 'is_flash_sale')) {
                $table->dropColumn('is_flash_sale');
            }
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'flash_sale_item_id')) {
                $table->dropConstrainedForeignId('flash_sale_item_id');
            }
            if (Schema::hasColumn('cart_items', 'is_flash_sale')) {
                $table->dropColumn('is_flash_sale');
            }
        });
    }
};
