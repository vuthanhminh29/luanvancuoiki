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
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        // Nới enum type nếu cột đang là ENUM, để nhận thêm QUARANTINE.
        $this->widenWarehouseTypeEnum();

        $exists = DB::table('warehouses')
            ->where('type', 'QUARANTINE')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('warehouses')->insert([
            'warehouse_code' => 'KHOLOI',
            'name' => 'Kho hàng lỗi / chờ xử lý',
            'type' => 'QUARANTINE',
            'capacity' => 100000,
            'address_detail' => 'Khu vực lưu hàng khách hoàn/đổi về, chưa bán lại được.',
            'min_stock_level' => 0,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        // Chỉ xóa kho lỗi khi nó chưa giữ hàng, tránh làm mất số liệu tồn.
        $warehouseIds = DB::table('warehouses')->where('type', 'QUARANTINE')->pluck('id');

        foreach ($warehouseIds as $warehouseId) {
            $hasStock = Schema::hasTable('inventories')
                && DB::table('inventories')
                    ->where('warehouse_id', $warehouseId)
                    ->where('quantity', '>', 0)
                    ->exists();

            if (! $hasStock) {
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
