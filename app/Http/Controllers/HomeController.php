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
    public function __invoke(): View
    {
        $homeLayout = DB::table('home_layouts')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('section_key');

        return view('home', [
            'banners' => Banner::visible('HOME_SLIDER')->limit(3)->get(),
            'featuredCategories' => Category::active()
                ->withCount(['products as active_products_count' => fn ($query) => $query->active()])
                ->orderBy('id')
                ->get()
                ->filter(fn (Category $category) => (int) $category->active_products_count > 0)
                ->take(6)
                ->values(),
            'featuredProducts' => Product::active()
                ->with(['brand', 'category'])
                ->orderByDesc('view_count')
                ->limit(8)
                ->get(),
            'trendProducts' => Product::active()
                ->with(['brand', 'category'])
                ->orderByDesc('view_count')
                ->limit(3)
                ->get(),
            'newProducts' => Product::active()
                ->with(['brand', 'category'])
                ->latest()
                ->limit(8)
                ->get(),
            'posts' => Post::published()->limit(5)->get(),
            'homeLayout' => $homeLayout,
        ]);
    }
}
