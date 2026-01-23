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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Ai comment
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Nội dung
            $table->text('content');
            $table->boolean('is_approved')->default(false);

            // Quan hệ polymorphic (có thể là post, product, ... )
            $table->unsignedBigInteger('commentable_id');   
            $table->string('commentable_type'); // 'App\Models\Post' hoặc 'App\Models\Product'

            $table->timestamps();

            $table->index(['commentable_id', 'commentable_type']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
