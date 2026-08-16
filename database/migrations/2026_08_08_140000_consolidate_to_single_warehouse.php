<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Vô hiệu hóa các kho khác và đổi tên để giải phóng constraint name
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('warehouses')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('id', '!=', 1)
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => DB::raw("CONCAT(name, ' (Đã gộp)')"),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'INACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ]);

        // 2. Chuẩn hóa Kho ID 1 làm Kho chính duy nhất
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('warehouses')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('id', 1)
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'warehouse_code' => 'KHO_TONG',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Kho hàng trung tâm',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'type' => 'NORMAL',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ]);

        // 2. Gom tất cả dòng tồn kho từ các kho khác về Kho ID 1
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $otherInventories = DB::table('inventories')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('warehouse_id', '!=', 1)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Lap qua tung phan tu de xu ly lan luot.
        foreach ($otherInventories as $inv) {
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            $existing = DB::table('inventories')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('warehouse_id', 1)
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('variant_id', $inv->variant_id)
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->first();

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($existing) {
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('inventories')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('id', $existing->id)
                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                    ->update([
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'quantity' => $existing->quantity + $inv->quantity,
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'updated_at' => now(),
                    ]);
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('inventories')->where('id', $inv->id)->delete();
            // Luong: Xu ly truong hop con lai cua nhanh dieu kien.
            } else {
                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('inventories')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('id', $inv->id)
                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                    ->update([
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'warehouse_id' => 1,
                        // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                        'updated_at' => now(),
                    ]);
            }
        }

        // 3. Chuyển tất cả giao dịch kho về Kho ID 1
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('stock_transactions')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereNotNull('source_warehouse_id')
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update(['source_warehouse_id' => 1]);

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('stock_transactions')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereNotNull('target_warehouse_id')
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update(['target_warehouse_id' => 1]);

        // 4. Vô hiệu hóa các kho khác
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('warehouses')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('id', '!=', 1)
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'INACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Không khôi phục vì gộp kho là chuyển đổi cấu trúc dữ liệu đơn giản hóa
    }
};
