@extends('admin.layouts.app')

@section('title', 'Nghiệp vụ')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $statusBadge = fn ($status) => match ($status) {
        'ACTIVE' => ['Đang bật', 'success'],
        'INACTIVE' => ['Tạm tắt', 'muted'],
        'SCHEDULED' => ['Lên lịch', 'info'],
        'EXPIRED' => ['Hết hạn', 'danger'],
        'DRAFT' => ['Nháp', 'muted'],
        'PENDING' => ['Đang chờ', 'warning'],
        'COMPLETED' => ['Hoàn tất', 'success'],
        'CANCELLED' => ['Đã hủy', 'danger'],
        default => [$status ?: '-', 'muted'],
    };
    $warehouseType = fn ($type) => match ($type) {
        'NORMAL' => 'Kho thường',
        'RETURN' => 'Kho hoàn',
        'WARRANTY' => 'Kho bảo hành',
        'STORE' => 'Kho cửa hàng',
        default => $type ?: '-',
    };
    $stockType = fn ($type) => match ($type) {
        'IMPORT' => 'Nhập kho',
        'EXPORT' => 'Xuất kho',
        'RETURN_IN' => 'Nhập hoàn',
        'SALE_OUT' => 'Xuất bán',
        default => $type ?: '-',
    };
    $tabs = [
        'brands' => ['Thương hiệu', 'fa-certificate', $summary['brands']],
        'promotions' => ['Khuyến mãi', 'fa-tags', $summary['promotions']],
        'warehouses' => ['Kho', 'fa-warehouse', $summary['warehouses']],
        'stores' => ['Cửa hàng', 'fa-store', $summary['stores']],
        'stock' => ['Phiếu kho', 'fa-clipboard-list', $summary['stock']],
    ];
@endphp

@push('styles')
<style>
.biz-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.biz-inner{max-width:1500px;margin:0 auto}.biz-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.biz-title small{color:#0f8a7a;font-size:13px;font-weight:900;text-transform:uppercase}.biz-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.biz-title p{color:#667085;font-size:14px;margin:0}.biz-actions{display:flex;gap:9px;justify-content:flex-end}.biz-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.biz-btn.primary{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.biz-btn.dark{background:#111827;border-color:#111827;color:#fff}.biz-btn:hover{filter:brightness(.98);color:inherit}.biz-btn.primary:hover,.biz-btn.dark:hover{color:#fff}
.biz-summary{display:grid;gap:12px;grid-template-columns:repeat(6,minmax(0,1fr));margin-bottom:14px}.biz-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.biz-stat i{align-items:center;background:#eefcf8;border-radius:8px;color:#0f8a7a;display:inline-flex;height:34px;justify-content:center;width:34px}.biz-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.biz-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}
.biz-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.biz-tabs{background:#fff;border-bottom:1px solid #e4e7ec;display:flex;flex-wrap:wrap;gap:8px;padding:12px}.biz-tab{align-items:center;background:#f8fafc;border:1px solid #e4e7ec;border-radius:7px;color:#344054;display:inline-flex;font-size:13px;font-weight:900;gap:8px;min-height:38px;padding:0 12px;text-decoration:none}.biz-tab strong{background:#fff;border-radius:999px;color:#111827;font-size:12px;padding:2px 7px}.biz-tab.active{background:#e8fff9;border-color:#0f8a7a;color:#087568}.biz-tab.active strong{background:#0f8a7a;color:#fff}.biz-section{display:grid;gap:14px;grid-template-columns:360px minmax(0,1fr);padding:14px}.biz-form{background:#fbfcfd;border:1px solid #eef2f6;border-radius:8px;padding:14px}.biz-form h6,.biz-table-head h6{color:#111827;font-size:16px;font-weight:900;margin:0 0 10px}.biz-field{display:grid;gap:6px;margin-bottom:10px}.biz-field label{color:#667085;font-size:11px;font-weight:900;text-transform:uppercase}.biz-field input,.biz-field select,.biz-field textarea{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.biz-field textarea{min-height:84px;resize:vertical}.biz-check{align-items:center;color:#344054;display:flex;font-size:13px;font-weight:800;gap:9px;margin:2px 0 12px}.biz-check input{height:16px;width:16px}
.biz-table-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.biz-table-head small{color:#667085;font-size:12px;font-weight:800}.biz-table-wrap{overflow-x:auto}.biz-table{border-collapse:collapse;min-width:820px;width:100%}.biz-table th{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:.03em;padding:10px 12px;text-align:left;text-transform:uppercase;white-space:nowrap}.biz-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:11px 12px;vertical-align:middle}.biz-table tr:hover td{background:#fafafa}.biz-name{color:#111827;font-weight:900}.biz-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.biz-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px;white-space:nowrap}.biz-badge.success{background:#dcfce7;color:#166534}.biz-badge.warning{background:#fef3c7;color:#92400e}.biz-badge.danger{background:#fee2e2;color:#991b1b}.biz-badge.info{background:#dbeafe;color:#1d4ed8}.biz-badge.muted{background:#f3f4f6;color:#4b5563}.biz-mini-form{display:inline}.biz-icon-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;height:32px;justify-content:center;width:34px}.biz-icon-btn:hover{background:#f8fafc}.biz-empty{color:#667085;padding:32px 12px;text-align:center}.biz-money{color:#111827;font-weight:900;white-space:nowrap}.biz-alert{background:#f0fdfa;border:1px solid #99f6e4;border-radius:8px;color:#0f766e;font-size:13px;font-weight:800;line-height:1.5;margin-bottom:12px;padding:11px 12px}
@media(max-width:1200px){.biz-summary{grid-template-columns:repeat(3,minmax(0,1fr))}.biz-section{grid-template-columns:1fr}.biz-form{max-width:none}}@media(max-width:760px){.biz-page{margin:-24px -12px 0;padding:16px 12px}.biz-head{flex-direction:column}.biz-actions,.biz-btn{width:100%}.biz-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.biz-table{min-width:760px}}
</style>
@endpush

@section('content')
<div class="biz-page">
    <div class="biz-inner">
        <div class="biz-head">
            <div class="biz-title">
                <small>Mắt kính admin</small>
                <h4>Quản lý nghiệp vụ cửa hàng</h4>
                <p>Gom các dữ liệu vận hành dùng chung: thương hiệu, khuyến mãi, kho, cửa hàng và phiếu kho.</p>
            </div>
            <div class="biz-actions">
                <a class="biz-btn" href="{{ route('admin.warehouses.index') }}"><i class="fa fa-warehouse"></i> Quản lý kho</a>
                <a class="biz-btn primary" href="{{ route('admin.products.create') }}"><i class="fa fa-plus"></i> Thêm sản phẩm</a>
            </div>
        </div>

        <div class="biz-summary">
            @foreach ($tabs as $key => [$label, $icon, $count])
                <a class="biz-stat text-decoration-none" href="{{ route('admin.business.index', ['tab' => $key]) }}">
                    <i class="fa {{ $icon }}"></i>
                    <span>{{ $label }}</span>
                    <strong>{{ $num($count) }}</strong>
                </a>
            @endforeach
        </div>

        <div class="biz-card">
            <div class="biz-tabs">
                @foreach ($tabs as $key => [$label, $icon, $count])
                    <a class="biz-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('admin.business.index', ['tab' => $key]) }}">
                        <i class="fa {{ $icon }}"></i> {{ $label }} <strong>{{ $num($count) }}</strong>
                    </a>
                @endforeach
            </div>

            @if ($activeTab === 'brands')
                <div class="biz-section">
                    <form class="biz-form" method="post" action="{{ route('admin.business.store') }}">
                        @csrf
                        <input type="hidden" name="_business_action" value="save_brand">
                        <h6>Thêm thương hiệu</h6>
                        <div class="biz-field"><label>Tên thương hiệu</label><input name="name" value="{{ old('name') }}" required></div>
                        <div class="biz-field"><label>Logo URL</label><input name="logo_url" value="{{ old('logo_url') }}"></div>
                        <div class="biz-field"><label>Mô tả</label><textarea name="description">{{ old('description') }}</textarea></div>
                        <button class="biz-btn primary w-100" type="submit"><i class="fa fa-save"></i> Lưu thương hiệu</button>
                    </form>

                    <div class="biz-card">
                        <div class="biz-table-head"><h6>Danh sách thương hiệu</h6><small>{{ $brands->count() }} dòng mới nhất</small></div>
                        <div class="biz-table-wrap">
                            <table class="biz-table">
                                <thead><tr><th>ID</th><th>Thương hiệu</th><th>Mô tả</th><th>Sản phẩm</th><th>Trạng thái</th><th></th></tr></thead>
                                <tbody>
                                @forelse ($brands as $brand)
                                    @php([$label, $class] = $statusBadge($brand->status))
                                    <tr>
                                        <td>#{{ $brand->id }}</td>
                                        <td><div class="biz-name">{{ $brand->name }}</div><div class="biz-sub">{{ $brand->logo_url ?: 'Chưa có logo' }}</div></td>
                                        <td>{{ $brand->description ?: '-' }}</td>
                                        <td class="biz-money">{{ $num($brand->products_count) }}</td>
                                        <td><span class="biz-badge {{ $class }}">{{ $label }}</span></td>
                                        <td>
                                            <form class="biz-mini-form" method="post" action="{{ route('admin.business.store') }}">
                                                @csrf
                                                <input type="hidden" name="_business_action" value="toggle_brand">
                                                <input type="hidden" name="id" value="{{ $brand->id }}">
                                                <button class="biz-icon-btn" title="Bật/tắt"><i class="fa fa-power-off"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="biz-empty" colspan="6">Chưa có thương hiệu.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($activeTab === 'promotions')
                <div class="biz-section">
                    <form class="biz-form" method="post" action="{{ route('admin.business.store') }}">
                        @csrf
                        <input type="hidden" name="_business_action" value="save_promotion">
                        <h6>Thêm khuyến mãi</h6>
                        <div class="biz-field"><label>Mã giảm giá</label><input name="promotion_code" value="{{ old('promotion_code') }}" placeholder="VD: WELCOME" maxlength="20" autocomplete="off" oninput="this.value = this.value.toUpperCase()"></div>
                        <div class="biz-field"><label>Tên chương trình</label><input name="name" value="{{ old('name') }}" required></div>
                        <div class="biz-field"><label>Kiểu giảm</label><select name="discount_type"><option value="PERCENT">Phần trăm</option><option value="FIXED_AMOUNT">Số tiền</option></select></div>
                        <div class="biz-field"><label>Giá trị giảm</label><input type="number" min="0" step="0.1" name="discount_value" value="{{ old('discount_value') }}" required></div>
                        <div class="biz-field"><label>Đơn tối thiểu</label><input type="number" min="0" step="1000" name="min_order_amount" value="{{ old('min_order_amount', 0) }}"></div>
                        <div class="biz-field"><label>Giảm tối đa</label><input type="number" min="0" step="1000" name="max_discount_amount" value="{{ old('max_discount_amount') }}" placeholder="Bỏ trống nếu không giới hạn"></div>
                        <div class="biz-field"><label>Lượt sử dụng</label><input type="number" min="1" step="1" name="usage_limit" value="{{ old('usage_limit') }}" placeholder="Bỏ trống nếu không giới hạn"></div>
                        <div class="biz-field"><label>Bắt đầu</label><input type="datetime-local" name="start_at" value="{{ old('start_at', now()->format('Y-m-d\TH:i')) }}" required></div>
                        <div class="biz-field"><label>Kết thúc</label><input type="datetime-local" name="end_at" value="{{ old('end_at') }}"></div>
                        <div class="biz-field"><label>Trạng thái</label><select name="status"><option value="ACTIVE">Đang bật</option><option value="SCHEDULED">Lên lịch</option><option value="INACTIVE">Tạm tắt</option></select></div>
                        <label class="biz-check"><input type="checkbox" name="stackable" value="1"> Cho phép dùng cùng ưu đãi khác</label>
                        <button class="biz-btn primary w-100" type="submit"><i class="fa fa-save"></i> Lưu khuyến mãi</button>
                    </form>

                    <div class="biz-card">
                        <div class="biz-table-head"><h6>Danh sách khuyến mãi</h6><small>{{ $promotions->count() }} dòng mới nhất</small></div>
                        <div class="biz-table-wrap">
                            <table class="biz-table">
                                <thead><tr><th>Mã</th><th>Chương trình</th><th>Giảm</th><th>Điều kiện</th><th>Thời gian</th><th>Đã dùng</th><th>Trạng thái</th><th></th></tr></thead>
                                <tbody>
                                @forelse ($promotions as $promotion)
                                    @php([$label, $class] = $statusBadge($promotion->status))
                                    <tr>
                                        <td>{{ $promotion->promotion_code }}</td>
                                        <td><div class="biz-name">{{ $promotion->name }}</div><div class="biz-sub">{{ $promotion->stackable ? 'Cho cộng dồn' : 'Không cộng dồn' }}</div></td>
                                        <td class="biz-money">{{ $promotion->discount_type === 'PERCENT' ? $num($promotion->discount_value) . '%' : $num($promotion->discount_value) . 'đ' }}</td>
                                        <td><div>Tối thiểu {{ $num($promotion->min_order_amount) }}đ</div><div class="biz-sub">Tối đa {{ $promotion->max_discount_amount ? $num($promotion->max_discount_amount) . 'đ' : 'không giới hạn' }}</div></td>
                                        <td><div>{{ $date($promotion->start_at) }}</div><div class="biz-sub">{{ $date($promotion->end_at) }}</div></td>
                                        <td>{{ $num($promotion->used_count) }}{{ $promotion->usage_limit ? ' / ' . $num($promotion->usage_limit) : '' }}</td>
                                        <td><span class="biz-badge {{ $class }}">{{ $label }}</span></td>
                                        <td>
                                            <form class="biz-mini-form" method="post" action="{{ route('admin.business.store') }}">
                                                @csrf
                                                <input type="hidden" name="_business_action" value="toggle_promotion">
                                                <input type="hidden" name="id" value="{{ $promotion->id }}">
                                                <button class="biz-icon-btn" title="Bật/tắt"><i class="fa fa-power-off"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="biz-empty" colspan="8">Chưa có khuyến mãi.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($activeTab === 'warehouses')
                <div class="biz-section">
                    <form class="biz-form" method="post" action="{{ route('admin.business.store') }}">
                        @csrf
                        <input type="hidden" name="_business_action" value="save_warehouse">
                        <h6>Thêm kho</h6>
                        <div class="biz-field"><label>Tên kho</label><input name="name" value="{{ old('name') }}" required></div>
                        <div class="biz-field"><label>Loại kho</label><select name="type"><option value="NORMAL">Kho thường</option><option value="RETURN">Kho hoàn</option><option value="WARRANTY">Kho bảo hành</option><option value="STORE">Kho cửa hàng</option></select></div>
                        <div class="biz-field"><label>Sức chứa</label><input type="number" min="1" name="capacity" value="{{ old('capacity', 1000) }}" required></div>
                        <div class="biz-field"><label>Tồn tối thiểu</label><input type="number" min="0" name="min_stock_level" value="{{ old('min_stock_level', 10) }}"></div>
                        <div class="biz-field"><label>Địa chỉ</label><textarea name="address_detail">{{ old('address_detail') }}</textarea></div>
                        <button class="biz-btn primary w-100" type="submit"><i class="fa fa-save"></i> Lưu kho</button>
                    </form>

                    <div class="biz-card">
                        <div class="biz-table-head"><h6>Danh sách kho</h6><small>{{ $warehouses->count() }} dòng mới nhất</small></div>
                        <div class="biz-table-wrap">
                            <table class="biz-table">
                                <thead><tr><th>Mã</th><th>Kho</th><th>Loại</th><th>Sức chứa</th><th>Tối thiểu</th><th>Trạng thái</th><th></th></tr></thead>
                                <tbody>
                                @forelse ($warehouses as $warehouse)
                                    @php([$label, $class] = $statusBadge($warehouse->status))
                                    <tr>
                                        <td>{{ $warehouse->warehouse_code }}</td>
                                        <td><div class="biz-name">{{ $warehouse->name }}</div><div class="biz-sub">{{ $warehouse->address_detail ?: '-' }}</div></td>
                                        <td>{{ $warehouseType($warehouse->type) }}</td>
                                        <td class="biz-money">{{ $num($warehouse->capacity) }}</td>
                                        <td>{{ $num($warehouse->min_stock_level) }}</td>
                                        <td><span class="biz-badge {{ $class }}">{{ $label }}</span></td>
                                        <td>
                                            <form class="biz-mini-form" method="post" action="{{ route('admin.business.store') }}">
                                                @csrf
                                                <input type="hidden" name="_business_action" value="toggle_warehouse">
                                                <input type="hidden" name="id" value="{{ $warehouse->id }}">
                                                <button class="biz-icon-btn" title="Bật/tắt"><i class="fa fa-power-off"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="biz-empty" colspan="7">Chưa có kho.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($activeTab === 'stores')
                <div class="p-3">
                    <div class="biz-alert"><i class="fa fa-info-circle"></i> Cửa hàng đang gắn với kho kiểu STORE. Muốn thêm cửa hàng mới, hãy tạo kho cửa hàng trước để tồn kho được theo dõi đúng.</div>
                    <div class="biz-card">
                        <div class="biz-table-head"><h6>Danh sách cửa hàng</h6><small>{{ $stores->count() }} dòng mới nhất</small></div>
                        <div class="biz-table-wrap">
                            <table class="biz-table">
                                <thead><tr><th>Mã</th><th>Cửa hàng</th><th>Kho liên kết</th><th>Điện thoại</th><th>Địa chỉ</th><th>Trạng thái</th></tr></thead>
                                <tbody>
                                @forelse ($stores as $store)
                                    @php([$label, $class] = $statusBadge($store->status))
                                    <tr>
                                        <td>{{ $store->store_code }}</td>
                                        <td><div class="biz-name">{{ $store->name }}</div></td>
                                        <td>{{ $store->warehouse_name ?: '-' }}</td>
                                        <td>{{ $store->phone ?: '-' }}</td>
                                        <td>{{ $store->address_detail ?: '-' }}</td>
                                        <td><span class="biz-badge {{ $class }}">{{ $label }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td class="biz-empty" colspan="6">Chưa có cửa hàng.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($activeTab === 'stock')
                <div class="p-3">
                    <div class="biz-card">
                        <div class="biz-table-head">
                            <h6>Phiếu kho gần đây</h6>
                            <a class="biz-btn primary" href="{{ route('admin.warehouses.create-transaction') }}"><i class="fa fa-plus"></i> Tạo phiếu</a>
                        </div>
                        <div class="biz-table-wrap">
                            <table class="biz-table">
                                <thead><tr><th>Mã phiếu</th><th>Loại</th><th>Luồng kho</th><th>Ngày dự kiến</th><th>Trạng thái</th><th>Ghi chú</th></tr></thead>
                                <tbody>
                                @forelse ($stockTransactions as $transaction)
                                    @php([$label, $class] = $statusBadge($transaction->status))
                                    <tr>
                                        <td><div class="biz-name">{{ $transaction->transaction_code }}</div><div class="biz-sub">{{ $date($transaction->created_at) }}</div></td>
                                        <td>{{ $stockType($transaction->type) }}</td>
                                        <td><div>{{ $transaction->sourceWarehouse?->name ?: 'Nguồn ngoài' }}</div><div class="biz-sub">→ {{ $transaction->targetWarehouse?->name ?: 'Điểm xuất' }}</div></td>
                                        <td>{{ $transaction->expected_date?->format('d/m/Y') ?: '-' }}</td>
                                        <td><span class="biz-badge {{ $class }}">{{ $label }}</span></td>
                                        <td>{{ $transaction->note ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="biz-empty" colspan="6">Chưa có phiếu kho.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
