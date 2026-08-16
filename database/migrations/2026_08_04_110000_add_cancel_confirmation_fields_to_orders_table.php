<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Một số môi trường test/schema cũ có thể chưa tạo bảng orders.
        // Kiểm tra trước để migration không làm hỏng quá trình chạy lệnh.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('orders')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('orders', function (Blueprint $table): void {
            // Lưu hash của token xác nhận, không lưu token thật.
            // Token thật chỉ nằm trong email gửi cho khách.
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('orders', 'cancel_confirmation_token_hash')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->string('cancel_confirmation_token_hash', 64)->nullable();
            }

            // Lưu lý do admin nhập khi yêu cầu hủy để đưa vào email và note đơn hàng.
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('orders', 'cancel_reason')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->text('cancel_reason')->nullable();
            }

            // Thời điểm admin gửi yêu cầu hủy, dùng để kiểm tra link hết hạn sau 3 ngày.
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('orders', 'cancel_requested_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('cancel_requested_at')->nullable();
            }

            // Thời điểm khách bấm xác nhận hủy thành công.
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('orders', 'cancel_confirmed_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('cancel_confirmed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Rollback chỉ xóa các cột của luồng xác nhận hủy.
        // Các cột khác của orders được giữ nguyên.
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('orders')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Gan ket qua xu ly vao bien $columns.
        $columns = array_values(array_filter([
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'cancel_confirmation_token_hash',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'cancel_reason',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'cancel_requested_at',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'cancel_confirmed_at',
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        ], fn (string $column): bool => Schema::hasColumn('orders', $column)));

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($columns === []) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('orders', function (Blueprint $table) use ($columns): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropColumn($columns);
        });
    }
};
