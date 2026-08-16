<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeLayoutAdminController extends Controller
{
    /**
     * Hiển thị danh sách bố cục trang chủ.
     */
    public function index(): View
    {
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $sections = DB::table('home_layouts')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('sort_order')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('id')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $meta.
        $meta = $this->sectionMeta();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.home-layout.index', compact('sections', 'meta'));
    }

    /**
     * Cập nhật bố cục trang chủ.
     */
    public function update(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'sections' => ['required', 'array'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'sections.*.section_key' => ['required', 'string', 'exists:home_layouts,section_key'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'sections.*.sort_order' => ['required', 'integer', 'min:1', 'max:99'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'sections.*.status' => ['nullable', 'boolean'],
        ]);

        // Luong: Lap qua tung phan tu de xu ly lan luot.
        foreach ($data['sections'] as $section) {
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            DB::table('home_layouts')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('section_key', $section['section_key'])
                // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
                ->update([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'sort_order' => (int) $section['sort_order'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'status' => ! empty($section['status']) ? 1 : 0,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'updated_at' => now(),
                ]);
        }

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.home-layout.index')->with('success', 'Đã lưu bố cục trang chủ.');
    }

    /**
     * Lấy thông tin các section trang chủ.
     */
    private function sectionMeta(): array
    {
        return [
            'banner' => ['icon' => 'fa-images', 'note' => 'Slider/banner đầu trang chủ.'],
            'categories' => ['icon' => 'fa-th-large', 'note' => 'Danh mục nổi bật giúp khách vào nhanh nhóm kính.'],
            'new_products' => ['icon' => 'fa-glasses', 'note' => 'Sản phẩm mới cập nhật.'],
            'best_sellers' => ['icon' => 'fa-fire', 'note' => 'Sản phẩm bán chạy.'],
            'brands' => ['icon' => 'fa-certificate', 'note' => 'Khối thương hiệu kính.'],
            'news' => ['icon' => 'fa-newspaper', 'note' => 'Bài viết nổi bật trên trang chủ.'],
            'services' => ['icon' => 'fa-shipping-fast', 'note' => 'Các quyền lợi/dịch vụ hỗ trợ.'],
            'support' => ['icon' => 'fa-headset', 'note' => 'Kênh hỗ trợ và cam kết mua hàng.'],
        ];
    }
}
