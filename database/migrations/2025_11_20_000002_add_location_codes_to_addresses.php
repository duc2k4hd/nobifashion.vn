<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('province_code')->nullable()->after('province');
            $table->unsignedBigInteger('district_code')->nullable()->after('province_code');
            $table->string('ward_code')->nullable()->after('district_code');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['province_code', 'district_code', 'ward_code']);
        });
    }
};

