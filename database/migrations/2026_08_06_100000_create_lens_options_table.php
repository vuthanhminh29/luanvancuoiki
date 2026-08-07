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
        Schema::create('lens_options', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('icon', 50)->default('fa-circle');

            // Nhóm hiển thị trên các tab của trang tư vấn: nhu-cau, tinh-nang,
            // pho-thong, cao-cap. Một tròng có thể thuộc nhiều nhóm cùng lúc.
            $table->json('groups');

            $table->string('status', 20)->default('ACTIVE');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Chuyển nguyên dữ liệu tĩnh cũ vào bảng để trang tư vấn không bị trống
        // sau khi đổi nguồn dữ liệu.
        $now = now();
        DB::table('lens_options')->insert([
            [
                'code' => 'CHONG_UV',
                'name' => 'Tráng chống UV 100%',
                'description' => 'Ngăn tia UV có hại, bảo vệ mắt khi ra ngoài trời.',
                'price' => 1200000,
                'icon' => 'fa-sun',
                'groups' => json_encode(['nhu-cau', 'pho-thong']),
                'status' => 'ACTIVE',
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CHONG_CHOI',
                'name' => 'Tráng chống chói (AR Coating)',
                'description' => 'Giảm chói khi lái xe ban đêm, hạn chế phản quang từ màn hình.',
                'price' => 1800000,
                'icon' => 'fa-car',
                'groups' => json_encode(['nhu-cau', 'pho-thong']),
                'status' => 'ACTIVE',
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CHONG_XUOC',
                'name' => 'Tráng chống xước & chống bám vân tay',
                'description' => 'Bề mặt cứng hơn, hạn chế trầy xước khi vệ sinh hằng ngày.',
                'price' => 900000,
                'icon' => 'fa-shield-alt',
                'groups' => json_encode(['tinh-nang', 'pho-thong']),
                'status' => 'ACTIVE',
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DOI_MAU',
                'name' => 'Tráng đổi màu (Photochromic)',
                'description' => 'Tự động sậm màu khi ra nắng, trong suốt trở lại khi ở trong nhà.',
                'price' => 2400000,
                'icon' => 'fa-adjust',
                'groups' => json_encode(['tinh-nang', 'cao-cap']),
                'status' => 'ACTIVE',
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SIEU_MONG',
                'name' => 'Tròng siêu mỏng chiết suất 1.74',
                'description' => 'Mỏng nhẹ hơn khoảng 40%, phù hợp độ cận/viễn cao.',
                'price' => 3200000,
                'icon' => 'fa-feather',
                'groups' => json_encode(['tinh-nang', 'cao-cap']),
                'status' => 'ACTIVE',
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DA_TRONG',
                'name' => 'Tráng đa tròng (Progressive)',
                'description' => 'Nhìn rõ ở mọi khoảng cách trên cùng một tròng kính.',
                'price' => 3800000,
                'icon' => 'fa-layer-group',
                'groups' => json_encode(['nhu-cau', 'cao-cap']),
                'status' => 'ACTIVE',
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lens_options');
    }
};
