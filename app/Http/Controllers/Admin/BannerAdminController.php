<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BannerAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status'));
        $position = trim((string) $request->query('position'));
        $platform = trim((string) $request->query('platform'));
        $keyword = trim((string) $request->query('keyword'));

        $query = Banner::query()->latest('id');

        if (in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $query->where('status', $status);
        }

        if (in_array($position, ['HOME_SLIDER', 'HOME_BANNER_1', 'HOME_BANNER_2', 'CATEGORY_BANNER', 'PRODUCT_BANNER'], true)) {
            $query->where('position', $position);
        }

        if (in_array($platform, ['DESKTOP', 'MOBILE', 'BOTH'], true)) {
            $query->where('platform', $platform);
        }

        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('title', 'like', "%{$keyword}%")
                    ->orWhere('link_url', 'like', "%{$keyword}%")
                    ->orWhere('image_url', 'like', "%{$keyword}%");
            });
        }

        $banners = $query->paginate(12)->withQueryString();
        $summary = [
            'total' => Banner::count(),
            'active' => Banner::where('status', 'ACTIVE')->count(),
            'inactive' => Banner::where('status', 'INACTIVE')->count(),
            'home' => Banner::where('position', 'HOME_SLIDER')->count(),
        ];

        return view('admin.banners.index', compact('banners', 'summary', 'status', 'position', 'platform', 'keyword'));
    }

    public function create(): View
    {
        return view('admin.shared.form', [
            'title' => 'Thêm banner',
            'subtitle' => 'Tạo banner mới cho trang chủ hoặc khu vực quảng cáo',
            'action' => route('admin.banners.store'),
            'submitLabel' => 'Lưu banner',
            'formStyle' => 'banner',
            'fields' => $this->bannerFields(),
            'backRoute' => route('admin.banners.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Banner::create($this->prepareData($request, $this->validateBanner($request)));

        return redirect()->route('admin.banners.index')->with('success', 'Đã thêm banner.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.shared.form', [
            'title' => 'Cập nhật banner',
            'subtitle' => $banner->title,
            'action' => route('admin.banners.update', $banner),
            'method' => 'PUT',
            'submitLabel' => 'Lưu banner',
            'formStyle' => 'banner',
            'fields' => $this->bannerFields($banner),
            'backRoute' => route('admin.banners.index'),
        ]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->prepareData($request, $this->validateBanner($request, $banner), $banner));

        return redirect()->route('admin.banners.index')->with('success', 'Đã cập nhật banner.');
    }

    public function hidden(Banner $banner): RedirectResponse
    {
        $banner->update(['status' => 'INACTIVE']);

        return back()->with('success', 'Đã ẩn banner.');
    }

    private function validateBanner(Request $request, ?Banner $banner = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'image_url' => [$banner ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'platform' => ['required', 'in:DESKTOP,MOBILE,BOTH'],
            'position' => ['required', 'in:HOME_SLIDER,HOME_BANNER_1,HOME_BANNER_2,CATEGORY_BANNER,PRODUCT_BANNER'],
            'priority' => ['required', 'integer', 'min:1'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);
    }

    private function prepareData(Request $request, array $data, ?Banner $banner = null): array
    {
        if ($request->hasFile('image_url')) {
            $data['image_url'] = $this->storeUpload($request, 'image_url', 'banner');
        } else {
            unset($data['image_url']);
        }

        return $data;
    }

    private function bannerFields(?Banner $banner = null): array
    {
        return [
            ['name' => 'title', 'label' => 'Tiêu đề', 'required' => true, 'value' => $banner?->title],
            ['name' => 'image_url', 'label' => 'Ảnh banner', 'type' => 'file', 'side' => true, 'preview' => $banner?->image_src],
            ['name' => 'link_url', 'label' => 'Đường dẫn', 'value' => $banner?->link_url],
            ['name' => 'position', 'label' => 'Vị trí', 'type' => 'select', 'required' => true, 'value' => $banner?->position ?? 'HOME_SLIDER', 'options' => [
                'HOME_SLIDER' => 'Slider trang chủ',
                'HOME_BANNER_1' => 'Banner trang chủ 1',
                'HOME_BANNER_2' => 'Banner trang chủ 2',
                'CATEGORY_BANNER' => 'Banner danh mục',
                'PRODUCT_BANNER' => 'Banner sản phẩm',
            ]],
            ['name' => 'platform', 'label' => 'Nền tảng', 'type' => 'select', 'required' => true, 'value' => $banner?->platform ?? 'BOTH', 'options' => ['DESKTOP' => 'Desktop', 'MOBILE' => 'Mobile', 'BOTH' => 'Cả hai']],
            ['name' => 'priority', 'label' => 'Ưu tiên', 'type' => 'number', 'required' => true, 'value' => $banner?->priority ?? 1],
            ['name' => 'start_at', 'label' => 'Ngày bắt đầu', 'type' => 'datetime-local', 'required' => true, 'value' => ($banner?->start_at ?? now())->format('Y-m-d\TH:i')],
            ['name' => 'end_at', 'label' => 'Ngày kết thúc', 'type' => 'datetime-local', 'value' => $banner?->end_at?->format('Y-m-d\TH:i')],
            ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'side' => true, 'required' => true, 'value' => $banner?->status ?? 'ACTIVE', 'options' => ['ACTIVE' => 'Hiển thị', 'INACTIVE' => 'Tạm ẩn']],
        ];
    }

    private function storeUpload(Request $request, string $field, string $folder): string
    {
        $file = $request->file($field);
        $name = (string) Str::uuid() . '.' . $file->extension();

        return Storage::disk('public')->putFileAs('upload/' . $folder, $file, $name);
    }
}
