<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouses') || ! Schema::hasColumn('warehouses', 'type')) {
            return;
        }

        DB::transaction(function (): void {
            $returnWarehouseIds = DB::table('warehouses')
                ->where('type', 'RETURN')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($returnWarehouseIds !== []) {
                $targetWarehouseId = DB::table('warehouses')
                    ->where('type', 'NORMAL')
                    ->whereNotIn('id', $returnWarehouseIds)
                    ->orderByRaw("status = 'ACTIVE' desc")
                    ->orderBy('id')
                    ->value('id');

                if (! $targetWarehouseId) {
                    $targetWarehouseId = $returnWarehouseIds[0];

                    DB::table('warehouses')
                        ->where('id', $targetWarehouseId)
                        ->update(['type' => 'NORMAL']);
                }

                $targetWarehouseId = (int) $targetWarehouseId;
                $sourceWarehouseIds = array_values(array_diff($returnWarehouseIds, [$targetWarehouseId]));

                if (Schema::hasTable('inventories') && Schema::hasColumn('inventories', 'warehouse_id')) {
                    foreach ($sourceWarehouseIds as $sourceWarehouseId) {
                        $inventories = DB::table('inventories')
                            ->where('warehouse_id', $sourceWarehouseId)
                            ->get();

                        foreach ($inventories as $inventory) {
                            $targetInventory = DB::table('inventories')
                                ->where('warehouse_id', $targetWarehouseId)
                                ->where('variant_id', $inventory->variant_id)
                                ->first();

                            if ($targetInventory) {
                                DB::table('inventories')
                                    ->where('id', $targetInventory->id)
                                    ->update([
                                        'quantity' => (int) $targetInventory->quantity + (int) $inventory->quantity,
                                        'reserved_quantity' => (int) $targetInventory->reserved_quantity + (int) $inventory->reserved_quantity,
                                        'updated_at' => now(),
                                    ]);

                                DB::table('inventories')->where('id', $inventory->id)->delete();
                            } else {
                                DB::table('inventories')
                                    ->where('id', $inventory->id)
                                    ->update([
                                        'warehouse_id' => $targetWarehouseId,
                                        'updated_at' => now(),
                                    ]);
                            }
                        }
                    }
                }

                if (Schema::hasTable('stock_transactions')) {
                    foreach (['source_warehouse_id', 'target_warehouse_id'] as $column) {
                        if (Schema::hasColumn('stock_transactions', $column)) {
                            DB::table('stock_transactions')
                                ->whereIn($column, $sourceWarehouseIds)
                                ->update([$column => $targetWarehouseId]);
                        }
                    }
                }

                if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'warehouse_id')) {
                    DB::table('stores')
                        ->whereIn('warehouse_id', $sourceWarehouseIds)
                        ->update(['warehouse_id' => $targetWarehouseId]);
                }

                if ($sourceWarehouseIds !== []) {
                    DB::table('warehouses')->whereIn('id', $sourceWarehouseIds)->delete();
                }

                DB::table('warehouses')
                    ->whereIn('id', $returnWarehouseIds)
                    ->update(['type' => 'NORMAL']);
            }

            if (Schema::hasTable('stock_transactions') && Schema::hasColumn('stock_transactions', 'type')) {
                DB::table('stock_transactions')
                    ->whereIn('type', ['RETURN_IN', 'ADJUST'])
                    ->update(['type' => 'IMPORT']);
            }
        });
    }

    public function down(): void
    {
        // Không tự đổi NORMAL về RETURN vì sau khi gộp kho không còn phân biệt được kho nào từng là kho hoàn.
    }
};
