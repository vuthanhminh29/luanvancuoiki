<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\StockTransaction;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $deliveredOrders = Order::whereIn('status', ['DELIVERED', 'DELIVERING'])->get();

        // Luong: Lap qua tung phan tu de xu ly lan luot.
        foreach ($deliveredOrders as $order) {
            // Luong: Gan ket qua xu ly vao bien $hasTx.
            $hasTx = StockTransaction::query()
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('type', 'SALE_OUT')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where(function ($q) use ($order) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    $q->where('related_order_id', $order->id)
                      // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                      ->orWhere('note', 'like', '%#' . $order->id . '%');
                })
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->exists();

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($hasTx) {
                // Luong: Bo qua vong lap hien tai va chuyen sang phan tu tiep theo.
                continue;
            }

            // Luong: Gan ket qua xu ly vao bien $items.
            $items = $order->items
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->filter(fn ($item) => (int) $item->variant_id > 0 && (int) $item->quantity > 0)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->values();

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($items->isEmpty()) {
                // Luong: Bo qua vong lap hien tai va chuyen sang phan tu tiep theo.
                continue;
            }

            // Luong: Gan ket qua xu ly vao bien $code.
            $code = 'SALE_OUT' . now()->format('YmdHis') . random_int(100, 999);

            // Luong: Gan ket qua xu ly vao bien $payload.
            $payload = [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'transaction_code' => $code,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'type' => 'SALE_OUT',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'source_warehouse_id' => 1,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'target_warehouse_id' => null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'COMPLETED',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'expected_date' => null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'note' => 'Xuất bán bổ sung cho đơn hàng cũ #' . $order->id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_by' => 1,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'confirmed_by' => 1,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'confirmed_at' => $order->created_at ?: now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $order->created_at ?: now(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ];

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (\Illuminate\Support\Facades\Schema::hasColumn('stock_transactions', 'related_order_id')) {
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                $payload['related_order_id'] = $order->id;
            }

            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            $transaction = StockTransaction::create($payload);

            // Luong: Lap qua tung phan tu de xu ly lan luot.
            foreach ($items as $item) {
                // Luong: Gan ket qua xu ly vao bien $variantId.
                $variantId = (int) $item->variant_id;
                // Luong: Gan ket qua xu ly vao bien $quantity.
                $quantity = (int) $item->quantity;

                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $transaction->items()->create([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'variant_id' => $variantId,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'ordered_quantity' => $quantity,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'actual_quantity' => $quantity,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'unit_cost' => null,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'note' => trim((string) $item->product_name) ?: null,
                ]);

                // Trừ tồn kho tại Kho ID 1 (không ném exception nếu thiếu để đảm bảo backfill dữ liệu cũ)
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('inventories')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('warehouse_id', 1)
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('variant_id', $variantId)
                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                    ->update([
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'quantity' => DB::raw('GREATEST(0, quantity - ' . $quantity . ')'),
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
    }
};
