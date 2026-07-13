<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.posts.index', [
            'posts' => Post::with('category')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.shared.form', [
            'title' => 'Thêm bài viết',
            'subtitle' => 'Tạo bài viết tin tức mới',
            'action' => route('admin.posts.store'),
            'submitLabel' => 'Lưu bài viết',
            'sectionTitle' => 'Bài viết',
            'formStyle' => 'classic',
            'fields' => $this->postFields(),
            'backRoute' => route('admin.posts.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Post::create($this->preparePostData($request, $this->validatePost($request)));

        return redirect()->route('admin.posts.index')->with('success', 'Đã thêm bài viết.');
    }

    public function edit(Post $post): View
    {
        return view('admin.shared.form', [
            'title' => 'Cập nhật bài viết',
            'subtitle' => $post->title,
            'action' => route('admin.posts.update', $post),
            'method' => 'PUT',
            'submitLabel' => 'Lưu bài viết',
            'sectionTitle' => 'Bài viết',
            'formStyle' => 'classic',
            'fields' => $this->postFields($post),
            'backRoute' => route('admin.posts.index'),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($this->preparePostData($request, $this->validatePost($request), $post));

        return redirect()->route('admin.posts.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function hidden(Post $post): RedirectResponse
    {
        $post->update(['status' => 'HIDDEN']);

        return back()->with('success', 'Đã ẩn bài viết.');
    }

    public function uploadEditorImage(Request $request): JsonResponse
    {
        $request->validate(['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096']]);

        return response()->json([
            'url' => asset('upload/' . $this->storeUpload($request, 'upload', 'BaiViet')),
        ]);
    }

    public function categories(Request $request): View
    {
        $categories = PostCategory::query()
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn ($query) => $query->where('status', 'PUBLISHED'),
                'posts as draft_posts_count' => fn ($query) => $query->where('status', 'DRAFT'),
            ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = '%' . trim((string) $request->keyword) . '%';

                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $keyword)
                    ->orWhere('slug', 'like', $keyword));
            })
            ->orderByRaw("status = 'ACTIVE' desc")
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.posts.categories', [
            'categories' => $categories,
            'filters' => $request->only(['status', 'keyword']),
            'summary' => [
                'total' => PostCategory::count(),
                'active' => PostCategory::where('status', 'ACTIVE')->count(),
                'inactive' => PostCategory::where('status', 'INACTIVE')->count(),
                'posts' => Post::count(),
            ],
        ]);
    }

    public function createCategory(): View
    {
        return view('admin.shared.form', [
            'title' => 'Thêm chuyên mục bài viết',
            'subtitle' => 'Tạo chuyên mục tin tức mới',
            'action' => route('admin.posts.categories.store'),
            'submitLabel' => 'Lưu chuyên mục',
            'formStyle' => 'classic',
            'fields' => $this->categoryFields(),
            'backRoute' => route('admin.posts.categories'),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        PostCategory::create($this->prepareCategoryData($this->validateCategory($request)));

        return redirect()->route('admin.posts.categories')->with('success', 'Đã thêm chuyên mục bài viết.');
    }

    public function editCategory(PostCategory $category): View
    {
        return view('admin.shared.form', [
            'title' => 'Cập nhật chuyên mục bài viết',
            'subtitle' => $category->name,
            'action' => route('admin.posts.categories.update', $category),
            'method' => 'PUT',
            'submitLabel' => 'Lưu chuyên mục',
            'formStyle' => 'classic',
            'fields' => $this->categoryFields($category),
            'backRoute' => route('admin.posts.categories'),
        ]);
    }

    public function updateCategory(Request $request, PostCategory $category): RedirectResponse
    {
        $category->update($this->prepareCategoryData($this->validateCategory($request)));

        return redirect()->route('admin.posts.categories')->with('success', 'Đã cập nhật chuyên mục.');
    }

    public function hiddenCategory(PostCategory $category): RedirectResponse
    {
        $category->update(['status' => 'INACTIVE']);

        return back()->with('success', 'Đã ẩn chuyên mục.');
    }

    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:300'],
            'category_id' => ['nullable', 'exists:post_categories,id'],
            'thumbnail_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:DRAFT,PUBLISHED,HIDDEN'],
        ]);
    }

    private function preparePostData(Request $request, array $data, ?Post $post = null): array
    {
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['title']) . '-' . ($post?->id ?? time());

        if ($request->hasFile('thumbnail_url')) {
            $data['thumbnail_url'] = $this->storeUpload($request, 'thumbnail_url', 'BaiViet');
        } else {
            unset($data['thumbnail_url']);
        }

        if ($data['status'] === 'PUBLISHED' && ! $post?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);
    }

    private function prepareCategoryData(array $data): array
    {
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['name']);

        return $data;
    }

    private function postFields(?Post $post = null): array
    {
        return [
            ['name' => 'title', 'label' => 'Tiêu đề', 'required' => true, 'value' => $post?->title],
            ['name' => 'slug', 'label' => 'Slug', 'value' => $post?->slug],
            ['name' => 'category_id', 'label' => 'Chuyên mục', 'type' => 'select', 'side' => true, 'value' => $post?->category_id, 'placeholder' => 'Không có', 'options' => PostCategory::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'thumbnail_url', 'label' => 'Ảnh đại diện', 'type' => 'file', 'side' => true, 'preview' => $post?->image_url],
            ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'side' => true, 'required' => true, 'value' => $post?->status ?? 'DRAFT', 'options' => ['DRAFT' => 'Bản nháp', 'PUBLISHED' => 'Đã đăng', 'HIDDEN' => 'Ẩn']],
            ['name' => 'summary', 'label' => 'Tóm tắt', 'type' => 'textarea', 'column' => 'col-12', 'rows' => 3, 'value' => $post?->summary],
            ['name' => 'content', 'id' => 'short_description', 'label' => 'Nội dung', 'type' => 'textarea', 'column' => 'col-12', 'rows' => 8, 'value' => $post?->content],
        ];
    }

    private function categoryFields(?PostCategory $category = null): array
    {
        return [
            ['name' => 'name', 'label' => 'Tên chuyên mục', 'required' => true, 'value' => $category?->name],
            ['name' => 'slug', 'label' => 'Slug', 'value' => $category?->slug],
            ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'required' => true, 'value' => $category?->status ?? 'ACTIVE', 'options' => ['ACTIVE' => 'Hiển thị', 'INACTIVE' => 'Tạm ẩn']],
        ];
    }

    private function storeUpload(Request $request, string $field, string $folder): string
    {
        $file = $request->file($field);
        $name = (string) Str::uuid() . '.' . $file->extension();
        $path = public_path('upload/' . $folder);

        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file->move($path, $name);

        return $folder . '/' . $name;
    }
}
