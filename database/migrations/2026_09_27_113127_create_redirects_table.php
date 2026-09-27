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
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_url', 500)->comment('Link cũ hoặc slug cũ');
            $table->char('old_url_hash', 32)->comment('MD5 của link cũ đã chuẩn hóa để tra cứu O(1)');
            $table->string('new_url', 500)->comment('Link mới hoặc slug mới được chuyển hướng tới');
            $table->unsignedSmallInteger('status_code')->default(301)->comment('HTTP status code: 301 hoặc 302');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt');
            $table->unsignedBigInteger('hits')->default(0)->comment('Số lần đã kích hoạt chuyển hướng');
            $table->timestamp('last_accessed_at')->nullable()->comment('Thời điểm truy cập chuyển hướng lần cuối');
            $table->string('note', 255)->nullable()->comment('Ghi chú quản trị');
            $table->timestamps();

            // Indexes tối ưu hiệu năng
            $table->unique('old_url_hash', 'redirects_old_url_hash_unique');
            $table->index('is_active', 'redirects_is_active_idx');
            $table->index('hits', 'redirects_hits_idx');
            $table->index('created_at', 'redirects_created_at_idx');
        });

        // Add prefix index on old_url and new_url for partial text search
        \Illuminate\Support\Facades\DB::statement('CREATE INDEX redirects_old_url_idx ON redirects (old_url(191))');
        \Illuminate\Support\Facades\DB::statement('CREATE INDEX redirects_new_url_idx ON redirects (new_url(191))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
