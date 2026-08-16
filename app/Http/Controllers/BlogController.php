<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Hiển thị danh sách bài viết blog.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $selectedCategory.
        $selectedCategory = null;

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->filled('category')) {
            // Luong: Gan ket qua xu ly vao bien $selectedCategory.
            $selectedCategory = PostCategory::query()
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('status', 'ACTIVE')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('slug', $request->query('category'))
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->firstOrFail();
        }

        // Luong: Gan ket qua xu ly vao bien $posts.
        $posts = Post::published()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('category')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(9)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withQueryString();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('blog.index', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'posts' => $posts,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'selectedCategory' => $selectedCategory,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'categories' => PostCategory::where('status', 'ACTIVE')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->withCount(['posts' => fn ($query) => $query->where('status', 'PUBLISHED')])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->orderBy('name')
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get(),
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'recentPosts' => Post::published()->limit(5)->get(),
        ]);
    }

    /**
     * Hiển thị chi tiết bài viết blog.
     */
    public function show(Post $post): View
    {
        // Luong: Xu ly dong logic tiep theo trong ham public nay.
        abort_unless($post->status === 'PUBLISHED', 404);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('blog.show', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'post' => $post->load('category'),
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            'relatedPosts' => Post::published()->whereKeyNot($post->id)->limit(3)->get(),
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            'recentPosts' => Post::published()->whereKeyNot($post->id)->limit(5)->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'categories' => PostCategory::where('status', 'ACTIVE')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->withCount(['posts' => fn ($query) => $query->where('status', 'PUBLISHED')])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->orderBy('name')
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get(),
        ]);
    }
}
