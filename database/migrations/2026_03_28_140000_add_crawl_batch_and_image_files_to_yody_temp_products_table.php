<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yody_temp_products', function (Blueprint $table) {
            if (!Schema::hasColumn('yody_temp_products', 'crawl_batch')) {
                $table->string('crawl_batch')->nullable()->after('primary_category_slug')->index();
            }

            if (!Schema::hasColumn('yody_temp_products', 'image_files')) {
                $table->json('image_files')->nullable()->after('image_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('yody_temp_products', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('yody_temp_products', 'crawl_batch')) {
                $dropColumns[] = 'crawl_batch';
            }

            if (Schema::hasColumn('yody_temp_products', 'image_files')) {
                $dropColumns[] = 'image_files';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
