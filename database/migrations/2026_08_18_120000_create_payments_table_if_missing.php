<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng payments chưa từng có migration nào tạo ra: nó chỉ tồn tại trong file
 * luanvan_ban_mat_kinh.sql. Hệ quả là một cài đặt dựng hoàn toàn bằng `migrate`
 * sẽ thiếu bảng này, và mọi lần xác nhận thanh toán VNPay đều ném QueryException.
 * Lỗi đó lại bị nuốt bởi `catch (RuntimeException)` trong VnPayController
 * (QueryException kế thừa PDOException, mà PDOException kế thừa RuntimeException),
 * nên IPN chỉ trả về RspCode 99 "Cannot confirm order" — đơn không bao giờ được
 * đánh dấu đã thanh toán và không có dấu vết lỗi rõ ràng.
 *
 * Cấu trúc dưới đây lấy đúng theo DDL trong file .sql để không lệch với các
 * môi trường đã dựng sẵn từ dump.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('payment_code', 100)->unique();
            // Dùng string thay cho enum: enum khiến mọi lần thêm phương thức thanh
            // toán mới đều phải ALTER TABLE, và SQLite (dùng cho test) không có enum.
            $table->string('method', 20);
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('PENDING');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->string('transaction_no')->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('response_message')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status'], 'idx_payments_order_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
