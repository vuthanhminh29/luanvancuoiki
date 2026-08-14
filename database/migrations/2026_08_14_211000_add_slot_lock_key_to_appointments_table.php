<?php

use App\Models\Appointment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'slot_lock_key')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('slot_lock_key', 32)->nullable()->after('appointment_time');
        });

        $usedKeys = [];

        DB::table('appointments')
            ->whereIn('status', Appointment::ACTIVE_SLOT_STATUSES)
            ->orderBy('id')
            ->select(['id', 'appointment_date', 'appointment_time'])
            ->chunkById(100, function ($appointments) use (&$usedKeys): void {
                foreach ($appointments as $appointment) {
                    $key = Appointment::slotLockKeyFor((string) $appointment->appointment_date, (string) $appointment->appointment_time);

                    if (isset($usedKeys[$key])) {
                        continue;
                    }

                    DB::table('appointments')
                        ->where('id', $appointment->id)
                        ->update(['slot_lock_key' => $key]);

                    $usedKeys[$key] = true;
                }
            });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unique('slot_lock_key', 'appointments_slot_lock_key_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'slot_lock_key')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropUnique('appointments_slot_lock_key_unique');
            $table->dropColumn('slot_lock_key');
        });
    }
};
