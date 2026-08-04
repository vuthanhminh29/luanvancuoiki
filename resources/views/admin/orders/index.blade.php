@extends('admin.layouts.app')

@section('title', $isUnconfirmed ? 'Đơn hàng chờ xác nhận' : 'Đơn hàng')

@php
    $statusLabels = [
        'PENDING' => ['Chờ xác nhận', 'warning', 'fa-clock'],
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning', 'fa-credit-card'],
        'CONFIRMED' => ['Đã xác nhận', 'info', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'moving', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'success', 'fa-check-circle'],
        'CANCELLED' => ['Đã hủy', 'danger', 'fa-times-circle'],
        'RETURN_PENDING' => ['Chờ hoàn/đổi', 'return', 'fa-rotate-left'],
        'RETURNED' => ['Đã hoàn trả', 'dark', 'fa-undo'],
        'EXCHANGED' => ['Đã đổi hàng', 'success', 'fa-exchange-alt'],
        'LOST_IN_TRANSIT' => ['Mất hàng khi giao', 'lost', 'fa-exclamation-triangle'],
    ];
    $statusOptions = collect($statusLabels)->map(fn ($meta) => $meta[0])->all();
    $paymentLabels = ['COD' => 'COD', 'VNPAY' => 'VNPay'];
    $nonCancellableStatuses = ['DELIVERING', 'DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED', 'LOST_IN_TRANSIT', 'CANCELLED'];
    $paymentBadge = function ($method) {
        return match ($method) {
            'VNPAY' => ['VNPay', 'vnpay'],
            'COD' => ['COD', 'cod'],
            default => [$method ?: '-', 'other'],
        };
    };
@endphp

@push('styles')
<style>
.ao-page { background:#f5f7fb; min-height:100vh; padding:24px; color:#111827; }
.ao-shell { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.ao-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:20px 22px; border-bottom:1px solid #e5e7eb; }
.ao-title h4 { margin:0; font-size:22px; font-weight:800; letter-spacing:0; }
.ao-title p { margin:6px 0 0; color:#6b7280; font-size:13px; }
.ao-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
.ao-btn { min-height:38px; border-radius:6px; padding:0 13px; display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid transparent; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; background:none; }
.ao-btn.primary { background:#111827; color:#fff; }
.ao-btn.light { background:#fff; color:#374151; border-color:#d1d5db; }
.ao-btn.soft { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.ao-btn.danger { background:#fff1f2; color:#be123c; border-color:#fecdd3; }
.ao-stats { display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); gap:12px; padding:18px 22px; border-bottom:1px solid #eef0f3; background:#fbfcfd; }
.ao-stat { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:13px; }
.ao-stat span { display:block; color:#6b7280; font-size:12px; font-weight:700; }
.ao-stat strong { display:block; margin-top:4px; font-size:22px; color:#111827; }
.ao-filter { display:grid; grid-template-columns:1fr 1fr 1.5fr 1fr 1fr auto auto; gap:10px; padding:18px 22px; border-bottom:1px solid #eef0f3; align-items:end; }
.ao-field label { display:block; margin:0 0 5px; color:#6b7280; font-size:12px; font-weight:800; }
.ao-input, .ao-select { width:100%; min-height:38px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; padding:0 10px; font-size:13px; }
.ao-result { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 22px; border-bottom:1px solid #eef0f3; color:#6b7280; font-size:13px; }
.ao-result strong { color:#111827; }
.ao-result span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ao-table-wrap { overflow-x:auto; padding:0 22px 22px; }
.ao-table { width:100%; border-collapse:collapse; min-width:960px; }
.ao-table th { padding:13px 10px; color:#6b7280; background:#fff; border-bottom:1px solid #e5e7eb; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
.ao-table td { padding:13px 10px; border-bottom:1px solid #f1f3f5; vertical-align:middle; font-size:14px; color:#111827; }
.ao-table tr:hover td { background:#fafafa; }
.ao-id { display:inline-flex; align-items:center; gap:6px; color:#111827; font-weight:800; text-decoration:none; }
.ao-id:hover { color:#2563eb; }
.ao-customer strong { display:block; font-size:14px; }
.ao-customer span { display:block; margin-top:3px; color:#6b7280; font-size:12px; }
.ao-money { font-weight:800; white-space:nowrap; text-align:right; }
.ao-date { color:#4b5563; white-space:nowrap; }
.ao-badge { display:inline-flex; align-items:center; gap:6px; min-height:28px; padding:0 10px; border-radius:999px; font-size:12px; font-weight:800; border:1px solid transparent; white-space:nowrap; }
.ao-badge.warning { color:#92400e; background:#fffbeb; border-color:#fde68a; }
.ao-badge.info { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.ao-badge.moving { color:#6d28d9; background:#f5f3ff; border-color:#ddd6fe; }
.ao-badge.success { color:#047857; background:#ecfdf5; border-color:#a7f3d0; }
.ao-badge.danger { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.ao-badge.return { color:#3730a3; background:#eef2ff; border-color:#c7d2fe; }
.ao-badge.dark { color:#374151; background:#f3f4f6; border-color:#d1d5db; }
.ao-badge.lost { color:#9a3412; background:#fff7ed; border-color:#fed7aa; }
.ao-payment { display:inline-flex; min-height:26px; align-items:center; padding:0 9px; border-radius:999px; font-size:12px; font-weight:800; }
.ao-payment.cod { background:#e0f2fe; color:#075985; }
.ao-payment.vnpay { background:#dcfce7; color:#166534; }
.ao-payment.other { background:#f3f4f6; color:#4b5563; }
.ao-row-actions { display:flex; gap:7px; justify-content:flex-end; }
.ao-row-actions form { margin:0; }
.ao-empty { padding:40px 16px; text-align:center; color:#6b7280; }
@media (max-width:1000px) { .ao-page { padding:14px; } .ao-head { flex-direction:column; } .ao-actions { justify-content:flex-start; } .ao-stats { grid-template-columns:repeat(2, minmax(0, 1fr)); } .ao-filter { grid-template-columns:1fr 1fr; } }
@media (max-width:620px) { .ao-stats, .ao-filter { grid-template-columns:1fr; } .ao-result { align-items:flex-start; flex-direction:column; } .ao-btn { width:100%; } }
</style>
@endpush

@section('content')
<div class="ao-page">
    <div class="ao-shell">
        <div class="ao-head">
            <div class="ao-title">
                <h4>{{ $isUnconfirmed ? 'Đơn hàng chờ xác nhận' : 'Quản lý đơn hàng' }}</h4>
                <p>{{ $isUnconfirmed ? 'Các đơn mới cần kiểm tra trước khi chuyển sang xử lý.' : 'Theo dõi, kiểm tra và cập nhật trạng thái đơn hàng của khách.' }}</p>
            </div>
            <div class="ao-actions">
                @if ($isUnconfirmed)
                    <a href="{{ route('admin.orders.index') }}" class="ao-btn light"><i class="fas fa-list"></i> Tất cả đơn</a>
                @else
                    <a href="{{ route('admin.orders.unconfirmed') }}" class="ao-btn light"><i class="fas fa-clock"></i> Đơn chờ</a>
                    <a href="{{ route('admin.reports.orders') }}" class="ao-btn primary"><i class="fas fa-download"></i> Xuất Excel</a>
                @endif
            </div>
        </div>

        @unless ($isUnconfirmed)
            <div class="ao-stats">
                <div class="ao-stat"><span>Tổng đơn</span><strong>{{ number_format($summary['total']) }}</strong></div>
                <div class="ao-stat"><span>Chờ xác nhận</span><strong>{{ number_format($summary['pending']) }}</strong></div>
                <div class="ao-stat"><span>Đang giao</span><strong>{{ number_format($summary['shipping']) }}</strong></div>
                <div class="ao-stat"><span>Đã giao</span><strong>{{ number_format($summary['completed']) }}</strong></div>
                <div class="ao-stat"><span>Hoàn/đổi</span><strong>{{ number_format($summary['returning']) }}</strong></div>
            </div>

            <form class="ao-filter" method="get">
                <div class="ao-field">
                    <label>Trạng thái</label>
                    <select name="status" class="ao-select">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ao-field">
                    <label>Thanh toán</label>
                    <select name="payment_method" class="ao-select">
                        <option value="">Tất cả phương thức</option>
                        @foreach ($paymentLabels as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['payment_method'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ao-field">
                    <label>Tìm kiếm</label>
                    <input type="text" name="keyword" class="ao-input" placeholder="Mã đơn, tên, email, số điện thoại..." value="{{ $filters['keyword'] ?? '' }}">
                </div>
                <div class="ao-field">
                    <label>Từ ngày</label>
                    <input type="date" name="date_from" class="ao-input" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="ao-field">
                    <label>Đến ngày</label>
                    <input type="date" name="date_to" class="ao-input" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <button class="ao-btn primary" type="submit"><i class="fas fa-search"></i> Tìm / lọc</button>
                <a class="ao-btn light" href="{{ route('admin.orders.index') }}">Xóa lọc</a>
            </form>
        @endunless

        <div class="ao-result">
            <span>Đang hiển thị <strong>{{ number_format($orders->count()) }}</strong> đơn hàng</span>
            @if (($filters['keyword'] ?? '') !== '')
                <span>Từ khóa: <strong>{{ $filters['keyword'] }}</strong></span>
            @endif
        </div>

        <div class="ao-table-wrap">
            <table class="ao-table" id="orders-list">
                <thead>
                    <tr>
                        <th style="width:72px;">STT</th>
                        <th style="width:145px;">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th style="width:155px;">Ngày đặt</th>
                        <th style="width:140px;text-align:right;">Tổng tiền</th>
                        <th style="width:120px;">Thanh toán</th>
                        <th style="width:175px;">Trạng thái</th>
                        <th style="width:220px;text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $index => $order)
                        @php
                            $meta = $statusLabels[$order->status] ?? [$order->status, 'dark', 'fa-question-circle'];
                            $payment = $paymentBadge($order->payment_method);
                        @endphp
                        <tr>
                            <td>{{ $orders->firstItem() + $index }}</td>
                            <td>
                                <a class="ao-id" href="{{ route('admin.orders.show', $order) }}">
                                    <i class="fas fa-receipt"></i> {{ $order->order_code ?: '#' . $order->id }}
                                </a>
                            </td>
                            <td class="ao-customer">
                                <strong>{{ $order->user->full_name ?? $order->recipient_name }}</strong>
                                <span>{{ $order->user->email ?? $order->recipient_phone }}</span>
                            </td>
                            <td class="ao-date">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="ao-money">{{ number_format($order->total_amount, 0, ',', '.') }}đ</td>
                            <td><span class="ao-payment {{ $payment[1] }}">{{ $payment[0] }}</span></td>
                            <td><span class="ao-badge {{ $meta[1] }}"><i class="fas {{ $meta[2] }}"></i>{{ $meta[0] }}</span></td>
                            <td>
                                <div class="ao-row-actions">
                                    <a class="ao-btn soft" href="{{ route('admin.orders.show', $order) }}"><i class="fas fa-eye"></i> Xem</a>
                                    <a class="ao-btn light" href="{{ route('admin.orders.show', $order) }}"><i class="fas fa-pen"></i> Sửa</a>
                                    @if (! $isUnconfirmed && ! in_array($order->status, $nonCancellableStatuses, true))
                                        <form method="post" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?');">
                                            @csrf
                                            @method('PATCH')
                                            <button class="ao-btn danger" type="submit"><i class="fas fa-times-circle"></i> Hủy</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="ao-empty">{{ $isUnconfirmed ? 'Không có đơn chờ xác nhận.' : 'Không có đơn hàng phù hợp.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div style=padding-top:16px;>{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
