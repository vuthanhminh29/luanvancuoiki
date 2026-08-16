<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tráng/loại tròng kính hiển thị ở công cụ "Chọn tròng kính phù hợp"
        // (/chon-trong-kinh). Trước đây khai báo cứng trong
        // StyleAdvisorController::LENS_OPTIONS, chuyển qua bảng để admin tự
        // thêm/sửa/ẩn được, không phải sửa code mỗi lần đổi giá.
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Schema::create('lens_options', function (Blueprint $table) {
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->id();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('code', 30)->unique();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('name', 150);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->text('description')->nullable();
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->decimal('price', 12, 2)->default(0);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('icon', 50)->default('fa-circle');

            // Nhóm hiển thị trên các tab của trang tư vấn: nhu-cau, tinh-nang,
            // pho-thong, cao-cap. Một tròng có thể thuộc nhiều nhóm cùng lúc.
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->json('groups');

            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->string('status', 20)->default('ACTIVE');
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->integer('sort_order')->default(0);
            // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
            $table->timestamps();
        });

        // Chuyển nguyên dữ liệu tĩnh cũ vào bảng để trang tư vấn không bị trống
        // sau khi đổi nguồn dữ liệu.
        // Luong: Gan ket qua xu ly vao bien $now.
        $now = now();
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        DB::table('lens_options')->insert([
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => 'CHONG_UV',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Tráng chống UV 100%',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Ngăn tia UV có hại, bảo vệ mắt khi ra ngoài trời.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => 1200000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'icon' => 'fa-sun',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'groups' => json_encode(['nhu-cau', 'pho-thong']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'sort_order' => 10,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $now,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => $now,
            ],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => 'CHONG_CHOI',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Tráng chống chói (AR Coating)',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Giảm chói khi lái xe ban đêm, hạn chế phản quang từ màn hình.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => 1800000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'icon' => 'fa-car',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'groups' => json_encode(['nhu-cau', 'pho-thong']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'sort_order' => 20,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $now,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => $now,
            ],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => 'CHONG_XUOC',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Tráng chống xước & chống bám vân tay',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Bề mặt cứng hơn, hạn chế trầy xước khi vệ sinh hằng ngày.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => 900000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'icon' => 'fa-shield-alt',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'groups' => json_encode(['tinh-nang', 'pho-thong']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'sort_order' => 30,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $now,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => $now,
            ],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => 'DOI_MAU',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Tráng đổi màu (Photochromic)',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Tự động sậm màu khi ra nắng, trong suốt trở lại khi ở trong nhà.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => 2400000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'icon' => 'fa-adjust',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'groups' => json_encode(['tinh-nang', 'cao-cap']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'sort_order' => 40,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $now,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => $now,
            ],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => 'SIEU_MONG',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Tròng siêu mỏng chiết suất 1.74',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Mỏng nhẹ hơn khoảng 40%, phù hợp độ cận/viễn cao.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => 3200000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'icon' => 'fa-feather',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'groups' => json_encode(['tinh-nang', 'cao-cap']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'sort_order' => 50,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $now,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => $now,
            ],
            [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'code' => 'DA_TRONG',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'name' => 'Tráng đa tròng (Progressive)',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'description' => 'Nhìn rõ ở mọi khoảng cách trên cùng một tròng kính.',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => 3800000,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'icon' => 'fa-layer-group',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'groups' => json_encode(['nhu-cau', 'cao-cap']),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => 'ACTIVE',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'sort_order' => 60,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_at' => $now,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        Schema::dropIfExists('lens_options');
    }
};
