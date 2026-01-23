<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'account_id', 'session_id'], 'favorites_unique_owner');
            $table->index(['account_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};


