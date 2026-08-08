<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Vô hiệu hóa các kho khác và đổi tên để giải phóng constraint name
        DB::table('warehouses')
            ->where('id', '!=', 1)
            ->update([
                'name' => DB::raw("CONCAT(name, ' (Đã gộp)')"),
                'status' => 'INACTIVE',
                'updated_at' => now(),
            ]);

        // 2. Chuẩn hóa Kho ID 1 làm Kho chính duy nhất
        DB::table('warehouses')
            ->where('id', 1)
            ->update([
                'warehouse_code' => 'KHO_TONG',
                'name' => 'Kho hàng trung tâm',
                'type' => 'NORMAL',
                'status' => 'ACTIVE',
                'updated_at' => now(),
            ]);

        // 2. Gom tất cả dòng tồn kho từ các kho khác về Kho ID 1
        $otherInventories = DB::table('inventories')
            ->where('warehouse_id', '!=', 1)
            ->get();

        foreach ($otherInventories as $inv) {
            $existing = DB::table('inventories')
                ->where('warehouse_id', 1)
                ->where('variant_id', $inv->variant_id)
                ->first();

            if ($existing) {
                DB::table('inventories')
                    ->where('id', $existing->id)
                    ->update([
                        'quantity' => $existing->quantity + $inv->quantity,
                        'updated_at' => now(),
                    ]);
                DB::table('inventories')->where('id', $inv->id)->delete();
            } else {
                DB::table('inventories')
                    ->where('id', $inv->id)
                    ->update([
                        'warehouse_id' => 1,
                        'updated_at' => now(),
                    ]);
            }
        }

        // 3. Chuyển tất cả giao dịch kho về Kho ID 1
        DB::table('stock_transactions')
            ->whereNotNull('source_warehouse_id')
            ->update(['source_warehouse_id' => 1]);

        DB::table('stock_transactions')
            ->whereNotNull('target_warehouse_id')
            ->update(['target_warehouse_id' => 1]);

        // 4. Vô hiệu hóa các kho khác
        DB::table('warehouses')
            ->where('id', '!=', 1)
            ->update([
                'status' => 'INACTIVE',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Không khôi phục vì gộp kho là chuyển đổi cấu trúc dữ liệu đơn giản hóa
    }
};
