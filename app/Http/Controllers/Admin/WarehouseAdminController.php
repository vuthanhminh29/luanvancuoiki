<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarehouseAdminController extends Controller
{
    /**
     * Hiển thị danh sách kho và tồn kho.
     */
    public function index(Request $request): View
    {
        // Luong: Gan ket qua xu ly vao bien $inventoryFilters.
        $inventoryFilters = $request->only([
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'inventory_keyword',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'inventory_warehouse_id',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'inventory_category_id',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'inventory_stock_state',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'inventory_limit',
        ]);
        // Luong: Gan ket qua xu ly vao bien $stockFilters.
        $stockFilters = $request->only([
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stock_keyword',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stock_type',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stock_warehouse_id',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stock_status',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stock_date_from',
            // Luong: Xu ly dong logic tiep theo trong ham public nay.
            'stock_date_to',
        ]);

        // Luong: Gan ket qua xu ly vao bien $inventoryLimit.
        $inventoryLimit = min(500, max(25, (int) ($request->input('inventory_limit', 200))));
        // Luong: Gan ket qua xu ly vao bien $inventories.
        $inventories = Inventory::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['warehouse', 'variant.product.category', 'variant.product.categories', 'variant.color', 'variant.lensSize'])
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('inventory_warehouse_id'), fn ($query) => $query->where('warehouse_id', $request->inventory_warehouse_id))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('inventory_category_id'), fn ($query) => $query->whereHas('variant.product', fn ($product) => $product->inCategories([(int) $request->inventory_category_id])))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('inventory_keyword'), function ($query) use ($request) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . trim((string) $request->inventory_keyword) . '%';

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->whereHas('variant', fn ($variant) => $variant
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('sku', 'like', $keyword)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhereHas('product', fn ($product) => $product
                        // Luong: Bo sung dieu kien loc du lieu cho truy van.
                        ->where('name', 'like', $keyword)
                        // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                        ->orWhere('product_code', 'like', $keyword))
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->orWhereHas('color', fn ($color) => $color->where('name', 'like', $keyword))
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->orWhereHas('lensSize', fn ($size) => $size->where('name', 'like', $keyword)));
            })
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('inventory_stock_state'), function ($query) use ($request) {
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                match ($request->inventory_stock_state) {
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    'OUT' => $query->whereRaw('quantity <= 0'),
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    'LOW' => $query->whereRaw('quantity > 0')->whereRaw('quantity <= COALESCE(min_stock_level, 10)'),
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    'OK' => $query->whereRaw('quantity > COALESCE(min_stock_level, 10)'),
                    // Luong: Danh dau mot nhanh xu ly trong cau truc switch.
                    default => null,
                };
            })
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('quantity')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderByDesc('updated_at')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->limit($inventoryLimit)
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get();

        // Luong: Gan ket qua xu ly vao bien $transactions.
        $transactions = StockTransaction::query()
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with(['sourceWarehouse', 'targetWarehouse'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withCount('items')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('stock_type'), fn ($query) => $query->where('type', $request->stock_type))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('stock_status'), fn ($query) => $query->where('status', $request->stock_status))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('stock_warehouse_id'), fn ($query) => $query->where(fn ($inner) => $inner
                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                ->where('source_warehouse_id', $request->stock_warehouse_id)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->orWhere('target_warehouse_id', $request->stock_warehouse_id)))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('stock_date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->stock_date_from))
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->when($request->filled('stock_date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->stock_date_to))
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->when($request->filled('stock_keyword'), function ($query) use ($request) {
                // Luong: Gan ket qua xu ly vao bien $keyword.
                $keyword = '%' . trim((string) $request->stock_keyword) . '%';

                // Luong: Bo sung dieu kien loc du lieu cho truy van.
                $query->where(fn ($inner) => $inner
                    // Luong: Bo sung dieu kien loc du lieu cho truy van.
                    ->where('transaction_code', 'like', $keyword)
                    // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                    ->orWhere('note', 'like', $keyword));
            })
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->latest()
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->paginate(15, ['*'], 'stock_page')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->withQueryString();

        $returnCodesByTransactionId = $transactions
            ->mapWithKeys(function ($transaction) {
                preg_match('/RTN[0-9A-Z]+/i', (string) $transaction->note, $matches);

                return $matches ? [$transaction->id => strtoupper($matches[0])] : [];
            });

        $returnReasonsByCode = $returnCodesByTransactionId->isEmpty()
            ? collect()
            : DB::table('return_requests as rr')
                ->leftJoin('return_reasons as reason', 'reason.id', '=', 'rr.reason_id')
                ->whereIn('rr.return_code', $returnCodesByTransactionId->values()->unique()->all())
                ->select([
                    'rr.return_code',
                    'rr.type',
                    'rr.reason_detail',
                    'rr.status',
                    'reason.code as reason_code',
                    'reason.name as reason_name',
                ])
                ->get()
                ->keyBy('return_code');

        $returnReasonByTransaction = $returnCodesByTransactionId
            ->mapWithKeys(fn ($returnCode, $transactionId) => [
                $transactionId => $returnReasonsByCode->get($returnCode),
            ])
            ->filter();

        // Luong: Gan ket qua xu ly vao bien $summary.
        $summary = Inventory::query()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COUNT(DISTINCT warehouse_id) as warehouse_count')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COUNT(DISTINCT variant_id) as variant_count')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_stock')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(SUM(quantity), 0) as available_stock')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('SUM(CASE WHEN quantity <= COALESCE(min_stock_level, 10) THEN 1 ELSE 0 END) as low_stock_rows')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->first();

        // Luong: Gan ket qua xu ly vao bien $activeTab.
        $activeTab = $request->input('warehouse_tab', 'stock');
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (collect($request->query())->keys()->contains(fn ($key) => str_starts_with((string) $key, 'stock_'))) {
            // Luong: Gan ket qua xu ly vao bien $activeTab.
            $activeTab = 'transactions';
        }
        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if (! in_array($activeTab, ['stock', 'warehouses', 'transactions'], true)) {
            // Luong: Gan ket qua xu ly vao bien $activeTab.
            $activeTab = 'stock';
        }

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.warehouses.index', [
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'warehouses' => Warehouse::withCount('inventories')
                ->withSum('inventories as stock_quantity', 'quantity')
                ->orderByRaw("status = 'ACTIVE' desc")
                ->orderBy('name')
                ->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'inventories' => $inventories,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'transactions' => $transactions,
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            'categories' => Category::orderBy('name')->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'summary' => $summary,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'activeTab' => $activeTab,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'inventoryFilters' => $inventoryFilters,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'stockFilters' => $stockFilters,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'inventoryLimit' => $inventoryLimit,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'returnReasonByTransaction' => $returnReasonByTransaction,
            'isAdminUser' => $this->currentUserHasRole('ADMIN'),
            // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
            'transactionItemTotals' => DB::table('stock_transaction_items')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->select('stock_transaction_id')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->selectRaw('COALESCE(SUM(ordered_quantity), 0) as ordered_quantity')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->selectRaw('COALESCE(SUM(actual_quantity), 0) as actual_quantity')
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->groupBy('stock_transaction_id')
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->get()
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->keyBy('stock_transaction_id'),
        ]);
    }

    /**
     * Hiển thị lịch sử giao dịch kho.
     */
    public function transactions(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.shared.table', [
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'title' => 'Danh sách kho',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'subtitle' => 'Phiếu nhập/xuất kho',
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'headers' => ['Mã phiếu', 'Loại', 'Kho nguồn', 'Kho đích', 'Trạng thái'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'createRoute' => route('admin.warehouses.create-transaction'),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'rows' => StockTransaction::with(['sourceWarehouse', 'targetWarehouse'])
                // Luong: Sap xep du lieu truoc khi tra ve ket qua.
                ->latest()
                // Luong: Thuc thi truy van va lay ket qua tu CSDL.
                ->paginate(20)
                // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
                ->through(fn ($transaction) => [
                    // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                    $transaction->transaction_code,
                    // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                    $transaction->type,
                    // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                    $transaction->sourceWarehouse->name ?? '-',
                    // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                    $transaction->targetWarehouse->name ?? '-',
                    // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                    $transaction->status,
                ]),
        ]);
    }

    /**
     * Hiển thị chi tiết phiếu kho ở chế độ chỉ xem.
     */
    public function showTransaction(StockTransaction $transaction): View
    {
        $transaction->load([
            'sourceWarehouse',
            'targetWarehouse',
            'creator',
            'confirmer',
            'items.variant.product',
            'items.variant.color',
            'items.variant.lensSize',
        ]);

        $variantIds = $transaction->items
            ->pluck('variant_id')
            ->filter()
            ->unique()
            ->values();

        $warehouseId = $transaction->type === 'EXPORT'
            ? $transaction->source_warehouse_id
            : $transaction->target_warehouse_id;

        $availableStockByVariantId = $variantIds->isEmpty()
            ? collect()
            : Inventory::query()
                ->whereIn('variant_id', $variantIds)
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
                ->select('variant_id')
                ->selectRaw('COALESCE(SUM(quantity), 0) as quantity')
                ->groupBy('variant_id')
                ->pluck('quantity', 'variant_id');

        return view('admin.warehouses.transaction-show', [
            'transaction' => $transaction,
            'availableStockByVariantId' => $availableStockByVariantId,
            'isAdminUser' => $this->currentUserHasRole('ADMIN'),
        ]);
    }

    /**
     * Hiển thị form tạo giao dịch kho.
     */
    public function createTransaction(): View
    {
        // Luong: Tao truy van truc tiep den bang du lieu can thao tac.
        $variants = DB::table('product_variants as pv')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('colors as c', 'c.id', '=', 'pv.color_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('lens_sizes as ls', 'ls.id', '=', 'pv.lens_size_id')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->leftJoin('inventories as i', 'i.variant_id', '=', 'pv.id')
            // Luong: Bo sung dieu kien loc du lieu cho truy van.
            ->where('p.status', '<>', 'DISCONTINUED')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->select([
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.id as variant_id',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.sku',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.product_code',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.name as product_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.thumbnail_url',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.import_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.base_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.sale_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.variant_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'c.name as color_name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'c.hex_code as color_hex',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'ls.name as lens_size_name',
            ])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->selectRaw('COALESCE(SUM(i.quantity), 0) as available_stock')
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->groupBy(
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.id',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.sku',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.product_code',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.thumbnail_url',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.import_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.base_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'p.sale_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'pv.variant_price',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'c.name',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'c.hex_code',
                // Luong: Xu ly dong logic tiep theo trong ham public nay.
                'ls.name'
            )
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('p.name')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('c.name')
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            ->orderBy('ls.name')
            // Luong: Thuc thi truy van va lay ket qua tu CSDL.
            ->get()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->map(fn ($variant) => [
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'id' => (int) $variant->variant_id,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'label' => trim((string) $variant->product_name),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'meta' => trim(($variant->product_code ?? '') . ' | ' . ($variant->color_name ?: '-') . ' | Size ' . ($variant->lens_size_name ?: '-')),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'image' => $this->productImageUrl($variant->thumbnail_url),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'stock' => (int) $variant->available_stock,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'price' => (float) ($variant->import_price ?: $variant->variant_price ?: $variant->sale_price ?: $variant->base_price ?: 0),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'salePrice' => (float) ($variant->sale_price ?: $variant->variant_price ?: $variant->base_price ?: 0),
            ]);

        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('admin.warehouses.transaction-form', [
            // Luong: Sap xep du lieu truoc khi tra ve ket qua.
            'warehouses' => Warehouse::active()->orderBy('type')->orderBy('name')->get(),
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'variants' => $variants,
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'isAdminUser' => $this->currentUserHasRole('ADMIN'),
        ]);
    }

    /**
     * Lưu phiếu nhập/xuất kho.
     */
    public function storeTransaction(Request $request): RedirectResponse
    {
        // Luong: Kiem tra va lay du lieu hop le tu request.
        $data = $request->validate([
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'transaction_code' => ['nullable', 'string', 'max:50', 'unique:stock_transactions,transaction_code'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'type' => ['required', 'in:IMPORT,EXPORT'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'source_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'target_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'expected_date' => ['nullable', 'date'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'note' => ['nullable', 'string', 'max:1000'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'variant_id' => ['required', 'array', 'min:1'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'variant_id.*' => ['required', 'integer', 'exists:product_variants,id'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'quantity' => ['required', 'array', 'min:1'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'quantity.*' => ['required', 'integer', 'min:1'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'unit_cost' => ['nullable', 'array'],
            // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
            'unit_cost.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Luong: Gan ket qua xu ly vao bien $type.
        $type = $data['type'];
        // Luong: Gan ket qua xu ly vao bien $sourceWarehouseId.
        $sourceWarehouseId = filled($data['source_warehouse_id'] ?? null) ? (int) $data['source_warehouse_id'] : 1;
        // Luong: Gan ket qua xu ly vao bien $targetWarehouseId.
        $targetWarehouseId = filled($data['target_warehouse_id'] ?? null) ? (int) $data['target_warehouse_id'] : 1;

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($type === 'IMPORT') {
            // Luong: Gan ket qua xu ly vao bien $sourceWarehouseId.
            $sourceWarehouseId = null;
            // Luong: Gan ket qua xu ly vao bien $targetWarehouseId.
            $targetWarehouseId = $targetWarehouseId ?: 1;
            $this->assertActiveWarehouse($targetWarehouseId, 'Bạn cần chọn kho đích đang hoạt động.', 'target_warehouse_id');
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($type === 'EXPORT') {
            // Luong: Gan ket qua xu ly vao bien $targetWarehouseId.
            $targetWarehouseId = null;
            // Luong: Gan ket qua xu ly vao bien $sourceWarehouseId.
            $sourceWarehouseId = $sourceWarehouseId ?: 1;
            $this->assertActiveWarehouse($sourceWarehouseId, 'Bạn cần chọn kho nguồn đang hoạt động.', 'source_warehouse_id');
        }

        // Luong: Gan ket qua xu ly vao bien $items.
        $items = collect($data['variant_id'])
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->map(function ($variantId, $index) use ($data) {
                // Luong: Tra ve ket qua cuoi cung cua ham.
                return [
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'variant_id' => (int) $variantId,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'quantity' => (int) ($data['quantity'][$index] ?? 0),
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'unit_cost' => filled($data['unit_cost'][$index] ?? null) ? (float) $data['unit_cost'][$index] : null,
                ];
            })
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->filter(fn ($item) => $item['variant_id'] > 0)
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->values();

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($items->isEmpty()) {
            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw ValidationException::withMessages([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'variant_id' => 'Phiếu kho cần ít nhất 1 sản phẩm.',
            ]);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($items->pluck('variant_id')->duplicates()->isNotEmpty()) {
            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw ValidationException::withMessages([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'variant_id' => 'Mỗi biến thể sản phẩm chỉ được chọn một lần trong cùng phiếu kho.',
            ]);
        }

        // Luong: Kiem tra dieu kien de re nhanh luong xu ly.
        if ($items->contains(fn ($item) => $item['quantity'] < 1)) {
            // Luong: Nem loi de dung luong khi dieu kien nghiep vu khong dat.
            throw ValidationException::withMessages([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'quantity' => 'Số lượng nhập hoặc xuất kho phải tối thiểu là 1.',
            ]);
        }

        $isAdminUser = $this->currentUserHasRole('ADMIN');

        // Luong: Mo transaction de cac thao tac CSDL cung thanh cong hoac cung rollback.
        $transaction = DB::transaction(function () use ($data, $items, $sourceWarehouseId, $targetWarehouseId, $type, $isAdminUser) {
            // Luong: Tao ban ghi moi tu du lieu da chuan bi.
            $transaction = StockTransaction::create([
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'transaction_code' => filled($data['transaction_code'] ?? null)
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    ? $data['transaction_code']
                    // Luong: Xu ly dong logic tiep theo trong ham public nay.
                    : $this->nextTransactionCode($type),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'type' => $type,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'source_warehouse_id' => $sourceWarehouseId,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'target_warehouse_id' => $targetWarehouseId,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'status' => $isAdminUser ? 'COMPLETED' : 'PENDING',
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'expected_date' => $data['expected_date'] ?? null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'note' => $data['note'] ?? null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'created_by' => Auth::id(),
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'confirmed_by' => $isAdminUser ? Auth::id() : null,
                // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                'confirmed_at' => $isAdminUser ? now() : null,
            ]);

            // Luong: Lap qua tung phan tu de xu ly lan luot.
            foreach ($items as $item) {
                // Luong: Goi thao tac tren doi tuong dang duoc xu ly.
                $transaction->items()->create([
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'variant_id' => $item['variant_id'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'ordered_quantity' => $item['quantity'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'actual_quantity' => $isAdminUser ? $item['quantity'] : 0,
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'unit_cost' => $item['unit_cost'],
                    // Luong: Khai bao gia tri cho mot khoa du lieu/cau hinh.
                    'note' => $data['note'] ?? null,
                ]);
            }

            if ($isAdminUser) {
                $transaction->load('items');
                $this->applyTransactionInventory($transaction);
            }

            // Luong: Tra ve ket qua cuoi cung cua ham.
            return $transaction;
        });

        $message = $isAdminUser
            ? 'Đã tạo phiếu kho ' . $transaction->transaction_code . ' và cập nhật tồn kho.'
            : 'Đã tạo đề xuất kho ' . $transaction->transaction_code . '. Admin cần duyệt trước khi tồn kho thay đổi.';

        // Luong: Dieu huong nguoi dung sang route hoac trang phu hop.
        return redirect()
            // Luong: Noi tiep chuoi goi ham de hoan thien thao tac hien tai.
            ->route('admin.warehouses.show-transaction', $transaction)
            // Luong: Gan them thong bao hoac du lieu flash cho lan hien thi tiep theo.
            ->with('success', $message);
    }

    public function approveTransaction(StockTransaction $transaction): RedirectResponse
    {
        if (! $this->currentUserHasRole('ADMIN')) {
            abort(403);
        }

        DB::transaction(function () use ($transaction) {
            $lockedTransaction = StockTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ duyệt được phiếu đang chờ duyệt.',
                ]);
            }

            if (! in_array($lockedTransaction->type, ['IMPORT', 'EXPORT'], true)) {
                throw ValidationException::withMessages([
                    'type' => 'Chỉ duyệt thủ công phiếu nhập kho hoặc xuất kho.',
                ]);
            }

            $lockedTransaction->load('items');
            $this->applyTransactionInventory($lockedTransaction);

            foreach ($lockedTransaction->items as $item) {
                $item->update(['actual_quantity' => (int) $item->ordered_quantity]);
            }

            $lockedTransaction->update([
                'status' => 'COMPLETED',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.warehouses.show-transaction', $transaction)
            ->with('success', 'Đã duyệt phiếu kho và cập nhật tồn kho.');
    }

    public function rejectTransaction(Request $request, StockTransaction $transaction): RedirectResponse
    {
        if (! $this->currentUserHasRole('ADMIN')) {
            abort(403);
        }

        $data = $request->validate([
            'reject_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($transaction->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'status' => 'Chỉ từ chối được phiếu đang chờ duyệt.',
            ]);
        }

        $reason = trim((string) ($data['reject_reason'] ?? ''));
        $note = trim((string) $transaction->note);
        $rejectNote = 'Từ chối' . ($reason !== '' ? ': ' . $reason : '.');

        $transaction->update([
            'status' => 'CANCELLED',
            'note' => $note !== '' ? $note . "\n" . $rejectNote : $rejectNote,
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        return redirect()
            ->route('admin.warehouses.show-transaction', $transaction)
            ->with('success', 'Đã từ chối đề xuất kho.');
    }

    private function applyTransactionInventory(StockTransaction $transaction): void
    {
        if ($transaction->type === 'IMPORT') {
            $targetWarehouseId = (int) $transaction->target_warehouse_id;
            $this->assertActiveWarehouse($targetWarehouseId, 'Kho đích của phiếu không còn hoạt động.', 'target_warehouse_id');

            foreach ($transaction->items as $item) {
                $quantity = (int) $item->ordered_quantity;
                $this->addVariantInventory($targetWarehouseId, (int) $item->variant_id, $quantity);
                $this->activateVariantProduct((int) $item->variant_id);
            }

            return;
        }

        if ($transaction->type === 'EXPORT') {
            $sourceWarehouseId = (int) $transaction->source_warehouse_id;
            $this->assertActiveWarehouse($sourceWarehouseId, 'Kho nguồn của phiếu không còn hoạt động.', 'source_warehouse_id');

            foreach ($transaction->items as $item) {
                $this->subtractVariantInventory($sourceWarehouseId, (int) $item->variant_id, (int) $item->ordered_quantity);
            }
        }
    }

    private function currentUserHasRole(string $role): bool
    {
        $userId = (int) Auth::id();

        if ($userId <= 0) {
            return false;
        }

        return DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.code', $role)
            ->exists();
    }

    /**
     * Kiểm tra kho còn hoạt động không.
     */
    private function assertActiveWarehouse(?int $warehouseId, string $message, string $field): void
    {
        if (! $warehouseId || ! Warehouse::active()->whereKey($warehouseId)->exists()) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    /**
     * Cộng tồn kho cho biến thể sản phẩm.
     */
    private function addVariantInventory(int $warehouseId, int $variantId, int $quantity): void
    {
        $inventory = Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->increment('quantity', $quantity);
            return;
        }

        Inventory::create([
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'min_stock_level' => Warehouse::whereKey($warehouseId)->value('min_stock_level') ?? 10,
        ]);
    }

    /**
     * Trừ tồn kho của biến thể sản phẩm.
     */
    private function subtractVariantInventory(int $warehouseId, int $variantId, int $quantity): void
    {
        $inventory = Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        $available = $inventory ? max(0, (int) $inventory->quantity) : 0;

        if ($available < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng xuất vượt quá tồn kho khả dụng.',
            ]);
        }

        $inventory->decrement('quantity', $quantity);
    }

    /**
     * Bật sản phẩm khi biến thể có tồn kho.
     */
    private function activateVariantProduct(int $variantId): void
    {
        ProductVariant::whereKey($variantId)->update(['status' => 'ACTIVE']);

        DB::table('products')
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->where('product_variants.id', $variantId)
            ->whereIn('products.status', ['DRAFT', 'INACTIVE'])
            ->update(['products.status' => 'ACTIVE']);
    }

    /**
     * Tạo mã giao dịch kho mới.
     */
    private function nextTransactionCode(string $type): string
    {
        $prefix = [
            'IMPORT' => 'PN',
            'EXPORT' => 'PX',
        ][$type] ?? 'STK';

        return $prefix . now()->format('YmdHis') . random_int(10, 99);
    }

    /**
     * Tạo đường dẫn ảnh sản phẩm.
     */
    private function productImageUrl(?string $image): string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return asset('upload/no-image.jpg');
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'upload/')) {
            return asset($image);
        }

        if (str_starts_with($image, 'anh_san_pham/')) {
            return asset('upload/' . $image);
        }

        return asset('upload/anh_san_pham/' . $image);
    }
}
