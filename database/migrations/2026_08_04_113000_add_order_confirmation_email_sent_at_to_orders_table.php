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
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'order_confirmation_email_sent_at')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('orders', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('order_confirmation_email_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        // Khi rollback chỉ xóa cột đánh dấu email, không đụng tới dữ liệu đơn hàng khác.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_confirmation_email_sent_at')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('orders', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropColumn('order_confirmation_email_sent_at');
        });
    }
};
