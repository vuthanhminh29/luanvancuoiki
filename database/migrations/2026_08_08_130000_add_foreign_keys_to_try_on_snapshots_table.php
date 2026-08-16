<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng try_on_snapshots có 3 cột tham chiếu nhưng không ràng buộc khóa ngoại,
     * nên xóa sản phẩm hoặc tài khoản sẽ để lại bản ghi mồ côi.
     * Cả 3 cột đều nullable: khách vãng lai thử kính không có user_id, và ảnh thử
     * vẫn phải giữ được khi sản phẩm bị gỡ khỏi catalog -> dùng nullOnDelete.
     */
    public function up(): void
    {
        // Dọn tham chiếu trỏ tới bản ghi đã bị xóa trước khi tạo ràng buộc,
        // nếu không MySQL từ chối ALTER TABLE.
        // Luong: Lap qua tung phan tu de xu ly lan luot.
        foreach ([
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['user_id', 'users'],
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['product_id', 'products'],
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['variant_id', 'product_variants'],
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        ] as [$column, $referenced]) {
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            DB::statement("
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                UPDATE try_on_snapshots t
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                LEFT JOIN {$referenced} r ON r.id = t.{$column}
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                SET t.{$column} = NULL
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                WHERE t.{$column} IS NOT NULL AND r.id IS NULL
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ");
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('try_on_snapshots', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('try_on_snapshots', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropForeign(['user_id']);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropForeign(['product_id']);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropForeign(['variant_id']);
        });
    }
};
