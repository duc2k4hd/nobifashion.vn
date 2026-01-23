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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Quan hệ
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');

            // Phương thức
            $table->enum('method', [
                'cod', 'bank_transfer', 'qr', 'momo', 'zalopay', 'vnpay', 'credit_card'
            ]);
            $table->decimal('amount', 10, 2);

            // Gateway info
            $table->string('gateway')->nullable();          // momo, vnpay, stripe...
            $table->string('transaction_code')->nullable(); // mã giao dịch từ gateway
            $table->json('raw_response')->nullable();       // toàn bộ response JSON

            // Card info (nullable nếu không phải credit card)
            $table->string('card_brand')->nullable();
            $table->string('last_four')->nullable();

            // Khác
            $table->string('receipt_url')->nullable();
            $table->text('notes')->nullable();

            // Trạng thái
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Xem Flow chuẩn trong file flow.txt


        // Logic hoạt động

        // Khi tạo order → mặc định order.payment_status = pending.

        // Khi gọi cổng thanh toán → tạo bản ghi payments (status = pending).

        // Khi cổng báo thành công → update payments.status = success, order.payment_status = paid.

        // Nếu thanh toán thất bại → payments.status = failed, order vẫn pending.

        // Nếu refund → thêm 1 bản ghi payments với status = refunded.

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
