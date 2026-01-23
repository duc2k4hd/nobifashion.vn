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
        Schema::table('orders', function (Blueprint $table) {
            // Thêm PayOS vào enum payment_method
            $table->enum('payment_method', [
                'cod', 'bank_transfer', 'qr', 'momo', 'zalopay', 'payos'
            ])->default('cod')->change();
            
            // Thêm voucher fields nếu chưa có
            if (!Schema::hasColumn('orders', 'voucher_id')) {
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
                $table->decimal('voucher_discount', 10, 2)->default(0);
                $table->string('voucher_code')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert payment_method enum
            $table->enum('payment_method', [
                'cod', 'bank_transfer', 'qr', 'momo', 'zalopay'
            ])->default('cod')->change();
            
            // Remove voucher fields if they were added
            if (Schema::hasColumn('orders', 'voucher_id')) {
                $table->dropForeign(['voucher_id']);
                $table->dropColumn(['voucher_id', 'voucher_discount', 'voucher_code']);
            }
        });
    }
};
