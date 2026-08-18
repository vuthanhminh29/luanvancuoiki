<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'cancel_request_count')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedTinyInteger('cancel_request_count')->default(0)->after('cancel_reason');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'cancel_request_count')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('cancel_request_count');
        });
    }
};
