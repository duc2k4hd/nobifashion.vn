<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_excludes', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // url, post_id, category_id, pattern
            $table->string('value'); // URL path, ID, or regex pattern
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_excludes');
    }
};
