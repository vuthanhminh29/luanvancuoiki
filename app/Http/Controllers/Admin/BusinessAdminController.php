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
    public function index(Request $request): View
    {
        $tabs = ['brands', 'promotions', 'warehouses', 'stores', 'stock'];
        $activeTab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'brands';

        $brands = Brand::query()
            ->withCount('products')
            ->latest()
            ->limit(40)
            ->get();

        $promotions = DB::table('promotions')
            ->latest('id')
            ->limit(40)
            ->get();

        $warehouses = Warehouse::query()
            ->latest()
            ->limit(40)
            ->get();

        $stores = DB::table('stores')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'stores.warehouse_id')
            ->select('stores.*', 'warehouses.name as warehouse_name')
            ->latest('stores.id')
            ->limit(40)
            ->get();

        $stockTransactions = StockTransaction::query()
            ->with(['sourceWarehouse', 'targetWarehouse'])
            ->latest('created_at')
            ->limit(40)
            ->get();

        $summary = [
            'brands' => Brand::count(),
            'promotions' => DB::table('promotions')->count(),
            'warehouses' => Warehouse::count(),
            'stores' => DB::table('stores')->count(),
            'stock' => StockTransaction::count(),
        ];

        return view('admin.business.index', compact(
            'activeTab',
            'brands',
            'promotions',
            'warehouses',
            'stores',
            'stockTransactions',
            'summary'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $action = (string) $request->input('_business_action');

        return match ($action) {
            'save_brand' => $this->saveBrand($request),
            'save_promotion' => $this->savePromotion($request),
            'save_warehouse' => $this->saveWarehouse($request),
            'toggle_brand' => $this->toggleTableStatus($request, 'brands', 'brands'),
            'toggle_promotion' => $this->toggleTableStatus($request, 'promotions', 'promotions'),
            'toggle_warehouse' => $this->toggleTableStatus($request, 'warehouses', 'warehouses'),
            default => back()->with('success', 'Chưa chọn nghiệp vụ cần xử lý.'),
        };
    }

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

    private function nextCode(string $table, string $column, string $prefix): string
    {
        do {
            $code = $prefix . now()->format('ymdHis') . Str::upper(Str::random(2));
        } while (DB::table($table)->where($column, $code)->exists());

        return $code;
    }

    private function redirectTab(string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.business.index', ['tab' => $tab])
            ->with('success', $message);
    }
}
