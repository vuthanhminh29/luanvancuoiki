<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeLayoutAdminController extends Controller
{
    public function index(): View
    {
        $sections = DB::table('home_layouts')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $meta = $this->sectionMeta();

        return view('admin.home-layout.index', compact('sections', 'meta'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.section_key' => ['required', 'string', 'exists:home_layouts,section_key'],
            'sections.*.sort_order' => ['required', 'integer', 'min:1', 'max:99'],
            'sections.*.status' => ['nullable', 'boolean'],
        ]);

        foreach ($data['sections'] as $section) {
            DB::table('home_layouts')
                ->where('section_key', $section['section_key'])
                ->update([
                    'sort_order' => (int) $section['sort_order'],
                    'status' => ! empty($section['status']) ? 1 : 0,
                    'updated_at' => now(),
                ]);
        }

        return redirect()->route('admin.home-layout.index')->with('success', 'Đã lưu bố cục trang chủ.');
    }

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
