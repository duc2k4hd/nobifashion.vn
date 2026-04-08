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
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku')->unique()->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('product_variants', 'name')) {
                $table->string('name')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('product_variants', 'sale_price')) {
                $table->decimal('sale_price', 10, 0)->nullable()->after('price');
            }
            if (!Schema::hasColumn('product_variants', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['sku', 'name', 'sale_price', 'is_active']);
        });
    }
};
