<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('return_reasons')) {
            $exists = Schema::hasColumn('return_reasons', 'code')
                ? DB::table('return_reasons')->where('code', 'NOT_WANTED')->exists()
                : DB::table('return_reasons')->where('name', 'Không mua nữa')->exists();

            if (! $exists) {
                DB::table('return_reasons')->insert($this->returnReasonValues());
            }
        }

        $this->widenStockTransactionTypeEnum();
    }

    public function down(): void
    {
        if (! Schema::hasTable('return_reasons')) {
            return;
        }

        $values = [];

        if (Schema::hasColumn('return_reasons', 'status')) {
            $values['status'] = 'INACTIVE';
        }

        if (Schema::hasColumn('return_reasons', 'updated_at')) {
            $values['updated_at'] = now();
        }

        if ($values === []) {
            return;
        }

        $query = DB::table('return_reasons');

        if (Schema::hasColumn('return_reasons', 'code')) {
            $query->where('code', 'NOT_WANTED');
        } else {
            $query->where('name', 'Không mua nữa');
        }

        $query->update($values);
    }

    private function returnReasonValues(): array
    {
        $values = ['name' => 'Không mua nữa'];

        if (Schema::hasColumn('return_reasons', 'code')) {
            $values['code'] = 'NOT_WANTED';
        }

        if (Schema::hasColumn('return_reasons', 'type')) {
            $values['type'] = 'RETURN';
        }

        if (Schema::hasColumn('return_reasons', 'status')) {
            $values['status'] = 'ACTIVE';
        }

        if (Schema::hasColumn('return_reasons', 'created_at')) {
            $values['created_at'] = now();
        }

        if (Schema::hasColumn('return_reasons', 'updated_at')) {
            $values['updated_at'] = now();
        }

        return $values;
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
