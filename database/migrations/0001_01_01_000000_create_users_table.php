<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('users', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('email')->unique();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('password_hash');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('full_name', 100);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('phone', 20)->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('avatar_url')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('gender', 20)->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->date('date_of_birth')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('provider', 20)->default('LOCAL');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('google_id')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('email_verified_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('status', 20)->default('ACTIVE');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->unsignedInteger('failed_login_count')->default(0);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('last_failed_login_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('locked_until')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('last_login_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamps();
        });

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('token_hash', 64);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('expires_at');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('used_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('created_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->index(['user_id', 'token_hash']);
        });

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('sessions', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('id')->primary();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->foreignId('user_id')->nullable()->index();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('ip_address', 45)->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->text('user_agent')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->longText('payload');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('sessions');
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('password_reset_tokens');
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('users');
    }
};
