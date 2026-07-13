<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $selectedCategory = null;

        if ($request->filled('category')) {
            $selectedCategory = PostCategory::query()
                ->where('status', 'ACTIVE')
                ->where('slug', $request->query('category'))
                ->firstOrFail();
        }

        $posts = Post::published()
            ->with('category')
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'selectedCategory' => $selectedCategory,
            'categories' => PostCategory::where('status', 'ACTIVE')
                ->withCount(['posts' => fn ($query) => $query->where('status', 'PUBLISHED')])
                ->orderBy('name')
                ->get(),
            'recentPosts' => Post::published()->limit(5)->get(),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->status === 'PUBLISHED', 404);

        return view('blog.show', [
            'post' => $post->load('category'),
            'relatedPosts' => Post::published()->whereKeyNot($post->id)->limit(3)->get(),
            'recentPosts' => Post::published()->whereKeyNot($post->id)->limit(5)->get(),
            'categories' => PostCategory::where('status', 'ACTIVE')
                ->withCount(['posts' => fn ($query) => $query->where('status', 'PUBLISHED')])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
