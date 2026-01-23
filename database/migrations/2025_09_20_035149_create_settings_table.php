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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();   // ví dụ: site_name, site_logo, contact_email
            $table->text('value')->nullable(); // giá trị (text, json, number...)

            $table->string('type')->default('string'); // kiểu dữ liệu: string, number, boolean, json
            $table->string('group')->nullable();       // nhóm: general, seo, payment, email...

            $table->string('label')->nullable();       // nhãn hiển thị trong admin
            $table->text('description')->nullable();   // mô tả field

            $table->boolean('is_public')->default(true);    // cho phép public ra frontend
            $table->boolean('is_required')->default(false); // bắt buộc nhập

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
