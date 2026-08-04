<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng này lưu mỗi lần khách bấm chụp/lưu kết quả sau khi thử kính.
        Schema::create('try_on_snapshots', function (Blueprint $table) {
            $table->id();
            // DB hiện tại đang dùng bigint signed, nên dùng bigInteger + index để tương thích.
            $table->bigInteger('user_id')->nullable()->index();
            $table->bigInteger('product_id')->nullable()->index();
            $table->bigInteger('variant_id')->nullable()->index();
            // Lưu lại tên/email tại thời điểm chụp để sau này user đổi thông tin vẫn xem được lịch sử cũ.
            $table->string('user_name', 100);
            $table->string('user_email');
            // product_name và model_sku là thông tin kính đã thử, model_sku chính là mã model Jeeliz.
            $table->string('product_name');
            $table->string('model_sku', 100);
            $table->decimal('price', 12, 2)->default(0);
            // Ảnh thật lưu trong storage/app/public, database chỉ lưu đường dẫn ảnh.
            $table->string('image_path');
            $table->string('tryon_mode', 20)->default('camera');
            $table->timestamps();

            $table->index(['user_email', 'created_at']);
            $table->index(['model_sku', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('try_on_snapshots');
    }
};
