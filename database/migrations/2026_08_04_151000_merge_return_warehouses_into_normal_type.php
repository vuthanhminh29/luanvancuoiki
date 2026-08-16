<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('warehouses') || ! Schema::hasColumn('warehouses', 'type')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        DB::transaction(function (): void {
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            $returnWarehouseIds = DB::table('warehouses')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('type', 'RETURN')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->pluck('id')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->map(fn ($id) => (int) $id)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->all();

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($returnWarehouseIds !== []) {
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                $targetWarehouseId = DB::table('warehouses')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('type', 'NORMAL')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->whereNotIn('id', $returnWarehouseIds)
                    // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                    ->orderByRaw("status = 'ACTIVE' desc")
                    // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                    ->orderBy('id')
                    // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                    ->value('id');

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (! $targetWarehouseId) {
                    // Luong: Gan ket qua xu ly vao bien $targetWarehouseId.
                    $targetWarehouseId = $returnWarehouseIds[0];

                    // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                    DB::table('warehouses')
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('id', $targetWarehouseId)
                        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                        ->update(['type' => 'NORMAL']);
                }

                // Luong: Gan ket qua xu ly vao bien $targetWarehouseId.
                $targetWarehouseId = (int) $targetWarehouseId;
                // Luong: Gan ket qua xu ly vao bien $sourceWarehouseIds.
                $sourceWarehouseIds = array_values(array_diff($returnWarehouseIds, [$targetWarehouseId]));

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (Schema::hasTable('inventories') && Schema::hasColumn('inventories', 'warehouse_id')) {
                    // Luong: Lap qua tung phan tu de xu ly lan luot.
                    foreach ($sourceWarehouseIds as $sourceWarehouseId) {
                        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                        $inventories = DB::table('inventories')
                            // Luong: Bo sung dieu kien loc du lieu cho truy van.
                            ->where('warehouse_id', $sourceWarehouseId)
                            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                            ->get();

                        // Luong: Lap qua tung phan tu de xu ly lan luot.
                        foreach ($inventories as $inventory) {
                            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                            $targetInventory = DB::table('inventories')
                                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                ->where('warehouse_id', $targetWarehouseId)
                                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                ->where('variant_id', $inventory->variant_id)
                                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                                ->first();

                            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                            if ($targetInventory) {
                                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                                DB::table('inventories')
                                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                    ->where('id', $targetInventory->id)
                                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                                    ->update([
                                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                                        'quantity' => (int) $targetInventory->quantity + (int) $inventory->quantity,
                                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                                        'reserved_quantity' => (int) $targetInventory->reserved_quantity + (int) $inventory->reserved_quantity,
                                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                                        'updated_at' => now(),
                                    ]);

                                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                                DB::table('inventories')->where('id', $inventory->id)->delete();
                            // Luong: Xu ly truong hop con lai cua nhanh dieu kien.
                            } else {
                                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                                DB::table('inventories')
                                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                    ->where('id', $inventory->id)
                                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                                    ->update([
                                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                                        'warehouse_id' => $targetWarehouseId,
                                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                                        'updated_at' => now(),
                                    ]);
                            }
                        }
                    }
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (Schema::hasTable('stock_transactions')) {
                    // Luong: Lap qua tung phan tu de xu ly lan luot.
                    foreach (['source_warehouse_id', 'target_warehouse_id'] as $column) {
                        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                        if (Schema::hasColumn('stock_transactions', $column)) {
                            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                            DB::table('stock_transactions')
                                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                                ->whereIn($column, $sourceWarehouseIds)
                                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                                ->update([$column => $targetWarehouseId]);
                        }
                    }
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'warehouse_id')) {
                    // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                    DB::table('stores')
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->whereIn('warehouse_id', $sourceWarehouseIds)
                        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                        ->update(['warehouse_id' => $targetWarehouseId]);
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($sourceWarehouseIds !== []) {
                    // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                    DB::table('warehouses')->whereIn('id', $sourceWarehouseIds)->delete();
                }

                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('warehouses')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->whereIn('id', $returnWarehouseIds)
                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                    ->update(['type' => 'NORMAL']);
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (Schema::hasTable('stock_transactions') && Schema::hasColumn('stock_transactions', 'type')) {
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('stock_transactions')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->whereIn('type', ['RETURN_IN', 'ADJUST'])
                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                    ->update(['type' => 'IMPORT']);
            }
        });
    }

    public function down(): void
    {
        // Không tự đổi NORMAL về RETURN vì sau khi gộp kho không còn phân biệt được kho nào từng là kho hoàn.
    }
};
