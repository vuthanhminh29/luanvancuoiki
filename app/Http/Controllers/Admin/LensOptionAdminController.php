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

    public function index(): View
    {
        return view('admin.lens-options.index', [
            'lensOptions' => LensOption::orderBy('sort_order')->orderBy('name')->paginate(20),
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('admin.lens-options.form', [
            'title' => 'Thêm tròng kính',
            'action' => route('admin.lens-options.store'),
            'submitLabel' => 'Đăng',
            'backRoute' => route('admin.lens-options.index'),
            'lensOption' => null,
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LensOption::create($this->prepareData($this->validateLensOption($request)));

        return redirect()->route('admin.lens-options.index')->with('success', 'Đã thêm tròng kính.');
    }

    public function edit(LensOption $lensOption): View
    {
        return view('admin.lens-options.form', [
            'title' => 'Cập nhật tròng kính',
            'action' => route('admin.lens-options.update', $lensOption),
            'method' => 'PUT',
            'submitLabel' => 'Cập nhật',
            'backRoute' => route('admin.lens-options.index'),
            'lensOption' => $lensOption,
            'groupLabels' => self::GROUP_LABELS,
        ]);
    }

    public function update(Request $request, LensOption $lensOption): RedirectResponse
    {
        $lensOption->update($this->prepareData($this->validateLensOption($request, $lensOption)));

        return redirect()->route('admin.lens-options.index')->with('success', 'Đã cập nhật tròng kính.');
    }

    public function hidden(LensOption $lensOption): RedirectResponse
    {
        $lensOption->update(['status' => 'INACTIVE']);

        return back()->with('success', 'Đã ẩn tròng kính khỏi trang tư vấn.');
    }

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

    private function prepareData(array $data): array
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
