<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('return_reasons')) {
            $exists = DB::table('return_reasons')->where('code', 'NOT_WANTED')->exists();

            if (! $exists) {
                DB::table('return_reasons')->insert([
                    'code' => 'NOT_WANTED',
                    'name' => 'Không mua nữa',
                    'type' => 'RETURN',
                    'status' => 'ACTIVE',
                ]);
            }
        }

        $this->widenStockTransactionTypeEnum();
    }

    public function down(): void
    {
        if (Schema::hasTable('return_reasons')) {
            DB::table('return_reasons')
                ->where('code', 'NOT_WANTED')
                ->update(['status' => 'INACTIVE']);
        }
    }

    private function widenStockTransactionTypeEnum(): void
    {
        if (! Schema::hasTable('stock_transactions') || ! Schema::hasColumn('stock_transactions', 'type')) {
            return;
        }

        try {
            $column = DB::selectOne(
                'SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['stock_transactions', 'type']
            );
        } catch (Throwable) {
            return;
        }

        if (! $column) {
            return;
        }

        $columnType = (string) $column->column_type;

        if (! str_starts_with(strtolower($columnType), 'enum') || str_contains($columnType, 'EXCHANGE_OUT')) {
            return;
        }

        $nullable = strtoupper((string) $column->is_nullable) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column->column_default !== null
            ? " DEFAULT '" . addslashes((string) $column->column_default) . "'"
            : '';

        DB::statement(
            "ALTER TABLE `stock_transactions` MODIFY `type` ENUM('IMPORT','EXPORT','TRANSFER','ADJUST','RETURN_IN','SALE_OUT','DAMAGE','EXCHANGE_OUT') {$nullable}{$default}"
        );
    }
};
