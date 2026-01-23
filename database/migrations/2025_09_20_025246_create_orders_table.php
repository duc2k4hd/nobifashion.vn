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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('session_id')->nullable();

            // Giá tiền
            $table->decimal('total_price', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);

            // Thông tin người nhận
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->string('receiver_email')->nullable();

            $table->string('shipping_address'); // chi tiết
            $table->unsignedBigInteger('shipping_province_id');
            $table->unsignedBigInteger('shipping_district_id');
            $table->unsignedBigInteger('shipping_ward_id');

            // Thanh toán
            $table->enum('payment_method', ['cod', 'bank_transfer', 'qr', 'momo', 'zalopay'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('transaction_code')->nullable();

            // Vận chuyển
            $table->enum('shipping_partner', ['viettelpost', 'ghtk', 'ghn'])->default('viettelpost');
            $table->string('shipping_tracking_code')->nullable();
            $table->json('shipping_raw_response')->nullable();

            // Trạng thái đơn
            $table->enum('delivery_status', ['pending', 'shipped', 'delivered', 'returned'])->default('pending');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');

            // Ghi chú
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
