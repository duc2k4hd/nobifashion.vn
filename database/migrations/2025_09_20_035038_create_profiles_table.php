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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                ->constrained('accounts')
                ->onDelete('cascade'); // Xóa user thì xóa luôn profile

            $table->string('full_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('avatar')->nullable();     // ảnh chính
            $table->string('sub_avatar')->nullable(); // ảnh phụ (cover, thumbnail)

            $table->text('bio')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('birthday')->nullable();

            $table->string('location')->nullable();
            $table->string('phone')->nullable();

            $table->boolean('is_public')->default(true); // bật/tắt hiển thị public

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
