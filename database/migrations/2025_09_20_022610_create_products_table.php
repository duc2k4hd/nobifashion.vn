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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('sku')->unique()->nullable();
            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();

            $table->integer('stock_quantity')->default(0);

            // SEO
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_canonical')->nullable();

            // Category
            $table->unsignedBigInteger('primary_category_id')->nullable()->index();
            $table->json('category_ids')->nullable();
            $table->json('tag_ids')->nullable();

            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('has_variants')->default(false);

            // Locking
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_active')->default(true); 
            $table->timestamps();

            // Indexes
            $table->index('created_by');
            $table->foreign('locked_by')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('primary_category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
