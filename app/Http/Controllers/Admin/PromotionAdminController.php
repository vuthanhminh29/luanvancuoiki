<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionAdminController extends Controller
{
    // Hiển thị danh sách mã giảm giá trong admin.
    /**
     * Hiển thị danh sách mã giảm giá.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $editingPromotion.
        $editingPromotion = null;
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ((int) $request->query('edit') > 0) {
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            $editingPromotion = DB::table('promotions')
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('id', (int) $request->query('edit'))
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->first();
        }

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $promotions = DB::table('promotions')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(80)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'total' => DB::table('promotions')->count(),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'active' => DB::table('promotions')->where('status', 'ACTIVE')->count(),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'scheduled' => DB::table('promotions')->where('status', 'SCHEDULED')->count(),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'expired' => DB::table('promotions')->where('status', 'EXPIRED')->count(),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.promotions.index', compact('editingPromotion', 'promotions', 'summary'));
    }

    // Điều hướng thao tác lưu hoặc bật/tắt mã giảm giá theo action trong form.
    /**
     * Chọn thao tác lưu hoặc bật tắt mã giảm giá.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $action.
        $action = (string) $request->input('_promotion_action');

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return match ($action) {
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'save' => $this->save($request),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'toggle' => $this->toggle($request),
            // Luong: Danh dau mot nhanh xu ly trong cau truc switch.
            default => back()->with('success', 'Chưa chọn thao tác khuyến mãi cần xử lý.'),
        };
    }

    // Tạo hoặc cập nhật mã giảm giá.
    /**
     * Lưu mã giảm giá.
     */
    private function save(Request $request): RedirectResponse
    {
        $id = (int) $request->input('id', 0);

        if ($id > 0 && ! DB::table('promotions')->where('id', $id)->exists()) {
            return redirect()->route('admin.promotions.index')
                ->with('success', 'Không tìm thấy khuyến mãi cần cập nhật.');
        }

        $data = $request->validate([
            'promotion_code' => ['nullable', 'string', 'max:20', Rule::unique('promotions', 'promotion_code')->ignore($id)],
            'name' => ['required', 'string', 'max:200'],
            'discount_type' => ['required', 'in:PERCENT,FIXED_AMOUNT'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_per_user' => ['nullable', 'integer', 'min:1'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'status' => ['required', 'in:SCHEDULED,ACTIVE,INACTIVE,EXPIRED'],
        ]);

        if ($data['discount_type'] === 'PERCENT' && (float) $data['discount_value'] > 100) {
            return back()
                ->withErrors(['discount_value' => 'Giá trị giảm phần trăm không được lớn hơn 100.'])
                ->withInput();
        }

        $data = array_merge($data, [
            'description' => $request->input('description'),
            'scope' => 'ORDER',
            'stackable' => $request->boolean('stackable') ? 1 : 0,
            'updated_at' => now(),
        ]);
        $data['promotion_code'] = Str::upper(trim((string) ($data['promotion_code'] ?: $this->nextCode())));
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['max_discount_amount'] = $data['max_discount_amount'] ?? null;
        $data['usage_limit'] = $data['usage_limit'] ?? null;
        $data['usage_per_user'] = $data['usage_per_user'] ?? 1;
        $data['start_at'] = Carbon::parse($data['start_at'])->format('Y-m-d H:i:s');
        $data['end_at'] = $data['end_at'] ? Carbon::parse($data['end_at'])->format('Y-m-d H:i:s') : null;

        if ($id > 0) {
            unset($data['promotion_code'], $data['scope']);

            DB::table('promotions')
                ->where('id', $id)
                ->update($data);

            return redirect()->route('admin.promotions.index')->with('success', 'Đã cập nhật khuyến mãi.');
        }

        $data['used_count'] = 0;
        $data['created_at'] = now();

        DB::table('promotions')->insert($data);

        return redirect()->route('admin.promotions.index')->with('success', 'Đã lưu khuyến mãi.');
    }

    // Bật hoặc tắt trạng thái mã giảm giá.
    /**
     * Bật hoặc tắt mã giảm giá.
     */
    private function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:promotions,id'],
        ]);

        $row = DB::table('promotions')->where('id', $data['id'])->first(['status']);
        $nextStatus = ($row?->status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';

        DB::table('promotions')
            ->where('id', $data['id'])
            ->update(['status' => $nextStatus, 'updated_at' => now()]);

        return redirect()->route('admin.promotions.index')->with('success', 'Đã cập nhật trạng thái khuyến mãi.');
    }

    // Tự sinh mã giảm giá mới nếu admin không nhập mã.
    /**
     * Tạo mã giảm giá mới.
     */
    private function nextCode(): string
    {
        do {
            $code = 'KM' . now()->format('ymdHis') . Str::upper(Str::random(2));
        } while (DB::table('promotions')->where('promotion_code', $code)->exists());

        return $code;
    }
}
