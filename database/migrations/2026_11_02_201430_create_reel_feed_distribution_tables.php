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
        // 1. Bảng lưu trữ Snapshots xếp hạng dùng chung (Shared Feed Snapshots)
        if (!Schema::hasTable('reel_ranking_snapshots')) {
            Schema::create('reel_ranking_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('version')->unique();
                $table->unsignedInteger('total_videos')->default(0);
                $table->json('trending_ids')->nullable(); // Danh sách IDs theo Engine A (Trending & Popularity)
                $table->json('newest_ids')->nullable();   // Danh sách IDs theo Engine B (Newest & Discovery)
                $table->string('status', 30)->default('active'); // active | expired
                $table->timestamp('generated_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'version'], 'idx_snapshot_status_ver');
            });
        }

        // 2. Bảng quản lý Vòng phân phối của từng người dùng (Full Coverage Feed Cycle)
        if (!Schema::hasTable('reel_feed_cycles')) {
            Schema::create('reel_feed_cycles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anonymous_user_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedInteger('snapshot_version');
                $table->unsignedBigInteger('random_seed');
                $table->unsignedInteger('current_index')->default(0); // Vị trí con trỏ xem hiện tại
                $table->unsignedInteger('total_delivered')->default(0); // Tổng số video đã phát cho client
                $table->unsignedInteger('total_videos')->default(0);    // Tổng số video trong vòng này
                $table->longText('sequence')->nullable();              // Chuỗi ID đầy đủ 100% video không trùng lặp
                $table->string('status', 20)->default('active');       // active | completed
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['anonymous_user_id', 'status'], 'idx_cycle_anon_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_feed_cycles');
        Schema::dropIfExists('reel_ranking_snapshots');
    }
};
