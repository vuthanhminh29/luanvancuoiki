<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng lưu lịch đặt đo thị lực tại cửa hàng. Không bắt buộc đăng nhập vì
        // khách vãng lai cũng cần đặt lịch được, nên user_id để nullable.
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('appointments', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->bigInteger('user_id')->nullable()->index();

            // Mã tra cứu ngắn hiển thị cho khách, ví dụ AO-20260806-4F2K.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('code', 20)->unique();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('service_code', 20);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('service_name', 100);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->decimal('price', 12, 2)->default(0);

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->date('appointment_date');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('appointment_time', 10);

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('customer_name', 100);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('customer_phone', 20);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('customer_email')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->text('note')->nullable();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('status', 20)->default('PENDING');

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamps();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->index(['appointment_date', 'appointment_time']);
        });
    }

    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('appointments');
    }
};
