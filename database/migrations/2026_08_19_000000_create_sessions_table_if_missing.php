<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cùng nguyên nhân với 2026_08_18_120000_create_payments_table_if_missing:
 * bảng migrations được nạp từ dump luanvan_ban_mat_kinh.sql đã đánh dấu
 * 0001_01_01_000000_create_users_table là "đã chạy" từ môi trường khác, nên
 * artisan migrate bỏ qua nó và không tạo bảng sessions thật trên máy này -
 * dù SESSION_DRIVER=database. Hệ quả: mọi request đọc/ghi session (tức gần
 * như toàn bộ trang web) ném QueryException 42S02 "Table ... sessions
 * doesn't exist" một cách ngẫu nhiên, tùy request đó có cần đọc session hay
 * không.
 *
 * Cấu trúc lấy đúng theo Schema::create('sessions', ...) trong
 * 0001_01_01_000000_create_users_table.php để không lệch với các môi trường
 * đã có bảng này.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
