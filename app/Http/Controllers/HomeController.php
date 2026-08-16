<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Hiển thị trang chủ.
     */
    public function __invoke(): View
    {
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $homeLayout = DB::table('home_layouts')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('sort_order')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->keyBy('section_key');

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('home', [
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'banners' => Banner::visible('HOME_SLIDER')->limit(3)->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'featuredCategories' => Category::active()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->withCount(['products as active_products_count' => fn ($query) => $query->active()])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->orderBy('id')
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->filter(fn (Category $category) => (int) $category->active_products_count > 0)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->take(6)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->values(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'featuredProducts' => Product::active()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with(['brand', 'category', 'variants'])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->orderByDesc('view_count')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->limit(8)
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'trendProducts' => Product::active()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with(['brand', 'category', 'variants'])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->orderByDesc('view_count')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->limit(3)
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'newProducts' => Product::active()
                // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
                ->with(['brand', 'category', 'variants'])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->latest()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->limit(8)
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get(),
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'posts' => Post::published()->limit(5)->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'homeLayout' => $homeLayout,
        ]);
    }
}
