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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // SEO & hiển thị
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('usage_count')->default(0);

            // Quan hệ trực tiếp
            $table->unsignedBigInteger('entity_id');    // ID của entity (product_id hoặc post_id)
            $table->string('entity_type');              // 'product' | 'post' | 'category'...

            $table->timestamps();

            $table->index(['entity_id', 'entity_type']);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
