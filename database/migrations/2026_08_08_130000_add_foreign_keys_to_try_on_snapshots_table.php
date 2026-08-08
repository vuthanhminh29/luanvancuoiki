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
        foreach ([
            ['user_id', 'users'],
            ['product_id', 'products'],
            ['variant_id', 'product_variants'],
        ] as [$column, $referenced]) {
            DB::statement("
                UPDATE try_on_snapshots t
                LEFT JOIN {$referenced} r ON r.id = t.{$column}
                SET t.{$column} = NULL
                WHERE t.{$column} IS NOT NULL AND r.id IS NULL
            ");
        }

        Schema::table('try_on_snapshots', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('try_on_snapshots', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variant_id']);
        });
    }
};
