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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable()->index();

            // Mã tra cứu ngắn hiển thị cho khách, ví dụ AO-20260806-4F2K.
            $table->string('code', 20)->unique();

            $table->string('service_code', 20);
            $table->string('service_name', 100);
            $table->decimal('price', 12, 2)->default(0);

            $table->date('appointment_date');
            $table->string('appointment_time', 10);

            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->text('note')->nullable();

            $table->string('status', 20)->default('PENDING');

            $table->timestamps();

            $table->index(['appointment_date', 'appointment_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
