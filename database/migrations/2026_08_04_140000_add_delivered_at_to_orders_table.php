<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'delivered_at')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('orders', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'delivered_at')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('orders', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropColumn('delivered_at');
        });
    }
};
