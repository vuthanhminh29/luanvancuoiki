<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('stock_transactions') || ! Schema::hasColumn('stock_transactions', 'type')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('stock_transactions')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereNotIn('type', ['IMPORT', 'EXPORT', 'SALE_OUT', 'DAMAGE'])
            // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
            ->update(['type' => 'IMPORT']);
    }

    public function down(): void
    {
        // Khong the khoi phuc loai phieu cu sau khi da gop.
    }
};
