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
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement('ALTER TABLE appointments ENGINE=InnoDB');

        // Dọn user_id trỏ tới tài khoản đã bị xóa, nếu không MySQL từ chối tạo khóa ngoại.
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement('
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            UPDATE appointments a
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            LEFT JOIN users u ON u.id = a.user_id
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            SET a.user_id = NULL
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            WHERE a.user_id IS NOT NULL AND u.id IS NULL
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        ');

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table) {
            // Khách vãng lai đặt lịch được nên user_id nullable; xóa tài khoản thì
            // vẫn giữ lịch hẹn để cửa hàng liên hệ theo số điện thoại đã lưu.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->foreign('user_id')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->references('id')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->on('users')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropForeign(['user_id']);
        });
    }
};
