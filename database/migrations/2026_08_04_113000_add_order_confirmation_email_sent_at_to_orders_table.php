<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cột này giúp hệ thống biết đơn nào đã gửi email xác nhận thành công.
        // Nhất là VNPay có cả return URL và IPN, nếu không đánh dấu thì dễ gửi email trùng.
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'order_confirmation_email_sent_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('order_confirmation_email_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        // Khi rollback chỉ xóa cột đánh dấu email, không đụng tới dữ liệu đơn hàng khác.
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_confirmation_email_sent_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('order_confirmation_email_sent_at');
        });
    }
};
