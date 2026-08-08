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
                    'OUT' => $query->whereRaw('quantity <= 0'),
                    'LOW' => $query->whereRaw('quantity > 0')->whereRaw('quantity <= COALESCE(min_stock_level, 10)'),
                    'OK' => $query->whereRaw('quantity > COALESCE(min_stock_level, 10)'),
                    default => null,
                };
            })
            ->orderBy('quantity')
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
            ->selectRaw('COALESCE(SUM(quantity), 0) as available_stock')
            ->selectRaw('SUM(CASE WHEN quantity <= COALESCE(min_stock_level, 10) THEN 1 ELSE 0 END) as low_stock_rows')
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
            'subtitle' => 'Phiếu nhập/xuất kho',
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
        $variants = DB::table('product_variants as pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('colors as c', 'c.id', '=', 'pv.color_id')
            ->leftJoin('lens_sizes as ls', 'ls.id', '=', 'pv.lens_size_id')
            ->leftJoin('inventories as i', 'i.variant_id', '=', 'pv.id')
            ->where('p.status', '<>', 'DISCONTINUED')
            ->select([
                'pv.id as variant_id',
                'pv.sku',
                'p.product_code',
                'p.name as product_name',
                'p.thumbnail_url',
                'p.import_price',
                'p.base_price',
                'p.sale_price',
                'pv.variant_price',
                'c.name as color_name',
                'c.hex_code as color_hex',
                'ls.name as lens_size_name',
            ])
            ->selectRaw('COALESCE(SUM(i.quantity), 0) as available_stock')
            ->groupBy(
                'pv.id',
                'pv.sku',
                'p.product_code',
                'p.name',
                'p.thumbnail_url',
                'p.import_price',
                'p.base_price',
                'p.sale_price',
                'pv.variant_price',
                'c.name',
                'c.hex_code',
                'ls.name'
            )
            ->orderBy('p.name')
            ->orderBy('c.name')
            ->orderBy('ls.name')
            ->get()
            ->map(fn ($variant) => [
                'id' => (int) $variant->variant_id,
                'label' => trim((string) $variant->product_name),
                'meta' => trim(($variant->product_code ?? '') . ' | ' . ($variant->color_name ?: '-') . ' | Size ' . ($variant->lens_size_name ?: '-')),
                'image' => $this->productImageUrl($variant->thumbnail_url),
                'stock' => (int) $variant->available_stock,
                'price' => (float) ($variant->import_price ?: $variant->variant_price ?: $variant->sale_price ?: $variant->base_price ?: 0),
                'salePrice' => (float) ($variant->sale_price ?: $variant->variant_price ?: $variant->base_price ?: 0),
            ]);

        return view('admin.warehouses.transaction-form', [
            'warehouses' => Warehouse::active()->orderBy('type')->orderBy('name')->get(),
            'variants' => $variants,
        ]);
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'transaction_code' => ['nullable', 'string', 'max:50', 'unique:stock_transactions,transaction_code'],
            'type' => ['required', 'in:IMPORT,EXPORT'],
            'source_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'target_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'expected_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'variant_id' => ['required', 'array', 'min:1'],
            'variant_id.*' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'array'],
            'unit_cost.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $type = $data['type'];
        $sourceWarehouseId = filled($data['source_warehouse_id'] ?? null) ? (int) $data['source_warehouse_id'] : 1;
        $targetWarehouseId = filled($data['target_warehouse_id'] ?? null) ? (int) $data['target_warehouse_id'] : 1;

        if ($type === 'IMPORT') {
            $sourceWarehouseId = null;
            $targetWarehouseId = $targetWarehouseId ?: 1;
        }

        if ($type === 'EXPORT') {
            $targetWarehouseId = null;
            $sourceWarehouseId = $sourceWarehouseId ?: 1;
        }

        $items = collect($data['variant_id'])
            ->map(function ($variantId, $index) use ($data) {
                return [
                    'variant_id' => (int) $variantId,
                    'quantity' => (int) ($data['quantity'][$index] ?? 0),
                    'unit_cost' => filled($data['unit_cost'][$index] ?? null) ? (float) $data['unit_cost'][$index] : null,
                ];
            })
            ->filter(fn ($item) => $item['variant_id'] > 0)
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'variant_id' => 'Phiếu kho cần ít nhất 1 sản phẩm.',
            ]);
        }

        if ($items->pluck('variant_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variant_id' => 'Mỗi biến thể sản phẩm chỉ được chọn một lần trong cùng phiếu kho.',
            ]);
        }

        if ($items->contains(fn ($item) => $item['quantity'] < 1)) {
            throw ValidationException::withMessages([
                'quantity' => 'Số lượng nhập hoặc xuất kho phải tối thiểu là 1.',
            ]);
        }

        $transaction = DB::transaction(function () use ($data, $items, $sourceWarehouseId, $targetWarehouseId, $type) {
            if ($type === 'EXPORT') {
                foreach ($items as $item) {
                    $this->subtractVariantInventory($sourceWarehouseId, $item['variant_id'], $item['quantity']);
                }
            }

            if ($type === 'IMPORT') {
                foreach ($items as $item) {
                    $this->addVariantInventory($targetWarehouseId, $item['variant_id'], $item['quantity']);
                    $this->activateVariantProduct($item['variant_id']);
                }
            }

            $transaction = StockTransaction::create([
                'transaction_code' => filled($data['transaction_code'] ?? null)
                    ? $data['transaction_code']
                    : $this->nextTransactionCode($type),
                'type' => $type,
                'source_warehouse_id' => $sourceWarehouseId,
                'target_warehouse_id' => $targetWarehouseId,
                'status' => 'COMPLETED',
                'expected_date' => $data['expected_date'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);

            foreach ($items as $item) {
                $transaction->items()->create([
                    'variant_id' => $item['variant_id'],
                    'ordered_quantity' => $item['quantity'],
                    'actual_quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'note' => $data['note'] ?? null,
                ]);
            }

            return $transaction;
        });

        return redirect()
            ->route('admin.warehouses.index', ['warehouse_tab' => 'transactions'])
            ->with('success', 'Đã tạo phiếu kho ' . $transaction->transaction_code . ' và ghi nhận tồn kho.');
    }

    private function assertActiveWarehouse(?int $warehouseId, string $message, string $field): void
    {
        if (! $warehouseId || ! Warehouse::active()->whereKey($warehouseId)->exists()) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

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

    private function activateVariantProduct(int $variantId): void
    {
        ProductVariant::whereKey($variantId)->update(['status' => 'ACTIVE']);

        DB::table('products')
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->where('product_variants.id', $variantId)
            ->whereIn('products.status', ['DRAFT', 'INACTIVE'])
            ->update(['products.status' => 'ACTIVE']);
    }

    private function nextTransactionCode(string $type): string
    {
        $prefix = [
            'IMPORT' => 'PN',
            'EXPORT' => 'PX',
        ][$type] ?? 'STK';

        return $prefix . now()->format('YmdHis') . random_int(10, 99);
    }

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
