@extends('admin.layouts.app')

@section('title', $order->order_code ?: 'Đơn hàng #' . $order->id)

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
    $paymentMap = ['COD' => 'Thanh toán khi nhận hàng', 'VNPAY' => 'VNPay'];
    $currentStatus = $statusLabels[$order->status] ?? [$order->status, 'dark', 'fa-question-circle'];
    $nonCancellableStatuses = ['DELIVERING', 'DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED', 'LOST_IN_TRANSIT', 'CANCELLED'];
    $canCancelOrder = ! in_array($order->status, $nonCancellableStatuses, true);
@endphp

@push('styles')
<style>
.aod-page { background:#f5f7fb; min-height:100vh; padding:24px; color:#111827; }
.aod-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:16px; }
.aod-title h4 { margin:0; font-size:22px; font-weight:800; letter-spacing:0; }
.aod-title p { margin:6px 0 0; color:#6b7280; font-size:13px; }
.aod-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
.aod-btn { min-height:38px; border-radius:6px; padding:0 13px; display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid transparent; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; }
.aod-btn.primary { background:#111827; color:#fff; }
.aod-btn.light { background:#fff; color:#374151; border-color:#d1d5db; }
.aod-grid { display:grid; grid-template-columns:minmax(0, 1.45fr) minmax(340px, .85fr); gap:16px; align-items:start; }
.aod-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.aod-card + .aod-card { margin-top:16px; }
.aod-card-head { padding:15px 18px; border-bottom:1px solid #eef0f3; background:#fbfcfd; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.aod-card-head h6 { margin:0; font-size:16px; font-weight:800; color:#111827; }
.aod-card-body { padding:18px; }
.aod-badge { display:inline-flex; align-items:center; gap:6px; min-height:30px; padding:0 11px; border-radius:999px; font-size:12px; font-weight:800; border:1px solid transparent; white-space:nowrap; }
.aod-badge.warning { color:#92400e; background:#fffbeb; border-color:#fde68a; }
.aod-badge.info { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.aod-badge.moving { color:#6d28d9; background:#f5f3ff; border-color:#ddd6fe; }
.aod-badge.success { color:#047857; background:#ecfdf5; border-color:#a7f3d0; }
.aod-badge.danger { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.aod-badge.return { color:#3730a3; background:#eef2ff; border-color:#c7d2fe; }
.aod-badge.dark { color:#374151; background:#f3f4f6; border-color:#d1d5db; }
.aod-badge.lost { color:#9a3412; background:#fff7ed; border-color:#fed7aa; }
.aod-meta { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; margin-bottom:16px; }
.aod-meta-item { border:1px solid #eef0f3; border-radius:8px; padding:12px; background:#fff; }
.aod-meta-item span { display:block; color:#6b7280; font-size:12px; font-weight:700; margin-bottom:5px; }
.aod-meta-item strong { display:block; font-size:14px; line-height:1.35; color:#111827; }
.aod-product { display:grid; grid-template-columns:76px 1fr auto; gap:13px; align-items:center; padding:14px 0; border-bottom:1px solid #eef0f3; }
.aod-product:last-child { border-bottom:0; }
.aod-product img { width:76px; height:76px; border:1px solid #e5e7eb; border-radius:8px; object-fit:cover; background:#f9fafb; }
.aod-product-name { margin:0 0 7px; font-size:14px; font-weight:800; line-height:1.35; }
.aod-product-meta { color:#6b7280; font-size:13px; }
.aod-product-price { text-align:right; min-width:125px; }
.aod-product-price strong { display:block; font-size:16px; color:#111827; }
.aod-form label { display:block; color:#6b7280; font-size:12px; font-weight:800; margin-bottom:6px; }
.aod-select { width:100%; min-height:40px; border:1px solid #d1d5db; border-radius:6px; padding:0 11px; color:#111827; background:#fff; }
.aod-form .aod-btn { margin-top:11px; width:100%; }
.aod-row { display:flex; justify-content:space-between; gap:16px; padding:11px 0; border-bottom:1px solid #eef0f3; font-size:14px; }
.aod-row span:first-child { color:#6b7280; }
.aod-row strong { text-align:right; color:#111827; line-height:1.45; }
.aod-total { border-bottom:0; padding-top:14px; }
.aod-total strong { font-size:22px; }
@media (max-width:1100px) { .aod-page { padding:14px; } .aod-head { flex-direction:column; } .aod-actions { justify-content:flex-start; } .aod-grid { grid-template-columns:1fr; } }
@media (max-width:620px) { .aod-meta { grid-template-columns:1fr; } .aod-product { grid-template-columns:64px 1fr; align-items:start; } .aod-product img { width:64px; height:64px; } .aod-product-price { grid-column:2; text-align:left; min-width:0; } }
</style>
@endpush

@section('content')
<div class="aod-page">
    <div class="aod-head">
        <div class="aod-title">
            <h4>Đơn hàng #{{ $order->id }}</h4>
            <p>Ngày đặt: {{ $order->created_at?->format('d/m/Y H:i') }}</p>
        </div>
        <div class="aod-actions">
            <span class="aod-badge {{ $currentStatus[1] }}"><i class="fas {{ $currentStatus[2] }}"></i>{{ $currentStatus[0] }}</span>
            <a href="{{ route('admin.orders.index') }}" class="aod-btn light"><i class="fas fa-arrow-left"></i> Quay lại</a>
        </div>
    </div>

    <div class="aod-grid">
        <section>
            <div class="aod-card">
                <div class="aod-card-head">
                    <h6>Tổng quan đơn hàng</h6>
                </div>
                <div class="aod-card-body">
                    <div class="aod-meta">
                        <div class="aod-meta-item">
                            <span>Khách hàng</span>
                            <strong>{{ $order->user->full_name ?? $order->recipient_name }}</strong>
                        </div>
                        <div class="aod-meta-item">
                            <span>Số điện thoại</span>
                            <strong>{{ $order->recipient_phone }}</strong>
                        </div>
                        <div class="aod-meta-item">
                            <span>Thanh toán</span>
                            <strong>{{ $paymentMap[$order->payment_method] ?? $order->payment_method }}</strong>
                        </div>
                    </div>

                    @foreach ($order->items as $item)
                        @php($image = $item->product?->image_url ?? asset('upload/no-image.jpg'))
                        <div class="aod-product">
                            <img src="{{ $image }}" alt="{{ $item->product_name }}">
                            <div>
                                <p class="aod-product-name">{{ $item->product_name }}</p>
                                <div class="aod-product-meta">
                                    Số lượng: <strong>x{{ $item->quantity }}</strong>
                                    @if ($item->color_name || $item->lens_size_name)
                                        · {{ $item->color_name }} {{ $item->lens_size_name }}
                                    @endif
                                </div>
                            </div>
                            <div class="aod-product-price">
                                <strong>{{ number_format($item->total_price, 0, ',', '.') }}đ</strong>
                                <small class="text-muted">{{ number_format($item->unit_price, 0, ',', '.') }}đ / sản phẩm</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <aside>
            <div class="aod-card">
                <div class="aod-card-head">
                    <h6>Cập nhật trạng thái</h6>
                </div>
                <div class="aod-card-body">
                    @if (session('success'))
                        <div class="alert alert-success p-2">{{ session('success') }}</div>
                    @endif
                    <form method="post" action="{{ route('admin.orders.status', $order) }}" class="aod-form">
                        @csrf
                        @method('PUT')
                        <label for="status-select">Trạng thái đơn hàng</label>
                        <select name="status" class="aod-select" id="status-select">
                            @foreach ($statusLabels as $value => $meta)
                                @continue($value === 'CANCELLED' && ! $canCancelOrder && $order->status !== 'CANCELLED')
                                <option value="{{ $value }}" @selected($order->status === $value)>{{ $meta[0] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="aod-btn primary">
                            <i class="fas fa-save"></i> Cập nhật trạng thái
                        </button>
                    </form>
                </div>
            </div>

            <div class="aod-card">
                <div class="aod-card-head">
                    <h6>Thông tin giao hàng</h6>
                </div>
                <div class="aod-card-body">
                    <div class="aod-row">
                        <span>Người nhận</span>
                        <strong>{{ $order->recipient_name }}</strong>
                    </div>
                    <div class="aod-row">
                        <span>Số điện thoại</span>
                        <strong>{{ $order->recipient_phone }}</strong>
                    </div>
                    <div class="aod-row">
                        <span>Địa chỉ</span>
                        <strong>{{ $order->shipping_address }}</strong>
                    </div>
                    @if (trim((string) $order->note) !== '')
                        <div class="aod-row">
                            <span>Ghi chú</span>
                            <strong>{{ $order->note }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <div class="aod-card">
                <div class="aod-card-head">
                    <h6>Thanh toán</h6>
                </div>
                <div class="aod-card-body">
                    <div class="aod-row">
                        <span>Tổng tiền hàng</span>
                        <strong>{{ number_format($order->subtotal_amount, 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="aod-row">
                        <span>Phí vận chuyển</span>
                        <strong>{{ (float) $order->shipping_fee > 0 ? number_format($order->shipping_fee, 0, ',', '.') . 'đ' : 'Miễn phí' }}</strong>
                    </div>
                    @if ((float) $order->discount_amount > 0)
                        <div class="aod-row">
                            <span>Giảm giá</span>
                            <strong>-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</strong>
                        </div>
                    @endif
                    <div class="aod-row aod-total">
                        <span>Thành tiền</span>
                        <strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
