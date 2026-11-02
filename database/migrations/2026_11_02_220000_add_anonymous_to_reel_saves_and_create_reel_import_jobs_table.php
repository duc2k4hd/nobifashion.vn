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
        // 1. Bổ sung anonymous_user_id vào reel_saves để đồng bộ với reel_likes
        if (Schema::hasTable('reel_saves')) {
            Schema::table('reel_saves', function (Blueprint $table) {
                if (!Schema::hasColumn('reel_saves', 'anonymous_user_id')) {
                    $table->unsignedBigInteger('anonymous_user_id')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('reel_saves', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });

            try {
                Schema::table('reel_saves', function (Blueprint $table) {
                    $table->unique(['reel_id', 'anonymous_user_id'], 'uq_reel_save_anon');
                });
            } catch (\Throwable $e) {}
        }

        // 2. Bảng quản lý tiến trình và lịch sử Import video hàng loạt (Reel Import Jobs)
        if (!Schema::hasTable('reel_import_jobs')) {
            Schema::create('reel_import_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('file_name', 255);
                $table->string('source_type', 30)->default('csv')->comment('csv, excel, r2_bucket, json_list');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('processed_rows')->default(0);
                $table->unsignedInteger('successful_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->unsignedInteger('skipped_rows')->default(0);
                $table->string('status', 30)->default('pending')->comment('pending, processing, completed, failed, cancelled');
                $table->json('errors_log')->nullable()->comment('Danh sách chi tiết các dòng bị lỗi');
                $table->unsignedBigInteger('created_by')->nullable()->comment('ID tài khoản admin thực hiện');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_import_jobs');

        if (Schema::hasTable('reel_saves')) {
            Schema::table('reel_saves', function (Blueprint $table) {
                if (Schema::hasColumn('reel_saves', 'anonymous_user_id')) {
                    try {
                        $table->dropUnique('uq_reel_save_anon');
                    } catch (\Throwable $e) {}
                    $table->dropColumn(['anonymous_user_id', 'updated_at']);
                }
            });
        }
    }
};

