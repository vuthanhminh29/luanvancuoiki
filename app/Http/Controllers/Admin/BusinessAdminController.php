<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BusinessAdminController extends Controller
{
    // Hiển thị màn hình nghiệp vụ: thương hiệu, kho và các dữ liệu cấu hình.
    /**
     * Hiển thị danh sách thiết lập kinh doanh.
     */
    public function index(Request $request): RedirectResponse|View
    {
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($request->query('tab') === 'promotions') {
            // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
            return redirect()->route('admin.promotions.index');
        }

        // Luong: Gan ket qua xu ly vao bien $tabs.
        $tabs = ['brands', 'warehouses', 'stores', 'stock'];
        // Luong: Gan ket qua xu ly vao bien $activeTab.
        $activeTab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'brands';

        // Luong: Gan ket qua xu ly vao bien $brands.
        $brands = Brand::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withCount('products')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(40)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $warehouses.
        $warehouses = Warehouse::query()
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(40)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $stores = DB::table('stores')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('warehouses', 'warehouses.id', '=', 'stores.warehouse_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select('stores.*', 'warehouses.name as warehouse_name')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('stores.id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(40)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $promotions = DB::table('promotions')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('created_at')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(40)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $stockTransactions.
        $stockTransactions = StockTransaction::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['sourceWarehouse', 'targetWarehouse'])
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest('created_at')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit(40)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'brands' => Brand::count(),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'promotions' => DB::table('promotions')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'warehouses' => Warehouse::count(),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'stores' => DB::table('stores')->count(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'stock' => StockTransaction::count(),
        ];

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.business.index', compact(
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'activeTab',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'brands',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'warehouses',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stores',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'promotions',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stockTransactions',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'summary'
        ));
    }

    /**
     * Lưu thiết lập kinh doanh mới.
     */
    public function store(Request $request): RedirectResponse
    {
        // Luong: Gan ket qua xu ly vao bien $action.
        $action = (string) $request->input('_business_action');

        // Luong: Tra ve ket qua cuoi cung cua ham.
        return match ($action) {
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'save_brand' => $this->saveBrand($request),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'save_promotion' => $this->savePromotion($request),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'save_warehouse' => $this->saveWarehouse($request),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'toggle_brand' => $this->toggleTableStatus($request, 'brands', 'brands'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'toggle_promotion' => $this->toggleTableStatus($request, 'promotions', 'promotions'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'toggle_warehouse' => $this->toggleTableStatus($request, 'warehouses', 'warehouses'),
            // Luong: Danh dau mot nhanh xu ly trong cau truc switch.
            default => back()->with('success', 'Chưa chọn nghiệp vụ cần xử lý.'),
        };
    }

    /**
     * Lưu thông tin thương hiệu.
     */
    private function saveBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['status'] = 'ACTIVE';
        Brand::create($data);

        return $this->redirectTab('brands', 'Đã lưu thương hiệu.');
    }

    /**
     * Lưu thông tin khuyến mãi.
     */
    private function savePromotion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'promotion_code' => ['nullable', 'string', 'max:20', 'unique:promotions,promotion_code'],
            'name' => ['required', 'string', 'max:200'],
            'discount_type' => ['required', 'in:PERCENT,FIXED_AMOUNT'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'status' => ['required', 'in:SCHEDULED,ACTIVE,INACTIVE,EXPIRED'],
        ]);

        $data += [
            'description' => $request->input('description'),
            'scope' => 'ORDER',
            'used_count' => 0,
            'stackable' => $request->boolean('stackable') ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $data['promotion_code'] = Str::upper(trim((string) ($data['promotion_code'] ?: $this->nextCode('promotions', 'promotion_code', 'KM'))));
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['max_discount_amount'] = $data['max_discount_amount'] ?? null;
        $data['usage_limit'] = $data['usage_limit'] ?? null;
        $data['start_at'] = Carbon::parse($data['start_at'])->format('Y-m-d H:i:s');
        $data['end_at'] = $data['end_at'] ? Carbon::parse($data['end_at'])->format('Y-m-d H:i:s') : null;

        DB::table('promotions')->insert($data);

        return $this->redirectTab('promotions', 'Đã lưu khuyến mãi.');
    }

    /**
     * Lưu thông tin kho.
     */
    private function saveWarehouse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200', 'unique:warehouses,name'],
            'type' => ['required', 'in:NORMAL,RETURN,WARRANTY,STORE'],
            'capacity' => ['required', 'integer', 'min:1'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'address_detail' => ['nullable', 'string', 'max:500'],
        ]);

        $data['warehouse_code'] = $this->nextCode('warehouses', 'warehouse_code', 'KHO');
        $data['min_stock_level'] = $data['min_stock_level'] ?? 10;
        $data['status'] = 'ACTIVE';

        Warehouse::create($data);

        return $this->redirectTab('warehouses', 'Đã lưu kho.');
    }

    // Bật/tắt trạng thái dữ liệu cấu hình trong một bảng cụ thể.
    /**
     * Bật hoặc tắt trạng thái bản ghi.
     */
    private function toggleTableStatus(Request $request, string $table, string $tab): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', "exists:{$table},id"],
        ]);

        $row = DB::table($table)->where('id', $data['id'])->first(['status']);
        $nextStatus = ($row?->status === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';

        DB::table($table)
            ->where('id', $data['id'])
            ->update(['status' => $nextStatus, 'updated_at' => now()]);

        return $this->redirectTab($tab, 'Đã cập nhật trạng thái.');
    }

    // Tự sinh mã mới theo prefix và số lớn nhất hiện tại trong bảng.
    /**
     * Tạo mã mới.
     */
    private function nextCode(string $table, string $column, string $prefix): string
    {
        do {
            $code = $prefix . now()->format('ymdHis') . Str::upper(Str::random(2));
        } while (DB::table($table)->where($column, $code)->exists());

        return $code;
    }

    // Redirect về đúng tab nghiệp vụ sau khi lưu.
    /**
     * Quay lại tab vừa thao tác.
     */
    private function redirectTab(string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.business.index', ['tab' => $tab])
            ->with('success', $message);
    }
}
