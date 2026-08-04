@extends('admin.layouts.app')

@section('title', 'Kho hàng')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $warehouseType = fn ($type) => match ($type) {
        'NORMAL' => 'Kho thường',
        'RETURN' => 'Kho hàng hoàn',
        'WARRANTY' => 'Kho bảo hành',
        'STORE' => 'Cửa hàng',
        default => $type ?: '-',
    };
    $transactionType = fn ($type) => match ($type) {
        'IMPORT' => ['Nhập kho', 'success', 'fa-arrow-down'],
        'EXPORT' => ['Xuất kho', 'danger', 'fa-arrow-up'],
        'RETURN_IN' => ['Nhập hàng hoàn', 'success', 'fa-undo'],
        'SALE_OUT' => ['Xuất bán', 'dark', 'fa-shopping-cart'],
        default => [$type ?: '-', 'muted', 'fa-circle'],
    };
    $transactionStatus = fn ($status) => match ($status) {
        'DRAFT' => ['Nháp', 'muted'],
        'PENDING' => ['Đang chờ', 'warning'],
        'COMPLETED' => ['Hoàn tất', 'success'],
        'CANCELLED' => ['Đã hủy', 'danger'],
        default => [$status ?: '-', 'muted'],
    };
    $stockState = function ($available, $minStock) {
        $available = (int) $available;
        $minStock = (int) ($minStock ?: 10);

        if ($available <= 0) {
            return ['Hết hàng', 'danger'];
        }

        if ($available <= $minStock) {
            return ['Sắp hết', 'warning'];
        }

        return ['Ổn định', 'success'];
    };
    $activeTab = $activeTab ?? 'stock';
@endphp

@push('styles')
<style>
.wa-page{background:#f5f7fb;color:#172033;min-height:100vh;padding:20px 24px 70px}.wa-inner{max-width:1500px;margin:0 auto}
.wa-toolbar{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:14px}.wa-title h4{color:#111827;font-size:24px;font-weight:900;line-height:1.2;margin:0 0 6px}.wa-title p{color:#667085;font-size:14px;margin:0}.wa-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}
.wa-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;cursor:pointer;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.wa-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.wa-btn.dark{background:#111827;border-color:#111827;color:#fff}.wa-btn.danger{background:#fee2e2;border-color:#fecaca;color:#991b1b}.wa-btn:hover{filter:brightness(.98);color:inherit}.wa-btn.primary:hover,.wa-btn.dark:hover{color:#fff}
.wa-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.wa-summary{margin-bottom:14px}
.wa-grid{display:grid;gap:10px;grid-template-columns:repeat(6,minmax(0,1fr));padding:12px}.wa-stat{background:#fbfcfd;border:1px solid #eef2f6;border-radius:7px;min-height:78px;padding:12px}.wa-stat span{color:#667085;display:block;font-size:11px;font-weight:900;text-transform:uppercase}.wa-stat strong{color:#111827;display:block;font-size:23px;font-weight:900;line-height:1;margin-top:8px}
.wa-tabs{background:#fbfdff;border-top:1px solid #eef2f6;display:flex;flex-wrap:wrap;gap:8px;padding:11px 12px}.wa-tab-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#344054;cursor:pointer;display:inline-flex;font-size:13px;font-weight:900;gap:8px;min-height:36px;padding:0 12px}.wa-tab-btn strong{background:#f2f4f7;border-radius:999px;color:#111827;font-size:12px;padding:2px 7px}.wa-tab-btn.is-active{background:#eff6ff;border-color:#2563eb;color:#1d4ed8}.wa-tab-btn.is-active strong{background:#dbeafe;color:#1d4ed8}
.wa-panel{display:none}.wa-panel.is-active{display:block}.wa-panel-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.wa-card-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 15px}.wa-card-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.wa-card-head small{color:#667085;font-size:12px;font-weight:800}
.wa-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:9px;grid-template-columns:minmax(220px,1.45fr) repeat(4,minmax(130px,.8fr)) auto;padding:12px 15px}.wa-filter.transactions{grid-template-columns:minmax(200px,1.2fr) repeat(5,minmax(120px,.75fr)) auto}.wa-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:5px;text-transform:uppercase}.wa-input,.wa-select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:36px;padding:0 10px;width:100%}
.wa-table-wrap{overflow-x:auto}.wa-table{border-collapse:collapse;min-width:900px;width:100%}.wa-table.compact{min-width:840px}.wa-table th{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:.04em;padding:10px 11px;text-align:left;text-transform:uppercase;white-space:nowrap}.wa-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:10px 11px;vertical-align:middle}.wa-table tr:hover td{background:#fafafa}
.wa-product{align-items:center;display:flex;gap:10px;min-width:220px}.wa-thumb{background:#f8fafc;border:1px solid #e4e7ec;border-radius:7px;flex:0 0 44px;height:44px;object-fit:cover;width:44px}.wa-name{color:#111827;font-size:13px;font-weight:900;line-height:1.35}.wa-sub{color:#667085;font-size:11px;line-height:1.35;margin-top:3px}.wa-variant-line{align-items:center;display:flex;gap:6px;white-space:nowrap}.wa-color{border:1px solid rgba(17,24,39,.2);border-radius:50%;display:inline-block;height:15px;width:15px}
.wa-number{color:#111827;font-weight:900;white-space:nowrap}.wa-badge{align-items:center;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;gap:6px;min-height:25px;padding:0 9px;white-space:nowrap}.wa-badge.success{background:#dcfce7;color:#166534}.wa-badge.warning{background:#fef3c7;color:#92400e}.wa-badge.danger{background:#fee2e2;color:#991b1b}.wa-badge.info{background:#dbeafe;color:#1d4ed8}.wa-badge.dark{background:#e5e7eb;color:#111827}.wa-badge.muted{background:#f3f4f6;color:#4b5563}
.wa-warehouse-card{align-items:center;border:1px solid #eef2f6;border-radius:8px;display:grid;gap:12px;grid-template-columns:44px minmax(0,1fr) auto;padding:12px}.wa-warehouse-icon{align-items:center;background:#eff6ff;border-radius:8px;color:#1d4ed8;display:inline-flex;height:44px;justify-content:center;width:44px}.wa-address{color:#667085;font-size:12px;line-height:1.45;margin-top:4px}
.wa-flow{color:#475467;line-height:1.5}.wa-flow strong{color:#111827}.wa-empty{color:#667085;padding:30px 14px;text-align:center}.wa-pagination{padding:12px 15px}.wa-mobile-note{display:none}
@media(max-width:1180px){.wa-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.wa-filter,.wa-filter.transactions{grid-template-columns:repeat(2,minmax(0,1fr))}.wa-filter .wa-actions{grid-column:1/-1}.wa-table{min-width:980px}}@media(max-width:760px){.wa-page{padding:14px}.wa-toolbar,.wa-card-head{align-items:flex-start;flex-direction:column}.wa-actions,.wa-btn{width:100%}.wa-grid,.wa-filter,.wa-filter.transactions{grid-template-columns:1fr}.wa-mobile-note{display:block;color:#667085;font-size:12px;margin-top:8px}}
</style>
@endpush

@section('content')
<div class="wa-page">
    <div class="wa-inner">
        <div class="wa-toolbar">
            <div class="wa-title">
                <h4>Kho hàng mắt kính</h4>
                <p>Xem nhanh tồn kho, kho đang dùng và phiếu kho trong cùng một màn hình.</p>
            </div>
            <div class="wa-actions">
                <a href="{{ route('admin.business.index') }}?tab=warehouses" class="wa-btn dark"><i class="fa fa-warehouse"></i> Quản lý kho</a>
                <a href="{{ route('admin.warehouses.create-transaction') }}" class="wa-btn primary"><i class="fa fa-plus"></i> Tạo phiếu</a>
            </div>
        </div>

        <div class="wa-card wa-summary">
            <div class="wa-grid">
                <div class="wa-stat"><span>Kho hoạt động</span><strong>{{ $num($summary->warehouse_count ?? 0) }}</strong></div>
                <div class="wa-stat"><span>Biến thể</span><strong>{{ $num($summary->variant_count ?? 0) }}</strong></div>
                <div class="wa-stat"><span>Tổng tồn</span><strong>{{ $num($summary->total_stock ?? 0) }}</strong></div>
                <div class="wa-stat"><span>Có thể bán</span><strong>{{ $num($summary->available_stock ?? 0) }}</strong></div>
                <div class="wa-stat"><span>Sắp hết</span><strong>{{ $num($summary->low_stock_rows ?? 0) }}</strong></div>
            </div>

            <div class="wa-tabs" role="tablist" aria-label="Quản lý kho">
                <button type="button" class="wa-tab-btn {{ $activeTab === 'stock' ? 'is-active' : '' }}" data-wa-tab="stock">
                    Tồn kho <strong>{{ $num($inventories->count()) }}</strong>
                </button>
                <button type="button" class="wa-tab-btn {{ $activeTab === 'warehouses' ? 'is-active' : '' }}" data-wa-tab="warehouses">
                    Kho <strong>{{ $num($warehouses->count()) }}</strong>
                </button>
                <button type="button" class="wa-tab-btn {{ $activeTab === 'transactions' ? 'is-active' : '' }}" data-wa-tab="transactions">
                    Phiếu kho <strong>{{ $num($transactions->count()) }}</strong>
                </button>
            </div>
        </div>

        <section class="wa-panel {{ $activeTab === 'stock' ? 'is-active' : '' }}" data-wa-panel="stock">
            <div class="wa-panel-card">
                <div class="wa-card-head">
                    <div>
                        <h6>Tồn kho theo biến thể</h6>
                        <small>Ưu tiên hiển thị các dòng sắp hết hoặc tồn thấp.</small>
                    </div>
                    <a href="{{ route('admin.warehouses.create-transaction') }}" class="wa-btn primary"><i class="fa fa-plus"></i> Nhập / xuất</a>
                </div>
                <form method="get" class="wa-filter">
                    <input type="hidden" name="warehouse_tab" value="stock">
                    <div class="wa-field">
                        <label>Tìm sản phẩm</label>
                        <input class="wa-input" type="text" name="inventory_keyword" value="{{ $inventoryFilters['inventory_keyword'] ?? '' }}" placeholder="Tên kính, mã, màu...">
                    </div>
                    <div class="wa-field">
                        <label>Kho</label>
                        <select class="wa-select" name="inventory_warehouse_id">
                            <option value="">Tất cả kho</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(($inventoryFilters['inventory_warehouse_id'] ?? '') == $warehouse->id)>
                                    {{ $warehouse->warehouse_code }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-field">
                        <label>Danh mục</label>
                        <select class="wa-select" name="inventory_category_id">
                            <option value="">Tất cả danh mục</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(($inventoryFilters['inventory_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-field">
                        <label>Tình trạng</label>
                        <select class="wa-select" name="inventory_stock_state">
                            <option value="">Tất cả</option>
                            <option value="OUT" @selected(($inventoryFilters['inventory_stock_state'] ?? '') === 'OUT')>Hết hàng</option>
                            <option value="LOW" @selected(($inventoryFilters['inventory_stock_state'] ?? '') === 'LOW')>Sắp hết</option>
                            <option value="OK" @selected(($inventoryFilters['inventory_stock_state'] ?? '') === 'OK')>Ổn định</option>
                        </select>
                    </div>
                    <div class="wa-field">
                        <label>Số dòng</label>
                        <select class="wa-select" name="inventory_limit">
                            @foreach ([50, 100, 200, 500] as $limit)
                                <option value="{{ $limit }}" @selected($inventoryLimit === $limit)>{{ $limit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-actions">
                        <button class="wa-btn primary" type="submit"><i class="fa fa-search"></i> Lọc</button>
                        <a class="wa-btn" href="{{ route('admin.warehouses.index') }}?warehouse_tab=stock">Xóa lọc</a>
                    </div>
                </form>
                <div class="wa-table-wrap">
                    <table class="wa-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Biến thể</th>
                                <th>Kho</th>
                                <th>Tồn</th>
                                <th>Có thể bán</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inventories as $inventory)
                                @php
                                    $variant = $inventory->variant;
                                    $product = $variant?->product;
                                    $available = (int) $inventory->quantity;
                                    [$stateText, $stateClass] = $stockState($available, $inventory->min_stock_level);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="wa-product">
                                            <img class="wa-thumb" src="{{ $product?->image_url ?? asset('upload/no-image.jpg') }}" alt="{{ $product?->name ?? 'Sản phẩm' }}">
                                            <div>
                                                <div class="wa-name">{{ $product?->name ?? '-' }}</div>
                                                <div class="wa-sub">{{ $product?->product_code ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="wa-variant-line">
                                            @if ($variant?->color)
                                                <span class="wa-color" style="background: {{ $variant->color->hex_code }}"></span>
                                            @endif
                                            <span>{{ $variant?->color->name ?? '-' }} / Size {{ $variant?->lensSize->name ?? '-' }}</span>
                                        </div>
                                        <div class="wa-sub">{{ $variant?->sku ?? '-' }}</div>
                                    </td>
                                    <td>{{ $inventory->warehouse->name ?? '-' }}</td>
                                    <td class="wa-number">{{ $num($inventory->quantity) }}</td>
                                    <td class="wa-number">{{ $num($available) }}</td>
                                    <td><span class="wa-badge {{ $stateClass }}">{{ $stateText }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="wa-empty">Không có dòng tồn kho phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="wa-panel {{ $activeTab === 'warehouses' ? 'is-active' : '' }}" data-wa-panel="warehouses">
            <div class="wa-panel-card">
                <div class="wa-card-head">
                    <div>
                        <h6>Danh sách kho</h6>
                        <small>Theo dõi mã kho, loại kho, trạng thái và số dòng tồn.</small>
                    </div>
                    <a href="{{ route('admin.business.index') }}?tab=warehouses" class="wa-btn dark"><i class="fa fa-cog"></i> Nghiệp vụ kho</a>
                </div>
                <div class="wa-table-wrap">
                    <table class="wa-table compact">
                        <thead>
                            <tr>
                                <th>Kho</th>
                                <th>Loại</th>
                                <th>Sức chứa</th>
                                <th>Dòng tồn</th>
                                <th>Ngưỡng thấp</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($warehouses as $warehouse)
                                <tr>
                                    <td>
                                        <div class="wa-warehouse-card">
                                            <span class="wa-warehouse-icon"><i class="fa fa-warehouse"></i></span>
                                            <div>
                                                <div class="wa-name">{{ $warehouse->warehouse_code }} - {{ $warehouse->name }}</div>
                                                <div class="wa-address">{{ collect([$warehouse->address_detail, $warehouse->ward_name, $warehouse->district_name, $warehouse->province_name])->filter()->implode(', ') ?: 'Chưa có địa chỉ' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $warehouseType($warehouse->type) }}</td>
                                    <td class="wa-number">{{ $num($warehouse->capacity) }}</td>
                                    <td class="wa-number">{{ $num($warehouse->inventories_count) }}</td>
                                    <td class="wa-number">{{ $num($warehouse->min_stock_level) }}</td>
                                    <td><span class="wa-badge {{ $warehouse->status === 'ACTIVE' ? 'success' : 'muted' }}">{{ $warehouse->status === 'ACTIVE' ? 'Hoạt động' : 'Tạm ẩn' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="wa-empty">Chưa có kho.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="wa-panel {{ $activeTab === 'transactions' ? 'is-active' : '' }}" data-wa-panel="transactions">
            <div class="wa-panel-card">
                <div class="wa-card-head">
                    <div>
                        <h6>Phiếu kho</h6>
                        <small>Danh sách phiếu nhập kho, xuất kho và xuất bán.</small>
                    </div>
                    <a href="{{ route('admin.warehouses.create-transaction') }}" class="wa-btn primary"><i class="fa fa-plus"></i> Tạo phiếu</a>
                </div>
                <form method="get" class="wa-filter transactions">
                    <input type="hidden" name="warehouse_tab" value="transactions">
                    <div class="wa-field">
                        <label>Tìm phiếu</label>
                        <input class="wa-input" type="text" name="stock_keyword" value="{{ $stockFilters['stock_keyword'] ?? '' }}" placeholder="Mã phiếu, ghi chú...">
                    </div>
                    <div class="wa-field">
                        <label>Loại phiếu</label>
                        <select class="wa-select" name="stock_type">
                            <option value="">Tất cả</option>
                            @foreach (['IMPORT' => 'Nhập kho', 'EXPORT' => 'Xuất kho', 'RETURN_IN' => 'Nhập hàng hoàn', 'SALE_OUT' => 'Xuất bán'] as $type => $label)
                                <option value="{{ $type }}" @selected(($stockFilters['stock_type'] ?? '') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-field">
                        <label>Kho</label>
                        <select class="wa-select" name="stock_warehouse_id">
                            <option value="">Tất cả kho</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(($stockFilters['stock_warehouse_id'] ?? '') == $warehouse->id)>{{ $warehouse->warehouse_code }} - {{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-field">
                        <label>Trạng thái</label>
                        <select class="wa-select" name="stock_status">
                            <option value="">Tất cả</option>
                            @foreach (['DRAFT' => 'Nháp', 'PENDING' => 'Đang chờ', 'COMPLETED' => 'Hoàn tất', 'CANCELLED' => 'Đã hủy'] as $status => $label)
                                <option value="{{ $status }}" @selected(($stockFilters['stock_status'] ?? '') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-field">
                        <label>Từ ngày</label>
                        <input class="wa-input" type="date" name="stock_date_from" value="{{ $stockFilters['stock_date_from'] ?? '' }}">
                    </div>
                    <div class="wa-field">
                        <label>Đến ngày</label>
                        <input class="wa-input" type="date" name="stock_date_to" value="{{ $stockFilters['stock_date_to'] ?? '' }}">
                    </div>
                    <div class="wa-actions">
                        <button class="wa-btn primary" type="submit"><i class="fa fa-search"></i> Lọc</button>
                        <a class="wa-btn" href="{{ route('admin.warehouses.index') }}?warehouse_tab=transactions">Xóa lọc</a>
                    </div>
                </form>
                <div class="wa-table-wrap">
                    <table class="wa-table">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Loại</th>
                                <th>Luồng kho</th>
                                <th>Số dòng</th>
                                <th>Số lượng</th>
                                <th>Ngày tạo</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                @php
                                    [$typeText, $typeClass, $typeIcon] = $transactionType($transaction->type);
                                    [$statusText, $statusClass] = $transactionStatus($transaction->status);
                                    $totals = $transactionItemTotals->get($transaction->id);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="wa-name">{{ $transaction->transaction_code }}</div>
                                        <div class="wa-sub">{{ $transaction->note ?: 'Không có ghi chú' }}</div>
                                    </td>
                                    <td><span class="wa-badge {{ $typeClass }}"><i class="fa {{ $typeIcon }}"></i>{{ $typeText }}</span></td>
                                    <td>
                                        <div class="wa-flow">
                                            <strong>{{ $transaction->sourceWarehouse->name ?? '-' }}</strong>
                                            <span> → </span>
                                            <strong>{{ $transaction->targetWarehouse->name ?? '-' }}</strong>
                                        </div>
                                    </td>
                                    <td class="wa-number">{{ $num($transaction->items_count) }}</td>
                                    <td class="wa-number">{{ $num($totals->actual_quantity ?: $totals->ordered_quantity ?: 0) }}</td>
                                    <td>{{ $transaction->created_at?->format('d/m/Y H:i') }}</td>
                                    <td><span class="wa-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="wa-empty">Không có phiếu kho phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-wa-tab]').forEach(function (button) {
    button.addEventListener('click', function () {
        var tab = button.dataset.waTab;
        document.querySelectorAll('[data-wa-tab]').forEach(function (item) {
            item.classList.toggle('is-active', item.dataset.waTab === tab);
        });
        document.querySelectorAll('[data-wa-panel]').forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.waPanel === tab);
        });
    });
});
</script>
@endpush
