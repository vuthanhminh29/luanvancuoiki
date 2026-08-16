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
        Schema::create('jobs', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('queue')->index();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->longText('payload');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->unsignedSmallInteger('attempts');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->unsignedInteger('reserved_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->unsignedInteger('available_at');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->unsignedInteger('created_at');
        });

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('job_batches', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('id')->primary();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('name');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('total_jobs');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('pending_jobs');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('failed_jobs');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->longText('failed_job_ids');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->mediumText('options')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('cancelled_at')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('created_at');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('finished_at')->nullable();
        });

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('failed_jobs', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('uuid')->unique();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('connection');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('queue');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->longText('payload');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->longText('exception');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamp('failed_at')->useCurrent();

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('jobs');
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('job_batches');
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('failed_jobs');
    }
};
