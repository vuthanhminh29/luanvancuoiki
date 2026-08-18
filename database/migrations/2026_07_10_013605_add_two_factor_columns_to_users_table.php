<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('users', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            // Bang users cua du an dung cot password_hash, KHONG co cot password.
            // Migration goc cua Fortify neo vao after('password') nen khi chay
            // `migrate` tren MySQL sach se loi "Unknown column 'password'" va
            // dung toan bo migration. (SQLite bo qua after() nen khong lo ra.)
            $table->text('two_factor_secret')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->after('password_hash')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->nullable();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->text('two_factor_recovery_codes')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->after('two_factor_secret')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->nullable();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('two_factor_confirmed_at')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->after('two_factor_recovery_codes')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::table('users', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->dropColumn([
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'two_factor_secret',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'two_factor_recovery_codes',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'two_factor_confirmed_at',
            ]);
        });
    }
};
