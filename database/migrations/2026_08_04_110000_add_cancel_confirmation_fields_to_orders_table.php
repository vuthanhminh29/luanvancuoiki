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
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            // Lưu hash của token xác nhận, không lưu token thật.
            // Token thật chỉ nằm trong email gửi cho khách.
            if (! Schema::hasColumn('orders', 'cancel_confirmation_token_hash')) {
                $table->string('cancel_confirmation_token_hash', 64)->nullable();
            }

            // Lưu lý do admin nhập khi yêu cầu hủy để đưa vào email và note đơn hàng.
            if (! Schema::hasColumn('orders', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable();
            }

            // Thời điểm admin gửi yêu cầu hủy, dùng để kiểm tra link hết hạn sau 3 ngày.
            if (! Schema::hasColumn('orders', 'cancel_requested_at')) {
                $table->timestamp('cancel_requested_at')->nullable();
            }

            // Thời điểm khách bấm xác nhận hủy thành công.
            if (! Schema::hasColumn('orders', 'cancel_confirmed_at')) {
                $table->timestamp('cancel_confirmed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Rollback chỉ xóa các cột của luồng xác nhận hủy.
        // Các cột khác của orders được giữ nguyên.
        if (! Schema::hasTable('orders')) {
            return;
        }

        $columns = array_values(array_filter([
            'cancel_confirmation_token_hash',
            'cancel_reason',
            'cancel_requested_at',
            'cancel_confirmed_at',
        ], fn (string $column): bool => Schema::hasColumn('orders', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
