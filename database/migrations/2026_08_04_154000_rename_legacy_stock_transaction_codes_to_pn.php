<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('stock_transactions')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Gan ket qua xu ly vao bien $hasUpdatedAt.
        $hasUpdatedAt = Schema::hasColumn('stock_transactions', 'updated_at');

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('stock_transactions')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where(function ($query) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where('transaction_code', 'like', 'RETURN_IN%')
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('transaction_code', 'like', 'ADJUST%')
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('note', 'like', '%Nhập hàng hoàn%')
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('note', 'like', '%Hoàn tồn%');
            })
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->each(function ($transaction) use ($hasUpdatedAt) {
                // Luong: Gan ket qua xu ly vao bien $newCode.
                $newCode = $transaction->transaction_code;

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (is_string($newCode) && str_starts_with($newCode, 'RETURN_IN')) {
                    // Luong: Gan ket qua xu ly vao bien $newCode.
                    $newCode = 'PN' . substr($newCode, strlen('RETURN_IN'));
                // Luong: Chuyen sang dieu kien thay the sau khi nhanh truoc khong dat.
                } elseif (is_string($newCode) && str_starts_with($newCode, 'ADJUST')) {
                    // Luong: Gan ket qua xu ly vao bien $newCode.
                    $newCode = 'PN' . substr($newCode, strlen('ADJUST'));
                }

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if (
                    // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                    DB::table('stock_transactions')
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('transaction_code', $newCode)
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('id', '<>', $transaction->id)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->exists()
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ) {
                    // Luong: Gan ket qua xu ly vao bien $newCode.
                    $newCode = 'PN' . now()->format('YmdHis') . str_pad((string) $transaction->id, 4, '0', STR_PAD_LEFT);
                }

                // Luong: Gan ket qua xu ly vao bien $updates.
                $updates = [
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'type' => 'IMPORT',
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'transaction_code' => $newCode,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'note' => null,
                ];

                // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                if ($hasUpdatedAt) {
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    $updates['updated_at'] = now();
                }

                // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                DB::table('stock_transactions')
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('id', $transaction->id)
                    // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                    ->update($updates);
            });
    }

    public function down(): void
    {
        // Khong the khoi phuc ma va ghi chu cu sau khi da doi sang PN.
    }
};
