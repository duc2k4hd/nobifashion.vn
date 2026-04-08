<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yody_temp_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->string('primary_category_slug')->nullable()->index();
            $table->unsignedInteger('variant_count')->default(0);
            $table->unsignedInteger('image_count')->default(0);
            $table->string('preview_relative_path')->nullable();
            $table->dateTime('first_crawled_at')->nullable()->index();
            $table->dateTime('last_crawled_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yody_temp_products');
    }
};
