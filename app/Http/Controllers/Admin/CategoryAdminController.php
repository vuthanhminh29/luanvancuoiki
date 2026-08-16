<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryAdminController extends Controller
{
    /**
     * Hiển thị danh sách danh mục.
     */
    public function index(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.categories.index', [
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'categories' => Category::withCount('products')->orderBy('name')->paginate(20),
        ]);
    }

    /**
     * Hiển thị form thêm danh mục.
     */
    public function create(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.categories.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm danh mục',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.categories.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Đăng',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.categories.index'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'category' => null,
        ]);
    }

    /**
     * Lưu danh mục mới.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Category::create($this->prepareData($request, $this->validateCategory($request)));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.categories.index')->with('success', 'Đã thêm danh mục.');
    }

    /**
     * Hiển thị form sửa danh mục.
     */
    public function edit(Category $category): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.categories.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật danh mục',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.categories.update', $category),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Cập nhật',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.categories.index'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'category' => $category,
        ]);
    }

    /**
     * Cập nhật danh mục.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $category->update($this->prepareData($request, $this->validateCategory($request), $category));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    /**
     * Ẩn danh mục.
     */
    public function hidden(Category $category): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $category->update(['status' => 'INACTIVE']);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã ẩn danh mục.');
    }

    /**
     * Kiểm tra dữ liệu danh mục.
     */
    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150'],
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);
    }

    /**
     * Chuẩn bị dữ liệu danh mục trước khi lưu.
     */
    private function prepareData(Request $request, array $data, ?Category $category = null): array
    {
        $data['slug'] = ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug($data['name']);

        if ($request->hasFile('image_url')) {
            $data['image_url'] = $this->storeUpload($request, 'image_url', 'danh_muc');
        } else {
            unset($data['image_url']);
        }

        return $data;
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
