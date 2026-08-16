<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('warehouses')
            || ! Schema::hasTable('inventories')
            || ! Schema::hasTable('stock_transactions')
            || ! Schema::hasTable('stock_transaction_items')
            || ! Schema::hasTable('return_requests')
            || ! Schema::hasTable('return_reasons')
        ) {
            return;
        }

        $quarantineId = $this->quarantineWarehouseId();

        DB::transaction(function () use ($quarantineId) {
            $transactions = DB::table('stock_transactions')
                ->where('type', 'RETURN_IN')
                ->where('status', 'COMPLETED')
                ->where(function ($query) use ($quarantineId) {
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
                            ->update([
                                'quantity' => DB::raw('GREATEST(quantity - ' . $quantity . ', 0)'),
                                'updated_at' => now(),
                            ]);
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

    private function quarantineWarehouseId(): int
    {
        $warehouse = DB::table('warehouses')
            ->where(function ($query) {
                $query->where('type', 'QUARANTINE')
                    ->orWhere('warehouse_code', 'KHOLOI');
            })
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

            return (int) $warehouse->id;
        }

        return (int) DB::table('warehouses')->insertGetId([
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

    private function returnCodeFromNote(string $note): ?string
    {
        preg_match('/RTN[0-9A-Z]+/i', $note, $matches);

        return isset($matches[0]) ? strtoupper($matches[0]) : null;
    }

    private function shouldMoveReturnToQuarantine(string $returnCode): bool
    {
        $return = DB::table('return_requests as rr')
            ->leftJoin('return_reasons as reason', 'reason.id', '=', 'rr.reason_id')
            ->where('rr.return_code', $returnCode)
            ->select([
                'rr.type',
                'reason.code as reason_code',
                'reason.name as reason_name',
            ])
            ->first();

        if (! $return) {
            return false;
        }

        if ((string) $return->type !== 'RETURN') {
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

    private function receiveInventory(int $warehouseId, int $variantId, int $quantity): void
    {
        $affected = DB::table('inventories')
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->update([
                'quantity' => DB::raw('quantity + ' . $quantity),
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            return;
        }

        DB::table('inventories')->insert([
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'min_stock_level' => DB::table('warehouses')->where('id', $warehouseId)->value('min_stock_level') ?? 0,
            'updated_at' => now(),
        ]);
    }
};
