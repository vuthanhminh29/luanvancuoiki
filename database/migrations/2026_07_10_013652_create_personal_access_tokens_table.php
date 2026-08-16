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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->morphs('tokenable');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->text('name');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('token', 64)->unique();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->text('abilities')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('last_used_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('expires_at')->nullable()->index();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('personal_access_tokens');
    }
};
