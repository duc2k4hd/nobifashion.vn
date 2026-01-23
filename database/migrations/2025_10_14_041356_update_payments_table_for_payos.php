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
        Schema::table('payments', function (Blueprint $table) {
            // Thêm PayOS vào enum method
            $table->enum('method', [
                'cod', 'bank_transfer', 'qr', 'momo', 'zalopay', 'vnpay', 'credit_card', 'payos'
            ])->change();
            
            // Thêm account_id nếu chưa có
            if (!Schema::hasColumn('payments', 'account_id')) {
                $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            }
            
            // Thêm gateway field nếu chưa có
            if (!Schema::hasColumn('payments', 'gateway')) {
                $table->string('gateway')->nullable();
            }
            
            // Thêm card_brand field nếu chưa có
            if (!Schema::hasColumn('payments', 'card_brand')) {
                $table->string('card_brand')->nullable();
            }
            
            // Thêm last_four field nếu chưa có
            if (!Schema::hasColumn('payments', 'last_four')) {
                $table->string('last_four')->nullable();
            }
            
            // Thêm receipt_url field nếu chưa có
            if (!Schema::hasColumn('payments', 'receipt_url')) {
                $table->string('receipt_url')->nullable();
            }
            
            // Thêm notes field nếu chưa có
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Revert method enum
            $table->enum('method', [
                'cod', 'bank_transfer', 'qr', 'momo', 'zalopay', 'vnpay', 'credit_card'
            ])->change();
            
            // Remove added columns
            if (Schema::hasColumn('payments', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }
            
            if (Schema::hasColumn('payments', 'gateway')) {
                $table->dropColumn('gateway');
            }
            
            if (Schema::hasColumn('payments', 'card_brand')) {
                $table->dropColumn('card_brand');
            }
            
            if (Schema::hasColumn('payments', 'last_four')) {
                $table->dropColumn('last_four');
            }
            
            if (Schema::hasColumn('payments', 'receipt_url')) {
                $table->dropColumn('receipt_url');
            }
            
            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
