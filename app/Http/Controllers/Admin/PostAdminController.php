<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostAdminController extends Controller
{
    /**
     * Hiển thị danh sách bài viết.
     */
    public function index(): View
    {
        $isAdmin = $this->isAdminUser();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.posts.index', [
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'posts' => Post::with(['category', 'creator'])->latest()->paginate(15),
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Hiển thị form thêm bài viết.
     */
    public function create(): View
    {
        $isAdmin = $this->isAdminUser();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => 'Tạo bài viết tin tức mới',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.posts.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'sectionTitle' => 'Bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'classic',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->postFields(null, $isAdmin),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.posts.index'),
        ]);
    }

    /**
     * Lưu bài viết mới.
     */
    public function store(Request $request): RedirectResponse
    {
        $isAdmin = $this->isAdminUser();
        $data = $this->preparePostData($request, $this->validatePost($request, $isAdmin), null, $isAdmin);

        if (! $isAdmin) {
            $this->attachContentRoleToCurrentUser();
        }

        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Post::create($data);

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            ->route('admin.posts.index')
            ->with('success', $isAdmin ? 'Đã thêm bài viết.' : 'Đã gửi bài viết vào bản nháp, chờ admin duyệt.');
    }

    /**
     * Hiển thị form sửa bài viết.
     */
    public function edit(Post $post): View
    {
        $this->assertCanEditPost($post);
        $isAdmin = $this->isAdminUser();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => $post->title,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.posts.update', $post),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'sectionTitle' => 'Bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'classic',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->postFields($post, $isAdmin),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.posts.index'),
        ]);
    }

    /**
     * Cập nhật bài viết.
     */
    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->assertCanEditPost($post);
        $isAdmin = $this->isAdminUser();

        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $post->update($this->preparePostData($request, $this->validatePost($request, $isAdmin), $post, $isAdmin));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.posts.index')->with('success', 'Đã cập nhật bài viết.');
    }

    /**
     * Ẩn bài viết.
     */
    public function hidden(Post $post): RedirectResponse
    {
        abort_unless($this->isAdminUser(), 403);

        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $post->update(['status' => 'HIDDEN']);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã ẩn bài viết.');
    }

    public function approve(Post $post): RedirectResponse
    {
        abort_unless($this->isAdminUser(), 403);

        $post->update([
            'status' => 'PUBLISHED',
            'published_at' => $post->published_at ?: now(),
        ]);

        return back()->with('success', 'Đã duyệt và đăng bài viết.');
    }

    /**
     * Lưu ảnh từ trình soạn thảo.
     */
    public function uploadEditorImage(Request $request): JsonResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $request->validate(['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096']]);

        // Luong: Tra ve du lieu JSON cho client goi API.
        return response()->json([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'url' => asset('upload/' . $this->storeUpload($request, 'upload', 'BaiViet')),
        ]);
    }

    /**
     * Xử lý categories cho bài viết.
     */
    public function categories(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $categories.
        $categories = PostCategory::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withCount([
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'posts',
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                'posts as published_posts_count' => fn ($query) => $query->where('status', 'PUBLISHED'),
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                'posts as draft_posts_count' => fn ($query) => $query->where('status', 'DRAFT'),
            ])
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('keyword'), function ($query) use ($request) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . trim((string) $request->keyword) . '%';

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where(fn ($inner) => $inner
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('name', 'like', $keyword)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('slug', 'like', $keyword));
            })
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByRaw("status = 'ACTIVE' desc")
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('name')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(12)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withQueryString();

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.posts.categories', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'categories' => $categories,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'filters' => $request->only(['status', 'keyword']),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'summary' => [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'total' => PostCategory::count(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'active' => PostCategory::where('status', 'ACTIVE')->count(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'inactive' => PostCategory::where('status', 'INACTIVE')->count(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'posts' => Post::count(),
            ],
        ]);
    }

    /**
     * Hiển thị form thêm danh mục bài viết.
     */
    public function createCategory(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm chuyên mục bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => 'Tạo chuyên mục tin tức mới',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.posts.categories.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu chuyên mục',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'classic',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->categoryFields(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.posts.categories'),
        ]);
    }

    /**
     * Lưu danh mục bài viết mới.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        PostCategory::create($this->prepareCategoryData($this->validateCategory($request)));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.posts.categories')->with('success', 'Đã thêm chuyên mục bài viết.');
    }

    /**
     * Hiển thị form sửa danh mục bài viết.
     */
    public function editCategory(PostCategory $category): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật chuyên mục bài viết',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => $category->name,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.posts.categories.update', $category),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu chuyên mục',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'classic',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->categoryFields($category),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.posts.categories'),
        ]);
    }

    /**
     * Cập nhật danh mục bài viết.
     */
    public function updateCategory(Request $request, PostCategory $category): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $category->update($this->prepareCategoryData($this->validateCategory($request)));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.posts.categories')->with('success', 'Đã cập nhật chuyên mục.');
    }

    /**
     * Ẩn danh mục bài viết.
     */
    public function hiddenCategory(PostCategory $category): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $category->update(['status' => 'INACTIVE']);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã ẩn chuyên mục.');
    }

    /**
     * Kiểm tra dữ liệu bài viết.
     */
    private function validatePost(Request $request, bool $canPublish): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:300'],
            'category_id' => ['nullable', 'exists:post_categories,id'],
            'thumbnail_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
        ];

        if ($canPublish) {
            $rules['status'] = ['required', 'in:DRAFT,PUBLISHED,HIDDEN'];
        }

        return $request->validate($rules);
    }

    /**
     * Chuẩn bị dữ liệu bài viết trước khi lưu.
     */
    private function preparePostData(Request $request, array $data, ?Post $post = null, bool $canPublish = false): array
    {
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['title']) . '-' . ($post?->id ?? time());
        $data['status'] = $canPublish ? ($data['status'] ?? 'DRAFT') : 'DRAFT';

        if ($request->hasFile('thumbnail_url')) {
            $data['thumbnail_url'] = $this->storeUpload($request, 'thumbnail_url', 'BaiViet');
        } else {
            unset($data['thumbnail_url']);
        }

        if ($data['status'] === 'PUBLISHED' && ! $post?->published_at) {
            $data['published_at'] = now();
        }

        if ($data['status'] !== 'PUBLISHED') {
            $data['published_at'] = null;
        }

        if (! $post) {
            $data['created_by'] = Auth::id();
        }

        return $data;
    }

    /**
     * Kiểm tra dữ liệu danh mục.
     */
    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);
    }

    /**
     * Chuẩn bị dữ liệu danh mục trước khi lưu.
     */
    private function prepareCategoryData(array $data): array
    {
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['name']);

        return $data;
    }

    /**
     * Lấy dữ liệu mặc định cho form bài viết.
     */
    private function postFields(?Post $post = null, bool $canPublish = false): array
    {
        $fields = [
            ['name' => 'title', 'label' => 'Tiêu đề', 'required' => true, 'value' => $post?->title],
            ['name' => 'slug', 'label' => 'Slug', 'value' => $post?->slug],
            ['name' => 'category_id', 'label' => 'Chuyên mục', 'type' => 'select', 'side' => true, 'value' => $post?->category_id, 'placeholder' => 'Không có', 'options' => PostCategory::orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'thumbnail_url', 'label' => 'Ảnh đại diện', 'type' => 'file', 'side' => true, 'preview' => $post?->image_url],
            ['name' => 'summary', 'label' => 'Tóm tắt', 'type' => 'textarea', 'column' => 'col-12', 'rows' => 3, 'value' => $post?->summary],
            ['name' => 'content', 'id' => 'short_description', 'label' => 'Nội dung', 'type' => 'textarea', 'column' => 'col-12', 'rows' => 8, 'value' => $post?->content],
        ];

        $statusField = $canPublish
            ? [
                'name' => 'status',
                'label' => 'Trạng thái',
                'type' => 'select',
                'side' => true,
                'required' => true,
                'value' => $post?->status ?? 'DRAFT',
                'options' => ['DRAFT' => 'Bản nháp / chờ duyệt', 'PUBLISHED' => 'Đã duyệt đăng', 'HIDDEN' => 'Ẩn'],
            ]
            : [
                'name' => 'status_note',
                'label' => 'Trạng thái',
                'type' => 'text',
                'side' => true,
                'value' => 'Bản nháp - chờ admin duyệt',
                'readonly' => true,
            ];

        array_splice($fields, 4, 0, [$statusField]);

        return $fields;
    }

    private function isAdminUser(): bool
    {
        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', Auth::id())
            ->where('roles.code', 'ADMIN')
            ->exists();
    }

    private function assertCanEditPost(Post $post): void
    {
        if ($this->isAdminUser()) {
            return;
        }

        abort_unless($post->created_by === Auth::id() && $post->status === 'DRAFT', 403);
    }

    private function attachContentRoleToCurrentUser(): void
    {
        $role = Role::firstOrCreate(
            ['code' => 'CONTENT'],
            [
                'name' => 'Nhân viên nội dung',
                'description' => 'Có thể tạo bài viết bản nháp để admin duyệt',
                'is_system' => true,
            ]
        );

        Auth::user()?->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Lấy dữ liệu mặc định cho form danh mục.
     */
    private function categoryFields(?PostCategory $category = null): array
    {
        return [
            ['name' => 'name', 'label' => 'Tên chuyên mục', 'required' => true, 'value' => $category?->name],
            ['name' => 'slug', 'label' => 'Slug', 'value' => $category?->slug],
            ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'required' => true, 'value' => $category?->status ?? 'ACTIVE', 'options' => ['ACTIVE' => 'Hiển thị', 'INACTIVE' => 'Tạm ẩn']],
        ];
    }

    /**
     * Lưu file upload và trả về đường dẫn.
     */
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
