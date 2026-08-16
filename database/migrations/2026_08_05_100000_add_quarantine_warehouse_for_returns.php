<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tạo kho chứa hàng lỗi (QUARANTINE) cho nghiệp vụ hoàn/đổi.
 *
 * Migration 2026_08_04_151000 trước đó đã gộp kho RETURN về NORMAL, nhưng nghiệp vụ
 * đổi hàng lỗi vẫn cần một chỗ chứa riêng: hàng khách trả về không được tính vào
 * tồn bán, nếu không hệ thống sẽ bán lại đúng sản phẩm bị hỏng cho khách tiếp theo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('warehouses')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Nới enum type nếu cột đang là ENUM, để nhận thêm QUARANTINE.
        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $this->widenWarehouseTypeEnum();

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $exists = DB::table('warehouses')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('type', 'QUARANTINE')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->exists();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($exists) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('warehouses')->insert([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'warehouse_code' => 'KHOLOI',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'name' => 'Kho hàng lỗi / chờ xử lý',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'type' => 'QUARANTINE',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'capacity' => 100000,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'address_detail' => 'Khu vực lưu hàng khách hoàn/đổi về, chưa bán lại được.',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'min_stock_level' => 0,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'status' => 'ACTIVE',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'created_at' => now(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('warehouses')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Chỉ xóa kho lỗi khi nó chưa giữ hàng, tránh làm mất số liệu tồn.
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $warehouseIds = DB::table('warehouses')->where('type', 'QUARANTINE')->pluck('id');

        // Luong: Lap qua tung phan tu de xu ly lan luot.
        foreach ($warehouseIds as $warehouseId) {
            // Luong: Gan ket qua xu ly vao bien $hasStock.
            $hasStock = Schema::hasTable('inventories')
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                && DB::table('inventories')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('warehouse_id', $warehouseId)
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('quantity', '>', 0)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->exists();

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! $hasStock) {
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('warehouses')->where('id', $warehouseId)->delete();
            }
        }
    }

    private function widenWarehouseTypeEnum(): void
    {
        // Dùng information_schema thay cho "SHOW COLUMNS ... LIKE ?":
        // MySQL không nhận tham số bind trong câu SHOW nên sẽ ném lỗi và
        // khối catch bên dưới sẽ nuốt mất, khiến ALTER không bao giờ chạy.
        try {
            $column = DB::selectOne(
                'SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['warehouses', 'type']
            );
        } catch (\Throwable) {
            return;                       // Không phải MySQL thì bỏ qua.
        }

        if (! $column) {
            return;
        }

        $columnType = (string) $column->column_type;

        if (! str_starts_with(strtolower($columnType), 'enum')) {
            return;                       // Cột đang là VARCHAR, không cần nới.
        }

        if (str_contains($columnType, 'QUARANTINE')) {
            return;                       // Đã nới rồi.
        }

        $nullable = strtoupper((string) $column->is_nullable) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column->column_default !== null
            ? " DEFAULT '" . addslashes((string) $column->column_default) . "'"
            : '';

        // Giữ nguyên RETURN trong enum: dữ liệu cũ có thể còn dòng mang giá trị này,
        // bỏ đi sẽ làm MySQL đổi chúng thành chuỗi rỗng.
        DB::statement(
            "ALTER TABLE `warehouses` MODIFY `type` ENUM('NORMAL','RETURN','WARRANTY','STORE','QUARANTINE') {$nullable}{$default}"
        );
    }
};
