<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseAdminController extends Controller
{
    public function index(Request $request): View
    {
        $inventoryFilters = $request->only([
            'inventory_keyword',
            'inventory_warehouse_id',
            'inventory_category_id',
            'inventory_stock_state',
            'inventory_limit',
        ]);
        $stockFilters = $request->only([
            'stock_keyword',
            'stock_type',
            'stock_warehouse_id',
            'stock_status',
            'stock_date_from',
            'stock_date_to',
            'stock_limit',
        ]);

        $inventoryLimit = min(500, max(25, (int) ($request->input('inventory_limit', 200))));
        $stockLimit = min(300, max(25, (int) ($request->input('stock_limit', 100))));

        $inventories = Inventory::query()
            ->with(['warehouse', 'variant.product.category', 'variant.color', 'variant.lensSize'])
            ->when($request->filled('inventory_warehouse_id'), fn ($query) => $query->where('warehouse_id', $request->inventory_warehouse_id))
            ->when($request->filled('inventory_category_id'), fn ($query) => $query->whereHas('variant.product', fn ($product) => $product->where('category_id', $request->inventory_category_id)))
            ->when($request->filled('inventory_keyword'), function ($query) use ($request) {
                $keyword = '%' . trim((string) $request->inventory_keyword) . '%';

                $query->whereHas('variant', fn ($variant) => $variant
                    ->where('sku', 'like', $keyword)
                    ->orWhereHas('product', fn ($product) => $product
                        ->where('name', 'like', $keyword)
                        ->orWhere('product_code', 'like', $keyword))
                    ->orWhereHas('color', fn ($color) => $color->where('name', 'like', $keyword))
                    ->orWhereHas('lensSize', fn ($size) => $size->where('name', 'like', $keyword)));
            })
            ->when($request->filled('inventory_stock_state'), function ($query) use ($request) {
                match ($request->inventory_stock_state) {
                    'OUT' => $query->whereRaw('(quantity - reserved_quantity) <= 0'),
                    'LOW' => $query->whereRaw('(quantity - reserved_quantity) > 0')->whereRaw('(quantity - reserved_quantity) <= COALESCE(min_stock_level, 10)'),
                    'OK' => $query->whereRaw('(quantity - reserved_quantity) > COALESCE(min_stock_level, 10)'),
                    default => null,
                };
            })
            ->orderByRaw('(quantity - reserved_quantity) asc')
            ->orderByDesc('updated_at')
            ->limit($inventoryLimit)
            ->get();

        $transactions = StockTransaction::query()
            ->with(['sourceWarehouse', 'targetWarehouse'])
            ->withCount('items')
            ->when($request->filled('stock_type'), fn ($query) => $query->where('type', $request->stock_type))
            ->when($request->filled('stock_status'), fn ($query) => $query->where('status', $request->stock_status))
            ->when($request->filled('stock_warehouse_id'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('source_warehouse_id', $request->stock_warehouse_id)
                ->orWhere('target_warehouse_id', $request->stock_warehouse_id)))
            ->when($request->filled('stock_date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->stock_date_from))
            ->when($request->filled('stock_date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->stock_date_to))
            ->when($request->filled('stock_keyword'), function ($query) use ($request) {
                $keyword = '%' . trim((string) $request->stock_keyword) . '%';

                $query->where(fn ($inner) => $inner
                    ->where('transaction_code', 'like', $keyword)
                    ->orWhere('note', 'like', $keyword));
            })
            ->latest()
            ->limit($stockLimit)
            ->get();

        $summary = Inventory::query()
            ->selectRaw('COUNT(DISTINCT warehouse_id) as warehouse_count')
            ->selectRaw('COUNT(DISTINCT variant_id) as variant_count')
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_stock')
            ->selectRaw('COALESCE(SUM(reserved_quantity), 0) as reserved_stock')
            ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) as available_stock')
            ->selectRaw('SUM(CASE WHEN (quantity - reserved_quantity) <= COALESCE(min_stock_level, 10) THEN 1 ELSE 0 END) as low_stock_rows')
            ->first();

        $activeTab = $request->input('warehouse_tab', 'stock');
        if (collect($request->query())->keys()->contains(fn ($key) => str_starts_with((string) $key, 'stock_'))) {
            $activeTab = 'transactions';
        }
        if (! in_array($activeTab, ['stock', 'warehouses', 'transactions'], true)) {
            $activeTab = 'stock';
        }

        return view('admin.warehouses.index', [
            'warehouses' => Warehouse::withCount('inventories')->orderByRaw("status = 'ACTIVE' desc")->orderBy('name')->get(),
            'inventories' => $inventories,
            'transactions' => $transactions,
            'categories' => Category::orderBy('name')->get(),
            'summary' => $summary,
            'activeTab' => $activeTab,
            'inventoryFilters' => $inventoryFilters,
            'stockFilters' => $stockFilters,
            'inventoryLimit' => $inventoryLimit,
            'stockLimit' => $stockLimit,
            'transactionItemTotals' => DB::table('stock_transaction_items')
                ->select('stock_transaction_id')
                ->selectRaw('COALESCE(SUM(ordered_quantity), 0) as ordered_quantity')
                ->selectRaw('COALESCE(SUM(actual_quantity), 0) as actual_quantity')
                ->groupBy('stock_transaction_id')
                ->get()
                ->keyBy('stock_transaction_id'),
        ]);
    }

    public function transactions(): View
    {
        return view('admin.shared.table', [
            'title' => 'Danh sách kho',
            'subtitle' => 'Phiếu nhập/xuất/chuyển kho',
            'headers' => ['Mã phiếu', 'Loại', 'Kho nguồn', 'Kho đích', 'Trạng thái'],
            'createRoute' => route('admin.warehouses.create-transaction'),
            'rows' => StockTransaction::with(['sourceWarehouse', 'targetWarehouse'])
                ->latest()
                ->paginate(20)
                ->through(fn ($transaction) => [
                    $transaction->transaction_code,
                    $transaction->type,
                    $transaction->sourceWarehouse->name ?? '-',
                    $transaction->targetWarehouse->name ?? '-',
                    $transaction->status,
                ]),
        ]);
    }

    public function createTransaction(): View
    {
        return view('admin.shared.form', [
            'title' => 'Thêm hóa đơn kho',
            'subtitle' => 'Tạo phiếu nhập/xuất/chuyển kho',
            'action' => route('admin.warehouses.store-transaction'),
            'submitLabel' => 'Lưu phiếu kho',
            'formStyle' => 'wa',
            'fields' => $this->transactionFields(),
            'backRoute' => route('admin.warehouses.transactions'),
        ]);
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'transaction_code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:IMPORT,EXPORT,TRANSFER,ADJUST,RETURN_IN,SALE_OUT'],
            'source_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'target_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'expected_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:DRAFT,PENDING,COMPLETED,CANCELLED'],
        ]);

        $data['transaction_code'] = ($data['transaction_code'] ?? '') !== '' ? $data['transaction_code'] : 'STK' . now()->format('YmdHis');
        $data['created_by'] = Auth::id();
        $data['confirmed_by'] = $data['status'] === 'COMPLETED' ? Auth::id() : null;
        $data['confirmed_at'] = $data['status'] === 'COMPLETED' ? now() : null;

        StockTransaction::create($data);

        return redirect()->route('admin.warehouses.transactions')->with('success', 'Đã thêm phiếu kho.');
    }

    private function transactionFields(): array
    {
        $warehouses = Warehouse::orderBy('name')->pluck('name', 'id')->all();

        return [
            ['name' => 'transaction_code', 'label' => 'Mã phiếu', 'value' => 'STK' . now()->format('YmdHis')],
            ['name' => 'type', 'label' => 'Loại phiếu', 'type' => 'select', 'required' => true, 'value' => 'IMPORT', 'options' => [
                'IMPORT' => 'Nhập kho',
                'EXPORT' => 'Xuất kho',
                'TRANSFER' => 'Chuyển kho',
                'ADJUST' => 'Điều chỉnh',
                'RETURN_IN' => 'Nhập hoàn',
                'SALE_OUT' => 'Xuất bán',
            ]],
            ['name' => 'source_warehouse_id', 'label' => 'Kho nguồn', 'type' => 'select', 'placeholder' => 'Không có', 'options' => $warehouses],
            ['name' => 'target_warehouse_id', 'label' => 'Kho đích', 'type' => 'select', 'placeholder' => 'Không có', 'options' => $warehouses],
            ['name' => 'expected_date', 'label' => 'Ngày dự kiến', 'type' => 'date'],
            ['name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'required' => true, 'value' => 'DRAFT', 'options' => ['DRAFT' => 'Bản nháp', 'PENDING' => 'Chờ xử lý', 'COMPLETED' => 'Hoàn tất', 'CANCELLED' => 'Đã hủy']],
            ['name' => 'note', 'label' => 'Ghi chú', 'type' => 'textarea', 'column' => 'col-12'],
        ];
    }
}
