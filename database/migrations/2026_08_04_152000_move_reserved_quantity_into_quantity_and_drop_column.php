<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('inventories') || ! Schema::hasColumn('inventories', 'reserved_quantity')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->dropReservedQuantityChecks();

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('inventories')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('reserved_quantity', '>', 0)
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'quantity' => DB::raw('quantity + reserved_quantity'),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ]);

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('inventories', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropColumn('reserved_quantity');
        });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement('ALTER TABLE `inventories` ADD CONSTRAINT `chk_inventories_quantity` CHECK (`quantity` >= 0)');
    }

    public function down(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('inventories') || Schema::hasColumn('inventories', 'reserved_quantity')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('inventories', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('reserved_quantity')->default(0)->after('quantity');
        });
    }

    private function dropReservedQuantityChecks(): void
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.CHECK_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CHECK_CLAUSE LIKE '%reserved_quantity%'
        ");

        foreach ($constraints as $constraint) {
            $name = str_replace('`', '``', (string) $constraint->CONSTRAINT_NAME);

            DB::statement("ALTER TABLE `inventories` DROP CHECK `{$name}`");
        }
    }
};
