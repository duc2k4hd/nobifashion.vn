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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();

            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');

            $table->enum('type', [
                'percentage',
                'fixed_amount',
                'free_shipping',
                'shipping_percentage',
                'shipping_fixed'
            ])->default('fixed_amount');

            $table->decimal('value', 10, 2)->default(0); // hỗ trợ số lẻ %
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('per_user_limit')->nullable();

            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();

            $table->enum('applicable_to', [
                'all_products',
                'specific_products',
                'specific_categories'
            ])->default('all_products');
            $table->json('applicable_ids')->nullable();

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            $table->enum('status', ['active', 'expired', 'disabled', 'scheduled'])->default('active');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
