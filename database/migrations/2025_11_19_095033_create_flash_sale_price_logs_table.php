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
        Schema::create('flash_sale_price_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_item_id')->constrained('flash_sale_items')->onDelete('cascade');
            $table->decimal('old_price', 15, 2);
            $table->decimal('new_price', 15, 2);
            $table->foreignId('changed_by')->constrained('accounts')->onDelete('cascade');
            $table->timestamp('changed_at');
            $table->text('reason')->nullable();
            $table->timestamps();
            
            $table->index('flash_sale_item_id');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_sale_price_logs');
    }
};
