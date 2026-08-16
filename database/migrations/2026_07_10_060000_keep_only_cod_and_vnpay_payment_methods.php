<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! \Illuminate\Support\Facades\Schema::hasTable('payments') || ! \Illuminate\Support\Facades\Schema::hasTable('orders')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement("ALTER TABLE payments MODIFY method ENUM('COD','VNPAY') NOT NULL");
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('COD','VNPAY') NOT NULL DEFAULT 'COD'");
    }

    public function down(): void
    {
        // Non-reversible migration on payment method enums
    }
};
