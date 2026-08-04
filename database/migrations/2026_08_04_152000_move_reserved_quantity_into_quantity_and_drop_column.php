<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventories') || ! Schema::hasColumn('inventories', 'reserved_quantity')) {
            return;
        }

        $this->dropReservedQuantityChecks();

        DB::table('inventories')
            ->where('reserved_quantity', '>', 0)
            ->update([
                'quantity' => DB::raw('quantity + reserved_quantity'),
                'updated_at' => now(),
            ]);

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });

        DB::statement('ALTER TABLE `inventories` ADD CONSTRAINT `chk_inventories_quantity` CHECK (`quantity` >= 0)');
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventories') || Schema::hasColumn('inventories', 'reserved_quantity')) {
            return;
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->integer('reserved_quantity')->default(0)->after('quantity');
        });
    }

    private function dropReservedQuantityChecks(): void
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.CHECK_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CHECK_CLAUSE LIKE '%reserved_quantity%'
        ");

        foreach ($constraints as $constraint) {
            $name = str_replace('`', '``', (string) $constraint->CONSTRAINT_NAME);

            DB::statement("ALTER TABLE `inventories` DROP CHECK `{$name}`");
        }
    }
};
