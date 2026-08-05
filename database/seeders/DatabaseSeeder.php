<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'full_name' => 'Quản trị viên',
                'password_hash' => \Illuminate\Support\Facades\Hash::make('123456'),
                'status' => 'ACTIVE',
                // Bắt buộc: AuthController::login() chặn đăng nhập phía khách
                // nếu email_verified_at còn null.
                'email_verified_at' => now(),
            ]
        );

        // Bảng roles chỉ có: code, name, description, is_system.
        // Không có role_code / role_name.
        $role = \App\Models\Role::firstOrCreate(
            ['code' => 'ADMIN'],
            [
                'name' => 'Quản trị hệ thống',
                'description' => 'Quyền quản trị toàn bộ hệ thống',
            ]
        );

        $admin->roles()->syncWithoutDetaching([$role->id]);

        $warehouse = \App\Models\Warehouse::firstOrCreate(
            ['warehouse_code' => 'KHOCANH'],
            [
                'name' => 'Kho hàng trung tâm',
                'type' => 'NORMAL',
                'capacity' => 50000,
                'address_detail' => 'Hà Nội',
                'status' => 'ACTIVE',
            ]
        );

        $quarantine = \App\Models\Warehouse::firstOrCreate(
            ['warehouse_code' => 'KHOLOI'],
            [
                'name' => 'Kho hàng lỗi / chờ xử lý',
                'type' => 'QUARANTINE',
                'capacity' => 10000,
                'address_detail' => 'Khu vực lưu hàng lỗi',
                'status' => 'ACTIVE',
            ]
        );
    }
}
