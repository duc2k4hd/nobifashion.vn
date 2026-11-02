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
        // 1. Bảng số liệu thống kê tổng hợp theo ngày (Daily Metrics Rollup)
        if (!Schema::hasTable('reel_daily_metrics')) {
            Schema::create('reel_daily_metrics', function (Blueprint $table) {
                $table->id();
                $table->date('metric_date')->index();
                $table->unsignedBigInteger('reel_id')->default(0)->comment('0: Tổng toàn hệ thống; > 0: Cho từng Reel cụ thể');
                $table->unsignedBigInteger('total_views')->default(0);
                $table->unsignedBigInteger('completed_views')->default(0);
                $table->double('total_watch_time_seconds', 14, 2)->default(0);
                $table->float('avg_completion_rate')->default(0);
                $table->unsignedBigInteger('total_loops')->default(0);
                $table->unsignedBigInteger('total_likes')->default(0);
                $table->unsignedBigInteger('total_comments')->default(0);
                $table->unsignedBigInteger('total_shares')->default(0);
                $table->unsignedBigInteger('total_saves')->default(0);
                $table->unsignedBigInteger('total_product_clicks')->default(0);
                $table->timestamps();

                $table->unique(['metric_date', 'reel_id'], 'uq_daily_metric_date_reel');
                $table->index(['metric_date', 'total_views'], 'idx_daily_metric_date_views');
            });
        }

        // 2. Bảng phân bổ thứ nguyên theo ngày (Thiết bị, Trình duyệt, OS, UTM)
        if (!Schema::hasTable('reel_visitor_daily_metrics')) {
            Schema::create('reel_visitor_daily_metrics', function (Blueprint $table) {
                $table->id();
                $table->date('metric_date')->index();
                $table->string('dimension_type', 30)->comment('device, platform, browser, utm_source, resolution');
                $table->string('dimension_value', 100);
                $table->unsignedBigInteger('sessions_count')->default(0);
                $table->double('total_watch_time_seconds', 14, 2)->default(0);
                $table->timestamps();

                $table->unique(['metric_date', 'dimension_type', 'dimension_value'], 'uq_visitor_daily_dimension');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_visitor_daily_metrics');
        Schema::dropIfExists('reel_daily_metrics');
    }
};

