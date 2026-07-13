<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY method ENUM('COD','VNPAY') NOT NULL");
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('COD','VNPAY') NOT NULL DEFAULT 'COD'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY method ENUM('COD','VNPAY') NOT NULL");
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('COD','VNPAY') NOT NULL DEFAULT 'COD'");
    }
};
