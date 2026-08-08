<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\StockTransaction;

return new class extends Migration
{
    public function up(): void
    {
        $deliveredOrders = Order::whereIn('status', ['DELIVERED', 'DELIVERING'])->get();

        foreach ($deliveredOrders as $order) {
            $hasTx = StockTransaction::query()
                ->where('type', 'SALE_OUT')
                ->where(function ($q) use ($order) {
                    $q->where('related_order_id', $order->id)
                      ->orWhere('note', 'like', '%#' . $order->id . '%');
                })
                ->exists();

            if ($hasTx) {
                continue;
            }

            $items = $order->items
                ->filter(fn ($item) => (int) $item->variant_id > 0 && (int) $item->quantity > 0)
                ->values();

            if ($items->isEmpty()) {
                continue;
            }

            $code = 'SALE_OUT' . now()->format('YmdHis') . random_int(100, 999);

            $payload = [
                'transaction_code' => $code,
                'type' => 'SALE_OUT',
                'source_warehouse_id' => 1,
                'target_warehouse_id' => null,
                'status' => 'COMPLETED',
                'expected_date' => null,
                'note' => 'Xuất bán bổ sung cho đơn hàng cũ #' . $order->id,
                'created_by' => 1,
                'confirmed_by' => 1,
                'confirmed_at' => $order->created_at ?: now(),
                'created_at' => $order->created_at ?: now(),
                'updated_at' => now(),
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('stock_transactions', 'related_order_id')) {
                $payload['related_order_id'] = $order->id;
            }

            $transaction = StockTransaction::create($payload);

            foreach ($items as $item) {
                $variantId = (int) $item->variant_id;
                $quantity = (int) $item->quantity;

                $transaction->items()->create([
                    'variant_id' => $variantId,
                    'ordered_quantity' => $quantity,
                    'actual_quantity' => $quantity,
                    'unit_cost' => null,
                    'note' => trim((string) $item->product_name) ?: null,
                ]);

                // Trừ tồn kho tại Kho ID 1 (không ném exception nếu thiếu để đảm bảo backfill dữ liệu cũ)
                DB::table('inventories')
                    ->where('warehouse_id', 1)
                    ->where('variant_id', $variantId)
                    ->update([
                        'quantity' => DB::raw('GREATEST(0, quantity - ' . $quantity . ')'),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
    }
};
