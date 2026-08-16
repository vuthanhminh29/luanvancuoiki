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
    /**
     * Hiển thị danh sách banner.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $status.
        $status = trim((string) $request->query('status'));
        // Luong: Gan ket qua xu ly vao bien $position.
        $position = trim((string) $request->query('position'));
        // Luong: Gan ket qua xu ly vao bien $platform.
        $platform = trim((string) $request->query('platform'));
        // Luong: Gan ket qua xu ly vao bien $keyword.
        $keyword = trim((string) $request->query('keyword'));

        // Luong: Sap xep du lieu truoc khi tra ve ket qua.
        $query = Banner::query()->latest('id');

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $query->where('status', $status);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (in_array($position, ['HOME_SLIDER', 'HOME_BANNER_1', 'HOME_BANNER_2', 'CATEGORY_BANNER', 'PRODUCT_BANNER'], true)) {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $query->where('position', $position);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (in_array($platform, ['DESKTOP', 'MOBILE', 'BOTH'], true)) {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $query->where('platform', $platform);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($keyword !== '') {
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            $query->where(function ($subQuery) use ($keyword) {
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $subQuery->where('title', 'like', "%{$keyword}%")
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('link_url', 'like', "%{$keyword}%")
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('image_url', 'like', "%{$keyword}%");
            });
        }

        // Luong: Thuc thi truy van va lay ket qua tu CSDL.
        $banners = $query->paginate(12)->withQueryString();
        // Luong: Gan ket qua xu ly vao bien $sliderPreview.
        $sliderPreview = Banner::query()
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('position', 'HOME_SLIDER')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('status', 'ACTIVE')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('priority')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByDesc('id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(5)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();
        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'total' => Banner::count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'active' => Banner::where('status', 'ACTIVE')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'inactive' => Banner::where('status', 'INACTIVE')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'home' => Banner::where('position', 'HOME_SLIDER')->count(),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.banners.index', compact('banners', 'sliderPreview', 'summary', 'status', 'position', 'platform', 'keyword'));
    }

    /**
     * Hiển thị form thêm banner.
     */
    public function create(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm banner',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => 'Tạo banner mới cho trang chủ hoặc khu vực quảng cáo',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.banners.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu banner',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'banner',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->bannerFields(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.banners.index'),
        ]);
    }

    /**
     * Lưu banner mới.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        Banner::create($this->prepareData($request, $this->validateBanner($request)));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.banners.index')->with('success', 'Đã thêm banner.');
    }

    /**
     * Hiển thị form sửa banner.
     */
    public function edit(Banner $banner): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật banner',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => $banner->title,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.banners.update', $banner),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Lưu banner',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'formStyle' => 'banner',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'fields' => $this->bannerFields($banner),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.banners.index'),
        ]);
    }

    /**
     * Cập nhật banner.
     */
    public function update(Request $request, Banner $banner): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $banner->update($this->prepareData($request, $this->validateBanner($request, $banner), $banner));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.banners.index')->with('success', 'Đã cập nhật banner.');
    }

    /**
     * Ẩn banner.
     */
    public function hidden(Banner $banner): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $banner->update(['status' => 'INACTIVE']);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã ẩn banner.');
    }

    /**
     * Kiểm tra dữ liệu banner.
     */
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

    /**
     * Chuẩn bị dữ liệu banner trước khi lưu.
     */
    private function prepareData(Request $request, array $data, ?Banner $banner = null): array
    {
        if ($request->hasFile('image_url')) {
            $data['image_url'] = $this->storeUpload($request, 'image_url', 'banner');
        } else {
            unset($data['image_url']);
        }

        return $data;
    }

    /**
     * Lấy dữ liệu mặc định cho form banner.
     */
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

    /**
     * Lưu file upload và trả về đường dẫn.
     */
    private function storeUpload(Request $request, string $field, string $folder): string
    {
        $file = $request->file($field);
        $name = (string) Str::uuid() . '.' . $file->extension();

        return Storage::disk('public')->putFileAs('upload/' . $folder, $file, $name);
    }
}
