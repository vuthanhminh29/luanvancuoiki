<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        $warehouse = DB::table('warehouses')
            ->where('warehouse_code', 'KHOLOI')
            ->orWhere('type', 'QUARANTINE')
            ->orderByRaw("warehouse_code = 'KHOLOI' desc")
            ->orderBy('id')
            ->first();

        if ($warehouse) {
            DB::table('warehouses')
                ->where('id', $warehouse->id)
                ->update([
                    'warehouse_code' => 'KHOLOI',
                    'name' => 'Kho hàng lỗi / chờ xử lý',
                    'type' => 'QUARANTINE',
                    'status' => 'ACTIVE',
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('warehouses')->insert([
            'warehouse_code' => 'KHOLOI',
            'name' => 'Kho hàng lỗi / chờ xử lý',
            'type' => 'QUARANTINE',
            'capacity' => 10000,
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

        DB::table('warehouses')
            ->where('warehouse_code', 'KHOLOI')
            ->where('type', 'QUARANTINE')
            ->update(['status' => 'INACTIVE', 'updated_at' => now()]);
    }
};
