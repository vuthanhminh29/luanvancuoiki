<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->updateOrInsert(
            ['code' => 'CONTENT'],
            [
                'name' => 'Nhân viên nội dung',
                'description' => 'Có thể tạo bài viết bản nháp để admin duyệt',
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->where('code', 'CONTENT')->delete();
    }
};
