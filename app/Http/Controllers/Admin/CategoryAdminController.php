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
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount('products')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'title' => 'Thêm danh mục',
            'action' => route('admin.categories.store'),
            'submitLabel' => 'Đăng',
            'backRoute' => route('admin.categories.index'),
            'category' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->prepareData($request, $this->validateCategory($request)));

        return redirect()->route('admin.categories.index')->with('success', 'Đã thêm danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'title' => 'Cập nhật danh mục',
            'action' => route('admin.categories.update', $category),
            'method' => 'PUT',
            'submitLabel' => 'Cập nhật',
            'backRoute' => route('admin.categories.index'),
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->prepareData($request, $this->validateCategory($request), $category));

        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function hidden(Category $category): RedirectResponse
    {
        $category->update(['status' => 'INACTIVE']);

        return back()->with('success', 'Đã ẩn danh mục.');
    }

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
