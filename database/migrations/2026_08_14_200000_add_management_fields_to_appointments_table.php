<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('appointments')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table) {
            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'confirmed_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('confirmed_at')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'cancelled_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('cancelled_at')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'completed_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('completed_at')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'no_show_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('no_show_at')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'cancel_reason')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->text('cancel_reason')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'admin_note')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->text('admin_note')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'reschedule_count')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->unsignedTinyInteger('reschedule_count')->default(0);
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'last_rescheduled_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('last_rescheduled_at')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'reschedule_reason')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->text('reschedule_reason')->nullable();
            }

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if (! Schema::hasColumn('appointments', 'reminder_email_sent_at')) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->timestamp('reminder_email_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('appointments')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table) {
            // Luong: Gan ket qua xu ly vao bien $columns.
            $columns = [
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'confirmed_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'cancelled_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'completed_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'no_show_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'cancel_reason',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'admin_note',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'reschedule_count',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'last_rescheduled_at',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'reschedule_reason',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'reminder_email_sent_at',
            ];

            // Luong: Gan ket qua xu ly vao bien $existingColumns.
            $existingColumns = array_filter(
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                $columns,
                // Luong: Dinh nghia callback ngan gon cho thao tac hien tai.
                fn (string $column): bool => Schema::hasColumn('appointments', $column)
            );

            // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
            if ($existingColumns !== []) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $table->dropColumn($existingColumns);
            }
        });
    }
};
