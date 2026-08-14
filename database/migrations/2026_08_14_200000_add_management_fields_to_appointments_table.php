<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'no_show_at')) {
                $table->timestamp('no_show_at')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'reschedule_count')) {
                $table->unsignedTinyInteger('reschedule_count')->default(0);
            }

            if (! Schema::hasColumn('appointments', 'last_rescheduled_at')) {
                $table->timestamp('last_rescheduled_at')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'reschedule_reason')) {
                $table->text('reschedule_reason')->nullable();
            }

            if (! Schema::hasColumn('appointments', 'reminder_email_sent_at')) {
                $table->timestamp('reminder_email_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $columns = [
                'confirmed_at',
                'cancelled_at',
                'completed_at',
                'no_show_at',
                'cancel_reason',
                'admin_note',
                'reschedule_count',
                'last_rescheduled_at',
                'reschedule_reason',
                'reminder_email_sent_at',
            ];

            $existingColumns = array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('appointments', $column)
            );

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
