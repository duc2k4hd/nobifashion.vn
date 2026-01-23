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
        Schema::table('comments', function (Blueprint $table) {
            // Threaded comments (reply)
            $table->unsignedBigInteger('parent_id')->nullable()->after('id')->index();

            // Guest info (nếu không đăng nhập)
            $table->string('guest_name')->nullable()->after('account_id');
            $table->string('guest_email')->nullable()->after('guest_name');

            // Meta & anti-spam
            $table->string('ip_address', 45)->nullable()->after('is_approved');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->boolean('is_reported')->default(false)->after('user_agent');
            $table->unsignedInteger('reports_count')->default(0)->after('is_reported');

            // Soft delete để an toàn dữ liệu
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn([
                'parent_id',
                'guest_name',
                'guest_email',
                'ip_address',
                'user_agent',
                'is_reported',
                'reports_count',
                'deleted_at',
            ]);
        });
    }
};


