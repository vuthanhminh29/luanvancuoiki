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
            ALTER TABLE user_addresses
                MODIFY phone varchar(20) NULL,
                MODIFY province_code varchar(20) NULL,
                MODIFY district_code varchar(20) NULL,
                MODIFY district_name varchar(100) NULL,
                MODIFY ward_code varchar(20) NULL,
                MODIFY ward_name varchar(100) NULL
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
            UPDATE user_addresses
            SET
                phone = COALESCE(phone, ''),
                province_code = COALESCE(province_code, ''),
                district_code = COALESCE(district_code, ''),
                district_name = COALESCE(district_name, ''),
                ward_code = COALESCE(ward_code, ''),
                ward_name = COALESCE(ward_name, '')
        ");

        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        DB::statement('
            ALTER TABLE user_addresses
                MODIFY phone varchar(20) NOT NULL,
                MODIFY province_code varchar(20) NOT NULL,
                MODIFY district_code varchar(20) NOT NULL,
                MODIFY district_name varchar(100) NOT NULL,
                MODIFY ward_code varchar(20) NOT NULL,
                MODIFY ward_name varchar(100) NOT NULL
        ');
    }
};
