<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_transactions')) {
            return;
        }

        $hasUpdatedAt = Schema::hasColumn('stock_transactions', 'updated_at');

        DB::table('stock_transactions')
            ->where(function ($query) {
                $query->where('transaction_code', 'like', 'RETURN_IN%')
                    ->orWhere('transaction_code', 'like', 'ADJUST%')
                    ->orWhere('note', 'like', '%Nhập hàng hoàn%')
                    ->orWhere('note', 'like', '%Hoàn tồn%');
            })
            ->orderBy('id')
            ->each(function ($transaction) use ($hasUpdatedAt) {
                $newCode = $transaction->transaction_code;

                if (is_string($newCode) && str_starts_with($newCode, 'RETURN_IN')) {
                    $newCode = 'PN' . substr($newCode, strlen('RETURN_IN'));
                } elseif (is_string($newCode) && str_starts_with($newCode, 'ADJUST')) {
                    $newCode = 'PN' . substr($newCode, strlen('ADJUST'));
                }

                if (
                    DB::table('stock_transactions')
                        ->where('transaction_code', $newCode)
                        ->where('id', '<>', $transaction->id)
                        ->exists()
                ) {
                    $newCode = 'PN' . now()->format('YmdHis') . str_pad((string) $transaction->id, 4, '0', STR_PAD_LEFT);
                }

                $updates = [
                    'type' => 'IMPORT',
                    'transaction_code' => $newCode,
                    'note' => null,
                ];

                if ($hasUpdatedAt) {
                    $updates['updated_at'] = now();
                }

                DB::table('stock_transactions')
                    ->where('id', $transaction->id)
                    ->update($updates);
            });
    }

    public function down(): void
    {
        // Khong the khoi phuc ma va ghi chu cu sau khi da doi sang PN.
    }
};
