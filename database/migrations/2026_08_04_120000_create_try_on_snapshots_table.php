<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng này lưu mỗi lần khách bấm chụp/lưu kết quả sau khi thử kính.
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('try_on_snapshots', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // DB hiện tại đang dùng bigint signed, nên dùng bigInteger + index để tương thích.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->bigInteger('user_id')->nullable()->index();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->bigInteger('product_id')->nullable()->index();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->bigInteger('variant_id')->nullable()->index();
            // Lưu lại tên/email tại thời điểm chụp để sau này user đổi thông tin vẫn xem được lịch sử cũ.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('user_name', 100);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('user_email');
            // product_name và model_sku là thông tin kính đã thử, model_sku chính là mã model Jeeliz.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('product_name');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('model_sku', 100);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->decimal('price', 12, 2)->default(0);
            // Ảnh thật lưu trong storage/app/public, database chỉ lưu đường dẫn ảnh.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('image_path');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('tryon_mode', 20)->default('camera');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamps();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->index(['user_email', 'created_at']);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->index(['model_sku', 'created_at']);
        });
    }

    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('try_on_snapshots');
    }
};
