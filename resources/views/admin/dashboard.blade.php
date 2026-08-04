@extends('admin.layouts.app')

@section('title', 'Quản trị - ' . config('app.name'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $statusLabels = [
        'PENDING' => ['Chờ xác nhận', 'warning'],
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning'],
        'CONFIRMED' => ['Đã xác nhận', 'primary'],
        'DELIVERING' => ['Đang giao', 'info'],
        'DELIVERED' => ['Đã giao', 'success'],
        'CANCELLED' => ['Đã hủy', 'secondary'],
        'RETURN_PENDING' => ['Đang hoàn/đổi', 'danger'],
        'RETURNED' => ['Đã hoàn', 'dark'],
        'EXCHANGED' => ['Đã đổi', 'dark'],
        'LOST_IN_TRANSIT' => ['Không hoàn tất', 'danger'],
    ];
    $maxSold = max(1, (int) $topCategories->max('sold_quantity'));
@endphp

<style>
.admin-dashboard{background:#f4f7fb;min-height:calc(100vh - 72px);padding:24px;color:#111827}
.admin-dashboard .page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:22px}
.admin-dashboard .page-kicker{color:#0f766e;font-size:13px;font-weight:800;letter-spacing:.04em;margin-bottom:6px;text-transform:uppercase}
.admin-dashboard .page-title{color:#101828;font-size:28px;font-weight:800;line-height:1.2;margin:0}
.admin-dashboard .page-subtitle{color:#667085;font-size:14px;margin:8px 0 0;max-width:640px}
.admin-dashboard .head-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end}
.admin-dashboard .dash-btn{align-items:center;border-radius:8px;display:inline-flex;font-size:14px;font-weight:800;gap:8px;min-height:40px;padding:0 14px;text-decoration:none}
.admin-dashboard .dash-btn.primary{background:#0f766e;color:#fff}
.admin-dashboard .dash-btn.light{background:#fff;border:1px solid #d0d5dd;color:#344054}
.admin-dashboard .metric-grid{display:grid;gap:16px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}
.admin-dashboard .metric-card,.admin-dashboard .panel{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04)}
.admin-dashboard .metric-card{min-height:150px;padding:18px}
.admin-dashboard .metric-top{align-items:center;display:flex;justify-content:space-between;margin-bottom:18px}
.admin-dashboard .metric-icon{align-items:center;border-radius:8px;display:inline-flex;height:42px;justify-content:center;width:42px}
.admin-dashboard .metric-icon.teal{background:#ccfbf1;color:#0f766e}.admin-dashboard .metric-icon.blue{background:#dbeafe;color:#1d4ed8}.admin-dashboard .metric-icon.amber{background:#fef3c7;color:#b45309}.admin-dashboard .metric-icon.rose{background:#ffe4e6;color:#be123c}.admin-dashboard .metric-icon.slate{background:#e2e8f0;color:#334155}.admin-dashboard .metric-icon.green{background:#dcfce7;color:#15803d}.admin-dashboard .metric-icon.indigo{background:#e0e7ff;color:#4338ca}.admin-dashboard .metric-icon.orange{background:#ffedd5;color:#c2410c}
.admin-dashboard .metric-label{color:#667085;font-size:13px;font-weight:800;margin:0}
.admin-dashboard .metric-value{color:#101828;font-size:28px;font-weight:900;line-height:1;margin:0 0 10px}
.admin-dashboard .metric-note{color:#667085;font-size:13px;margin:0}
.admin-dashboard .layout-grid{display:grid;gap:16px;grid-template-columns:minmax(0,1.45fr) minmax(320px,.8fr);margin-bottom:18px}
.admin-dashboard .panel{padding:18px}
.admin-dashboard .panel-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;margin:-2px 0 16px;padding-bottom:14px}
.admin-dashboard .panel-title{color:#101828;font-size:17px;font-weight:900;margin:0}
.admin-dashboard .panel-link{color:#0f766e;font-size:13px;font-weight:900;text-decoration:none}
.admin-dashboard .chart-wrap{height:320px;position:relative}
.admin-dashboard .table{margin:0}.admin-dashboard .table th{border-top:0;color:#667085;font-size:12px;font-weight:900;text-transform:uppercase}.admin-dashboard .table td{color:#344054;font-size:14px;vertical-align:middle}
.admin-dashboard .status-pill{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;line-height:1;padding:7px 10px;white-space:nowrap}
.admin-dashboard .status-pill.warning{background:#fff7ed;color:#b45309}.admin-dashboard .status-pill.primary{background:#eff6ff;color:#1d4ed8}.admin-dashboard .status-pill.info{background:#ecfeff;color:#0e7490}.admin-dashboard .status-pill.success{background:#ecfdf3;color:#067647}.admin-dashboard .status-pill.secondary{background:#f2f4f7;color:#475467}.admin-dashboard .status-pill.danger{background:#fff1f2;color:#be123c}.admin-dashboard .status-pill.dark{background:#f1f5f9;color:#0f172a}
.admin-dashboard .work-list{display:grid;gap:12px}.admin-dashboard .work-item{align-items:center;background:#f8fafc;border:1px solid #eef2f6;border-radius:8px;display:flex;gap:12px;justify-content:space-between;padding:12px;text-decoration:none}
.admin-dashboard .work-title{color:#101828;font-size:14px;font-weight:900;margin:0 0 3px}.admin-dashboard .work-meta{color:#667085;font-size:12px;margin:0}.admin-dashboard .work-count{color:#be123c;font-size:20px;font-weight:900;min-width:44px;text-align:right}
.admin-dashboard .category-row{align-items:center;display:grid;gap:12px;grid-template-columns:minmax(0,1fr) 90px;margin-bottom:14px}.admin-dashboard .bar{background:#e4e7ec;border-radius:999px;height:8px;margin-top:7px;overflow:hidden}.admin-dashboard .bar span{background:#0f766e;border-radius:inherit;display:block;height:100%;min-width:4px}
.admin-dashboard .empty-state{align-items:center;color:#667085;display:flex;font-size:14px;justify-content:center;min-height:120px;text-align:center}
@media(max-width:1200px){.admin-dashboard .metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-dashboard .layout-grid{grid-template-columns:1fr}}@media(max-width:768px){.admin-dashboard{padding:16px}.admin-dashboard .page-head{display:block}.admin-dashboard .head-actions{justify-content:flex-start;margin-top:14px}.admin-dashboard .metric-grid{grid-template-columns:1fr}.admin-dashboard .chart-wrap{height:260px}}
</style>

<div class="admin-dashboard">
    <div class="page-head">
        <div>
            <div class="page-kicker">Mắt Kính Admin</div>
            <h1 class="page-title">Tổng quan vận hành cửa hàng</h1>
            <p class="page-subtitle">Theo dõi doanh thu, đơn hàng, tồn kho gọng kính/tròng kính và yêu cầu hoàn đổi.</p>
        </div>
        <div class="head-actions">
            <a class="dash-btn light" href="{{ route('admin.warehouses.index') }}"><i class="fas fa-warehouse"></i> Kho hàng</a>
            <a class="dash-btn light" href="{{ route('admin.orders.unconfirmed') }}"><i class="fas fa-clock"></i> Đơn chờ</a>
            <a class="dash-btn primary" href="{{ route('admin.products.create') }}"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
        </div>
    </div>

    <div class="metric-grid">
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Doanh thu đã giao</p><span class="metric-icon teal"><i class="fas fa-wallet"></i></span></div><p class="metric-value">{{ $money($orderStats->total_revenue) }}</p><p class="metric-note">Tháng này: {{ $money($orderStats->month_revenue) }}</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Đơn cần xử lý</p><span class="metric-icon amber"><i class="fas fa-clipboard-check"></i></span></div><p class="metric-value">{{ $num($orderStats->pending_orders) }}</p><p class="metric-note">Hôm nay có {{ $num($orderStats->today_orders) }} đơn mới</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Sản phẩm đang bán</p><span class="metric-icon blue"><i class="fas fa-glasses"></i></span></div><p class="metric-value">{{ $num($activeProducts) }}</p><p class="metric-note">{{ $num($totalVariants) }} biến thể màu/size</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Tồn kho khả dụng</p><span class="metric-icon green"><i class="fas fa-boxes"></i></span></div><p class="metric-value">{{ $num($availableStock) }}</p><p class="metric-note">{{ $num($lowStockCount) }} mẫu đang chạm ngưỡng thấp</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Hoàn/đổi chờ duyệt</p><span class="metric-icon rose"><i class="fas fa-exchange-alt"></i></span></div><p class="metric-value">{{ $num($returnStats->pending_returns) }}</p><p class="metric-note">{{ $num($returnStats->return_only) }} hoàn, {{ $num($returnStats->exchange_only) }} đổi</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Khách hàng hoạt động</p><span class="metric-icon indigo"><i class="fas fa-users"></i></span></div><p class="metric-value">{{ $num($activeCustomers) }}</p><p class="metric-note">Không tính tài khoản quản trị</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Danh mục kính</p><span class="metric-icon orange"><i class="fas fa-tags"></i></span></div><p class="metric-value">{{ $num($activeCategories) }}</p><p class="metric-note">{{ $num($activeBrands) }} thương hiệu đang bật</p></div>
        <div class="metric-card"><div class="metric-top"><p class="metric-label">Đơn đang giao</p><span class="metric-icon slate"><i class="fas fa-shipping-fast"></i></span></div><p class="metric-value">{{ $num($orderStats->delivering_orders) }}</p><p class="metric-note">{{ $num($orderStats->confirmed_orders) }} đơn đã xác nhận</p></div>
    </div>

    <div class="layout-grid">
        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Đơn hàng và doanh thu 7 ngày</h2><a class="panel-link" href="{{ route('admin.reports.orders') }}">Xem báo cáo</a></div>
            @if ($chartLabels)
                <div class="chart-wrap"><canvas id="dashboardSalesChart"></canvas></div>
            @else
                <div class="empty-state">Chưa có dữ liệu đơn hàng để vẽ biểu đồ.</div>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Việc cần xử lý</h2><a class="panel-link" href="{{ route('admin.orders.unconfirmed') }}">Mở đơn chờ</a></div>
            <div class="work-list">
                <a class="work-item" href="{{ route('admin.orders.unconfirmed') }}"><div><p class="work-title">Đơn chờ xác nhận</p><p class="work-meta">Kiểm tra thanh toán, địa chỉ và chuẩn bị giao</p></div><div class="work-count">{{ $num($orderStats->pending_orders) }}</div></a>
                <a class="work-item" href="{{ route('admin.returns.index') }}"><div><p class="work-title">Yêu cầu hoàn/đổi</p><p class="work-meta">Duyệt hàng trả, đổi biến thể và cập nhật kho</p></div><div class="work-count">{{ $num($returnStats->pending_returns) }}</div></a>
                <a class="work-item" href="{{ route('admin.warehouses.index') }}"><div><p class="work-title">Biến thể sắp hết hàng</p><p class="work-meta">Ưu tiên nhập thêm gọng, màu hoặc size bán chạy</p></div><div class="work-count">{{ $num($lowStockCount) }}</div></a>
            </div>
        </div>
    </div>

    <div class="layout-grid">
        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Đơn hàng mới nhất</h2><a class="panel-link" href="{{ route('admin.orders.index') }}">Quản lý đơn</a></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Thanh toán</th><th>Trạng thái</th><th class="text-end">Tổng tiền</th></tr></thead>
                    <tbody>
                    @forelse ($latestOrders as $order)
                        @php($status = $statusLabels[$order->status] ?? [$order->status, 'secondary'])
                        <tr>
                            <td><strong>{{ $order->order_code }}</strong><br><small class="text-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</small></td>
                            <td>{{ $order->user->full_name ?? $order->recipient_name }}</td>
                            <td>{{ $order->payment_method }}<br><small class="text-muted">{{ $order->payment_status }}</small></td>
                            <td><span class="status-pill {{ $status[1] }}">{{ $status[0] }}</span></td>
                            <td class="text-end"><strong>{{ $money($order->total_amount) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Chưa có đơn hàng nào.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Danh mục bán tốt</h2><a class="panel-link" href="{{ route('admin.reports.products') }}">Xem thống kê</a></div>
            @forelse ($topCategories as $category)
                @php($percent = min(100, round(((int) $category->sold_quantity / $maxSold) * 100)))
                <div class="category-row">
                    <div><strong>{{ $category->name }}</strong><div class="bar"><span style="width: {{ $percent }}%"></span></div><small class="text-muted">{{ $num($category->product_count) }} sản phẩm</small></div>
                    <div class="text-end"><strong>{{ $num($category->sold_quantity) }}</strong><br><small class="text-muted">đã bán</small></div>
                </div>
            @empty
                <div class="empty-state">Chưa có dữ liệu danh mục.</div>
            @endforelse
        </div>
    </div>

    <div class="layout-grid">
        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Tồn kho cần chú ý</h2><a class="panel-link" href="{{ route('admin.warehouses.index') }}">Vào kho hàng</a></div>
            @if ($lowStockItems->count())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Sản phẩm</th><th>Biến thể</th><th class="text-end">Còn lại</th></tr></thead>
                        <tbody>
                        @foreach ($lowStockItems as $item)
                            <tr><td>{{ $item->product_name }}</td><td>{{ trim(($item->color_name ?: 'Màu') . ' / ' . ($item->lens_size ?: 'Size')) }}</td><td class="text-end"><strong>{{ $num($item->available_stock) }}</strong> <small class="text-muted">/ ngưỡng {{ $num($item->min_stock_level) }}</small></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">Tồn kho đang ổn, chưa có biến thể chạm ngưỡng thấp.</div>
            @endif
        </div>

        <div class="panel">
            <div class="panel-head"><h2 class="panel-title">Hoàn/đổi mới</h2><a class="panel-link" href="{{ route('admin.returns.index') }}">Xử lý</a></div>
            @if ($pendingReturns->count())
                <div class="work-list">
                    @foreach ($pendingReturns as $request)
                        <a class="work-item" href="{{ route('admin.returns.show', $request) }}"><div><p class="work-title">{{ $request->return_code }} - {{ $request->user->full_name ?? '-' }}</p><p class="work-meta">{{ $request->order->order_code ?? '-' }} · {{ $request->type === 'EXCHANGE' ? 'Đổi hàng' : 'Hoàn hàng' }} · {{ $request->requested_at?->format('d/m/Y H:i') }}</p></div><span class="status-pill warning">Chờ duyệt</span></a>
                    @endforeach
                </div>
            @else
                <div class="empty-state">Không có yêu cầu hoàn/đổi đang chờ.</div>
            @endif
        </div>
    </div>
</div>

@if ($chartLabels)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('dashboardSalesChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { type: 'bar', label: 'Số đơn', data: @json($chartOrders), backgroundColor: 'rgba(15,118,110,.18)', borderColor: '#0f766e', borderWidth: 2, borderRadius: 6, yAxisID: 'orders' },
                { type: 'line', label: 'Doanh thu', data: @json($chartRevenue), borderColor: '#be123c', backgroundColor: 'rgba(190,18,60,.08)', borderWidth: 3, pointRadius: 4, tension: .35, fill: true, yAxisID: 'revenue' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
@endpush
@endif
@endsection
