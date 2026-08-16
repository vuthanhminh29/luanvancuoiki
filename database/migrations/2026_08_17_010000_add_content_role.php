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

        $codeColumn = Schema::hasColumn('roles', 'code')
            ? 'code'
            : (Schema::hasColumn('roles', 'role_code') ? 'role_code' : null);

        if (! $codeColumn) {
            return;
        }

        $values = [];

        if (Schema::hasColumn('roles', 'name')) {
            $values['name'] = 'Nhân viên nội dung';
        }

        if (Schema::hasColumn('roles', 'role_name')) {
            $values['role_name'] = 'Nhân viên nội dung';
        }

        if (Schema::hasColumn('roles', 'description')) {
            $values['description'] = 'Có thể tạo bài viết bản nháp để admin duyệt';
        }

        if (Schema::hasColumn('roles', 'is_system')) {
            $values['is_system'] = true;
        }

        if (Schema::hasColumn('roles', 'updated_at')) {
            $values['updated_at'] = now();
        }

        if (Schema::hasColumn('roles', 'created_at')) {
            $values['created_at'] = now();
        }

        DB::table('roles')->updateOrInsert([$codeColumn => 'CONTENT'], $values);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $codeColumn = Schema::hasColumn('roles', 'code')
            ? 'code'
            : (Schema::hasColumn('roles', 'role_code') ? 'role_code' : null);

        if ($codeColumn) {
            DB::table('roles')->where($codeColumn, 'CONTENT')->delete();
        }
    }
};
