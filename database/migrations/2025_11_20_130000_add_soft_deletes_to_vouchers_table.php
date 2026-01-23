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
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->index(['status', 'start_at', 'end_at'], 'vouchers_status_schedule_index');
            $table->index(['applicable_to'], 'vouchers_applicable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->dropIndex('vouchers_status_schedule_index');
            $table->dropIndex('vouchers_applicable_index');
        });
    }
};

