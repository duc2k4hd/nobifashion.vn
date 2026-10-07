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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('campaign')->nullable()->after('description');
            $table->unsignedInteger('followers_count')->default(2000)->after('campaign');
            $table->decimal('rating_score', 3, 2)->default(4.90)->after('followers_count');
            $table->unsignedTinyInteger('joined_years')->default(9)->after('rating_score');
            $table->json('banner_slides')->nullable()->after('joined_years');
            $table->json('faqs')->nullable()->after('banner_slides');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['campaign', 'followers_count', 'rating_score', 'joined_years', 'banner_slides', 'faqs']);
        });
    }
};
