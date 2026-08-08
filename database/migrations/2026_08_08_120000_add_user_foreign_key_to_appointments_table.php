<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng appointments ban đầu sinh ra bằng MyISAM (default_storage_engine của
        // WAMP là MyISAM), engine này bỏ qua khóa ngoại. Ép InnoDB trước cho chắc,
        // phòng trường hợp migration chạy trên máy chưa sửa config/database.php.
        DB::statement('ALTER TABLE appointments ENGINE=InnoDB');

        // Dọn user_id trỏ tới tài khoản đã bị xóa, nếu không MySQL từ chối tạo khóa ngoại.
        DB::statement('
            UPDATE appointments a
            LEFT JOIN users u ON u.id = a.user_id
            SET a.user_id = NULL
            WHERE a.user_id IS NOT NULL AND u.id IS NULL
        ');

        Schema::table('appointments', function (Blueprint $table) {
            // Khách vãng lai đặt lịch được nên user_id nullable; xóa tài khoản thì
            // vẫn giữ lịch hẹn để cửa hàng liên hệ theo số điện thoại đã lưu.
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
