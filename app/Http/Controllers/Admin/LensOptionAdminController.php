<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LensOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LensOptionAdminController extends Controller
{
    // Nhóm hiển thị trên các tab của trang tư vấn /chon-trong-kinh.
    public const GROUP_LABELS = [
        'nhu-cau' => 'Theo nhu cầu',
        'tinh-nang' => 'Theo tính năng',
        'pho-thong' => 'Mức phổ thông',
        'cao-cap' => 'Mức cao cấp',
    ];

    /**
     * Hiển thị danh sách tùy chọn tròng kính.
     */
    public function index(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.lens-options.index', [
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'lensOptions' => LensOption::orderBy('sort_order')->orderBy('name')->paginate(20),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    /**
     * Hiển thị form thêm tùy chọn tròng kính.
     */
    public function create(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.lens-options.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Thêm tròng kính',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.lens-options.store'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Đăng',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.lens-options.index'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'lensOption' => null,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    /**
     * Lưu tùy chọn tròng kính mới.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Tao ban ghi moi tu du lieu da chuan bi.
        LensOption::create($this->prepareData($this->validateLensOption($request)));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.lens-options.index')->with('success', 'Đã thêm tròng kính.');
    }

    /**
     * Hiển thị form sửa tùy chọn tròng kính.
     */
    public function edit(LensOption $lensOption): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.lens-options.form', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Cập nhật tròng kính',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'action' => route('admin.lens-options.update', $lensOption),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'method' => 'PUT',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'submitLabel' => 'Cập nhật',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'backRoute' => route('admin.lens-options.index'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'lensOption' => $lensOption,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    /**
     * Cập nhật tùy chọn tròng kính.
     */
    public function update(Request $request, LensOption $lensOption): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $lensOption->update($this->prepareData($this->validateLensOption($request, $lensOption)));

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()->route('admin.lens-options.index')->with('success', 'Đã cập nhật tròng kính.');
    }

    /**
     * Ẩn tùy chọn tròng kính.
     */
    public function hidden(LensOption $lensOption): RedirectResponse
    {
        // Luong: Cap nhat cac ban ghi phu hop voi dieu kien da loc.
        $lensOption->update(['status' => 'INACTIVE']);

        // Luong: Quay lai trang truoc kem du lieu hoac thong bao can hien thi.
        return back()->with('success', 'Đã ẩn tròng kính khỏi trang tư vấn.');
    }

    /**
     * Kiểm tra dữ liệu tùy chọn tròng kính.
     */
    private function validateLensOption(Request $request, ?LensOption $lensOption = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/',
                'unique:lens_options,code' . ($lensOption ? ',' . $lensOption->id : ''),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'icon' => ['required', 'string', 'max:50'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => ['in:' . implode(',', array_keys(self::GROUP_LABELS))],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'sort_order' => ['nullable', 'integer'],
        ], [
            'code.regex' => 'Mã chỉ gồm chữ hoa, số và dấu gạch dưới (VD: CHONG_UV).',
        ]);
    }

    /**
     * Chuẩn bị dữ liệu tùy chọn tròng kính trước khi lưu.
     */
    private function prepareData(array $data): array
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
