<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('user_addresses') || DB::getDriverName() !== 'mysql') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement('
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ALTER TABLE user_addresses
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY phone varchar(20) NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY province_code varchar(20) NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY district_code varchar(20) NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY district_name varchar(100) NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY ward_code varchar(20) NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY ward_name varchar(100) NULL
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        ');
    }

    public function down(): void
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! Schema::hasTable('user_addresses') || DB::getDriverName() !== 'mysql') {
            // Luong: Tra ve ket qua cuoi cung cua ham.
            return;
        }

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement("
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            UPDATE user_addresses
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            SET
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                phone = COALESCE(phone, ''),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                province_code = COALESCE(province_code, ''),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                district_code = COALESCE(district_code, ''),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                district_name = COALESCE(district_name, ''),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ward_code = COALESCE(ward_code, ''),
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                ward_name = COALESCE(ward_name, '')
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        ");

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement('
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ALTER TABLE user_addresses
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY phone varchar(20) NOT NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY province_code varchar(20) NOT NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY district_code varchar(20) NOT NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY district_name varchar(100) NOT NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY ward_code varchar(20) NOT NULL,
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                MODIFY ward_name varchar(100) NOT NULL
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        ');
    }
};
