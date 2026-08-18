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
        // Chỉ MySQL mới có khái niệm storage engine nên phải chặn theo driver,
        // nếu không migration sẽ chết khi chạy test trên SQLite.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE appointments ENGINE=InnoDB');
        }

        // Dọn user_id trỏ tới tài khoản đã bị xóa, nếu không MySQL từ chối tạo khóa ngoại.
        // Viết bằng NOT IN (subquery) thay vì UPDATE ... LEFT JOIN vì cú pháp JOIN
        // trong UPDATE là riêng của MySQL, còn dạng này chạy được trên cả SQLite.
        DB::statement('
            UPDATE appointments
            SET user_id = NULL
            WHERE user_id IS NOT NULL
              AND user_id NOT IN (SELECT id FROM users)
        ');

        // appointments.user_id được tạo bằng bigInteger() (CÓ DẤU), trong khi
        // users.id là bigint UNSIGNED do $table->id(). MySQL từ chối tạo khóa ngoại
        // giữa cột có dấu và cột không dấu (lỗi 3780), nên migration này và MỌI
        // migration sau nó chưa bao giờ chạy được. Đổi kiểu cột trước khi tạo FK.
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

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
