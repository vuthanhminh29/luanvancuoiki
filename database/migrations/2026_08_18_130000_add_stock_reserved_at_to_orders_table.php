<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đánh dấu thời điểm đơn hàng đã trừ tồn kho.
 *
 * Trước đây tồn kho chỉ bị trừ khi admin chuyển đơn sang DELIVERING, nên trong
 * suốt quãng từ lúc khách đặt tới lúc giao, hàng vẫn được tính là còn bán: hai
 * khách mua được cùng một cái kính cuối cùng, và khách VNPay còn trả tiền xong
 * mới phát hiện hết hàng.
 *
 * Giờ tồn bị trừ ngay khi tạo đơn và được hoàn lại khi hủy. Cột này là chốt chặn
 * để không trừ hai lần / hoàn hai lần, và quan trọng hơn: các đơn CŨ tạo trước
 * thay đổi này có giá trị NULL nên khi hủy sẽ KHÔNG được cộng trả tồn kho
 * (chúng vốn chưa từng bị trừ) — nếu thiếu cột này, tồn kho sẽ bị thổi phồng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'stock_reserved_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_reserved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'stock_reserved_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_reserved_at');
        });
    }
};
