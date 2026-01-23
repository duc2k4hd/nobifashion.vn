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
        Schema::create('product_faqs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade'); // nếu xóa product thì xóa luôn FAQ

            $table->string('question');    // Câu hỏi
            $table->text('answer')->nullable(); // Trả lời (có thể để null nếu admin chưa trả lời)

            $table->integer('order')->default(0); // Thứ tự hiển thị

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_faqs');
    }
};
