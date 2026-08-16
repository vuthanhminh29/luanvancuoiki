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
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('cache', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('key')->primary();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->mediumText('value');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->bigInteger('expiration')->index();
        });

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('cache_locks', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('key')->primary();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('owner');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->bigInteger('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('cache');
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('cache_locks');
    }
};
