<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasRequiredTablesAndColumns()) {
            return;
        }

        $quarantineId = $this->quarantineWarehouseId();

        DB::transaction(function () use ($quarantineId) {
            $transactions = DB::table('stock_transactions')
                ->where('type', 'RETURN_IN')
                ->where('status', 'COMPLETED')
                ->where(function (Builder $query) use ($quarantineId) {
                    $query->whereNull('target_warehouse_id')
                        ->orWhere('target_warehouse_id', '<>', $quarantineId);
                })
                ->where('note', 'like', '%RTN%')
                ->get(['id', 'target_warehouse_id', 'note']);

            foreach ($transactions as $transaction) {
                $returnCode = $this->returnCodeFromNote((string) $transaction->note);

                if ($returnCode === null || ! $this->shouldMoveReturnToQuarantine($returnCode)) {
                    continue;
                }

                $items = DB::table('stock_transaction_items')
                    ->where('stock_transaction_id', $transaction->id)
                    ->get(['variant_id', 'ordered_quantity', 'actual_quantity']);

                foreach ($items as $item) {
                    $variantId = (int) $item->variant_id;
                    $quantity = (int) ($item->actual_quantity ?: $item->ordered_quantity);

                    if ($variantId < 1 || $quantity < 1) {
                        continue;
                    }

                    if ($transaction->target_warehouse_id) {
                        DB::table('inventories')
                            ->where('warehouse_id', (int) $transaction->target_warehouse_id)
                            ->where('variant_id', $variantId)
                            ->update($this->inventorySubtractValues($quantity));
                    }

                    $this->receiveInventory($quarantineId, $variantId, $quantity);
                }

                DB::table('stock_transactions')
                    ->where('id', $transaction->id)
                    ->update(['target_warehouse_id' => $quarantineId]);
            }
        });
    }

    public function down(): void
    {
        // Data correction only. Do not move stock back automatically.
    }

    private function hasRequiredTablesAndColumns(): bool
    {
        foreach ([
            'warehouses',
            'inventories',
            'stock_transactions',
            'stock_transaction_items',
            'return_requests',
            'return_reasons',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        foreach (['type', 'status', 'target_warehouse_id', 'note'] as $column) {
            if (! Schema::hasColumn('stock_transactions', $column)) {
                return false;
            }
        }

        foreach (['warehouse_id', 'variant_id', 'quantity'] as $column) {
            if (! Schema::hasColumn('inventories', $column)) {
                return false;
            }
        }

        foreach (['stock_transaction_id', 'variant_id', 'ordered_quantity', 'actual_quantity'] as $column) {
            if (! Schema::hasColumn('stock_transaction_items', $column)) {
                return false;
            }
        }

        return Schema::hasColumn('return_requests', 'return_code');
    }

    private function quarantineWarehouseId(): int
    {
        $warehouse = $this->quarantineWarehouseQuery()->first();

        if ($warehouse) {
            $values = $this->warehouseValues();

            if ($values !== []) {
                DB::table('warehouses')
                    ->where('id', $warehouse->id)
                    ->update($values);
            }

            return (int) $warehouse->id;
        }

        return (int) DB::table('warehouses')->insertGetId($this->warehouseValues(true));
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

    private function returnCodeFromNote(string $note): ?string
    {
        preg_match('/RTN[0-9A-Z]+/i', $note, $matches);

        return isset($matches[0]) ? strtoupper($matches[0]) : null;
    }

    private function shouldMoveReturnToQuarantine(string $returnCode): bool
    {
        $typeColumn = Schema::hasColumn('return_requests', 'type')
            ? 'type'
            : (Schema::hasColumn('return_requests', 'return_type') ? 'return_type' : null);

        $selects = [];
        $selects[] = $typeColumn
            ? "rr.{$typeColumn} as request_type"
            : DB::raw("'RETURN' as request_type");

        $canJoinReason = Schema::hasColumn('return_requests', 'reason_id')
            && Schema::hasColumn('return_reasons', 'id');

        if ($canJoinReason && Schema::hasColumn('return_reasons', 'code')) {
            $selects[] = 'reason.code as reason_code';
        } else {
            $selects[] = DB::raw('NULL as reason_code');
        }

        if ($canJoinReason && Schema::hasColumn('return_reasons', 'name')) {
            $selects[] = 'reason.name as reason_name';
        } else {
            $selects[] = DB::raw('NULL as reason_name');
        }

        $query = DB::table('return_requests as rr')
            ->where('rr.return_code', $returnCode)
            ->select($selects);

        if ($canJoinReason) {
            $query->leftJoin('return_reasons as reason', 'reason.id', '=', 'rr.reason_id');
        }

        $return = $query->first();

        if (! $return) {
            return false;
        }

        if ((string) $return->request_type !== 'RETURN') {
            return true;
        }

        $code = strtoupper((string) $return->reason_code);
        $name = Str::ascii(Str::lower((string) $return->reason_name));

        return ! (
            in_array($code, ['NOT_WANTED', 'CHANGE_MIND', 'NO_LONGER_NEEDED'], true)
            || str_contains($name, 'khong mua nua')
            || str_contains($name, 'doi y')
            || str_contains($name, 'khong con nhu cau')
        );
    }

    private function inventorySubtractValues(int $quantity): array
    {
        $values = [
            'quantity' => DB::raw('GREATEST(quantity - ' . $quantity . ', 0)'),
        ];

        if (Schema::hasColumn('inventories', 'updated_at')) {
            $values['updated_at'] = now();
        }

        return $values;
    }

    private function receiveInventory(int $warehouseId, int $variantId, int $quantity): void
    {
        $affected = DB::table('inventories')
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->update($this->inventoryReceiveValues($quantity));

        if ($affected > 0) {
            return;
        }

        $values = [
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
        ];

        if (Schema::hasColumn('inventories', 'min_stock_level')) {
            $values['min_stock_level'] = Schema::hasColumn('warehouses', 'min_stock_level')
                ? (DB::table('warehouses')->where('id', $warehouseId)->value('min_stock_level') ?? 0)
                : 0;
        }

        if (Schema::hasColumn('inventories', 'created_at')) {
            $values['created_at'] = now();
        }

        if (Schema::hasColumn('inventories', 'updated_at')) {
            $values['updated_at'] = now();
        }

        DB::table('inventories')->insert($values);
    }

    private function inventoryReceiveValues(int $quantity): array
    {
        $values = [
            'quantity' => DB::raw('quantity + ' . $quantity),
        ];

        if (Schema::hasColumn('inventories', 'updated_at')) {
            $values['updated_at'] = now();
        }

        return $values;
    }
};
