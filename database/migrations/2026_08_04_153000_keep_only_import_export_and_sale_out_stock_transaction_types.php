<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_transactions') || ! Schema::hasColumn('stock_transactions', 'type')) {
            return;
        }

        DB::table('stock_transactions')
            ->whereNotIn('type', ['IMPORT', 'EXPORT', 'SALE_OUT', 'DAMAGE'])
            ->update(['type' => 'IMPORT']);
    }

    public function down(): void
    {
        // Khong the khoi phuc loai phieu cu sau khi da gop.
    }
};
