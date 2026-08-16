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
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'slot_lock_key')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('slot_lock_key', 32)->nullable()->after('appointment_time');
        });

        // Luong: Gan ket qua xu ly vao bien $usedKeys.
        $usedKeys = [];

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('appointments')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->whereIn('status', Appointment::ACTIVE_SLOT_STATUSES)
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select(['id', 'appointment_date', 'appointment_time'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->chunkById(100, function ($appointments) use (&$usedKeys): void {
                // Luong: Lap qua tung phan tu de xu ly lan luot.
                foreach ($appointments as $appointment) {
                    // Luong: Gan ket qua xu ly vao bien $key.
                    $key = Appointment::slotLockKeyFor((string) $appointment->appointment_date, (string) $appointment->appointment_time);

                    // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
                    if (isset($usedKeys[$key])) {
                        // Luong: Bo qua vong lap hien tai va chuyen sang phan tu tiep theo.
                        continue;
                    }

                    // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
                    DB::table('appointments')
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('id', $appointment->id)
                        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                        ->update(['slot_lock_key' => $key]);

                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    $usedKeys[$key] = true;
                }
            });

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->unique('slot_lock_key', 'appointments_slot_lock_key_unique');
        });
    }

    public function down(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'slot_lock_key')) {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('appointments', function (Blueprint $table): void {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropUnique('appointments_slot_lock_key_unique');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropColumn('slot_lock_key');
        });
    }
};
