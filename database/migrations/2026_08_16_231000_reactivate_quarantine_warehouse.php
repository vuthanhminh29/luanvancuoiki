<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        $warehouse = $this->quarantineWarehouseQuery()->first();

        if ($warehouse) {
            $values = $this->warehouseValues();

            if ($values !== []) {
                DB::table('warehouses')
                    ->where('id', $warehouse->id)
                    ->update($values);
            }

            return;
        }

        DB::table('warehouses')->insert($this->warehouseValues(true));
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouses')) {
            return;
        }

        $values = [];

        if (Schema::hasColumn('warehouses', 'status')) {
            $values['status'] = 'INACTIVE';
        }

        if (Schema::hasColumn('warehouses', 'updated_at')) {
            $values['updated_at'] = now();
        }

        if ($values !== []) {
            $this->quarantineWarehouseQuery()->update($values);
        }
    }

    private function quarantineWarehouseQuery(): Builder
    {
        $query = DB::table('warehouses')->where(function (Builder $inner) {
            $hasCondition = false;

            if (Schema::hasColumn('warehouses', 'warehouse_code')) {
                $inner->where('warehouse_code', 'KHOLOI');
                $hasCondition = true;
            }

            if (Schema::hasColumn('warehouses', 'code')) {
                $hasCondition
                    ? $inner->orWhere('code', 'KHOLOI')
                    : $inner->where('code', 'KHOLOI');
                $hasCondition = true;
            }

            if (Schema::hasColumn('warehouses', 'type')) {
                $hasCondition
                    ? $inner->orWhere('type', 'QUARANTINE')
                    : $inner->where('type', 'QUARANTINE');
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $inner->whereRaw('1 = 0');
            }
        });

        if (Schema::hasColumn('warehouses', 'warehouse_code')) {
            $query->orderByRaw("warehouse_code = 'KHOLOI' desc");
        }

        return $query->orderBy('id');
    }

    private function warehouseValues(bool $forInsert = false): array
    {
        $values = [];

        if (Schema::hasColumn('warehouses', 'warehouse_code')) {
            $values['warehouse_code'] = 'KHOLOI';
        }

        if (Schema::hasColumn('warehouses', 'code')) {
            $values['code'] = 'KHOLOI';
        }

        if (Schema::hasColumn('warehouses', 'name')) {
            $values['name'] = 'Kho hàng lỗi / chờ xử lý';
        }

        if (Schema::hasColumn('warehouses', 'type')) {
            $values['type'] = 'QUARANTINE';
        }

        if (Schema::hasColumn('warehouses', 'capacity')) {
            $values['capacity'] = 10000;
        }

        if (Schema::hasColumn('warehouses', 'address_detail')) {
            $values['address_detail'] = 'Khu vực lưu hàng khách hoàn/đổi về, chưa bán lại được.';
        }

        if (Schema::hasColumn('warehouses', 'min_stock_level')) {
            $values['min_stock_level'] = 0;
        }

        if (Schema::hasColumn('warehouses', 'status')) {
            $values['status'] = 'ACTIVE';
        }

        if ($forInsert && Schema::hasColumn('warehouses', 'created_at')) {
            $values['created_at'] = now();
        }

        if (Schema::hasColumn('warehouses', 'updated_at')) {
            $values['updated_at'] = now();
        }

        return $values;
    }
};
