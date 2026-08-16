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
        // Luong: Gan ket qua xu ly vao bien $admin.
        $admin = User::firstOrCreate(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['email' => 'admin@gmail.com'],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'full_name' => 'Quản trị viên',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'password_hash' => \Illuminate\Support\Facades\Hash::make('123456'),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Bắt buộc: AuthController::login() chặn đăng nhập phía khách
                // nếu email_verified_at còn null.
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'email_verified_at' => now(),
            ]
        );

        // Bảng roles chỉ có: code, name, description, is_system.
        // Không có role_code / role_name.
        // Luong: Gan ket qua xu ly vao bien $role.
        $role = \App\Models\Role::firstOrCreate(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['code' => 'ADMIN'],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Quản trị hệ thống',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Quyền quản trị toàn bộ hệ thống',
            ]
        );

        // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
        $admin->roles()->syncWithoutDetaching([$role->id]);

        \App\Models\Role::firstOrCreate(
            ['code' => 'CONTENT'],
            [
                'name' => 'Nhân viên nội dung',
                'description' => 'Có thể tạo bài viết bản nháp để admin duyệt',
                'is_system' => true,
            ]
        );

        // Luong: Gan ket qua xu ly vao bien $warehouse.
        $warehouse = \App\Models\Warehouse::firstOrCreate(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['warehouse_code' => 'KHOCANH'],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Kho hàng trung tâm',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'type' => 'NORMAL',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'capacity' => 50000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'address_detail' => 'Hà Nội',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
            ]
        );

        // Luong: Gan ket qua xu ly vao bien $quarantine.
        $quarantine = \App\Models\Warehouse::firstOrCreate(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            ['warehouse_code' => 'KHOLOI'],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Kho hàng lỗi / chờ xử lý',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'type' => 'QUARANTINE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'capacity' => 10000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'address_detail' => 'Khu vực lưu hàng lỗi',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
            ]
        );

        \App\Models\ReturnReason::updateOrCreate(
            ['code' => 'NOT_WANTED'],
            [
                'name' => 'Không mua nữa',
                'type' => 'RETURN',
                'status' => 'ACTIVE',
            ]
        );

        \App\Models\ReturnReason::updateOrCreate(
            ['code' => 'OTHER'],
            [
                'name' => 'Lý do khác',
                'type' => 'BOTH',
                'status' => 'ACTIVE',
            ]
        );
    }
}
