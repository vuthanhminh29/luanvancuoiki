@extends('admin.layouts.app')

@section('title', $isUnconfirmed ? 'Đơn hàng chờ xác nhận' : 'Đơn hàng')

@php
    $statusLabels = $statusLabels ?? [
        'PENDING' => ['Chờ xác nhận', 'warning', 'fa-clock'],
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning', 'fa-credit-card'],
        'CONFIRMED' => ['Đã xác nhận', 'info', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'moving', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'success', 'fa-check-circle'],
        'CANCELLED' => ['Đã hủy', 'danger', 'fa-times-circle'],
        'RETURN_PENDING' => ['Chờ hoàn/đổi', 'return', 'fa-undo-alt'],
        'RETURNED' => ['Đã hoàn trả', 'dark', 'fa-undo'],
        'EXCHANGED' => ['Đã đổi hàng', 'success', 'fa-exchange-alt'],
    ];
    $statusOptions = $statusOptions ?? collect($statusLabels)->map(fn ($meta) => $meta[0])->all();
    $paymentLabels = ['COD' => 'COD', 'VNPAY' => 'VNPay'];
    $nonCancellableStatuses = ['DELIVERING', 'DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED', 'CANCELLED'];
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
.ao-page{background:#f4f7fb;color:#111827;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.ao-inner{max-width:1500px;margin:0 auto}.ao-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.ao-title small{color:#2563eb;font-size:13px;font-weight:900;text-transform:uppercase}.ao-title h4{font-size:28px;font-weight:900;line-height:1.18;margin:6px 0}.ao-title p{color:#667085;font-size:14px;margin:0}.ao-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}.ao-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.ao-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.ao-btn.soft{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.ao-btn.danger{background:#fff1f2;border-color:#fecdd3;color:#be123c}.ao-btn:hover{filter:brightness(.98);color:#111827}.ao-btn.primary:hover{color:#fff}
.ao-stats{display:grid;gap:12px;grid-template-columns:repeat(5,minmax(0,1fr));margin-bottom:14px}.ao-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.ao-stat i{align-items:center;border-radius:8px;display:inline-flex;height:34px;justify-content:center;width:34px}.ao-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.ao-stat strong{display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.ao-stat:nth-child(1) i{background:#eef2ff;color:#4f46e5}.ao-stat:nth-child(2) i{background:#fffbeb;color:#92400e}.ao-stat:nth-child(3) i{background:#f5f3ff;color:#6d28d9}.ao-stat:nth-child(4) i{background:#dcfce7;color:#166534}.ao-stat:nth-child(5) i{background:#eef2ff;color:#3730a3}
.ao-cancel-alerts{display:grid;gap:8px;margin-bottom:14px}.ao-cancel-alert{align-items:center;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;color:#9a3412;display:flex;font-size:13px;font-weight:800;gap:10px;justify-content:space-between;line-height:1.45;padding:12px 14px}.ao-cancel-alert strong{color:#7c2d12}.ao-cancel-alert .ao-btn{background:#fff;border-color:#fdba74;color:#9a3412;min-height:34px}
.ao-panel{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.ao-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:10px;grid-template-columns:minmax(150px,.7fr) minmax(140px,.6fr) minmax(240px,1fr) minmax(135px,.55fr) minmax(135px,.55fr) auto;padding:14px}.ao-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:6px;text-transform:uppercase}.ao-input,.ao-select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.ao-result{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.ao-result h6{font-size:16px;font-weight:900;margin:0}.ao-result span{color:#667085;font-size:12px;font-weight:800}
.ao-list-head,.ao-row{align-items:center;display:grid;gap:10px;grid-template-columns:40px minmax(190px,.9fr) minmax(230px,1.15fr) 145px 95px 185px 125px 178px;min-width:0}.ao-list-head{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:0;padding:10px 14px;text-transform:uppercase}.ao-row{border-bottom:1px solid #f1f5f9;font-size:13px;min-height:78px;padding:12px 14px}.ao-row:hover{background:#fafafa}.ao-id{align-items:center;color:#111827;display:inline-flex;font-weight:900;font-size:12px;letter-spacing:0;gap:6px;text-decoration:none;word-break:break-all}.ao-id:hover{color:#2563eb}.ao-customer strong{display:block;font-size:13px;font-weight:900;line-height:1.35;word-break:break-word}.ao-customer span{color:#667085;display:block;font-size:12px;font-weight:700;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ao-date{color:#344054;font-weight:800;white-space:nowrap}.ao-money{font-weight:900;text-align:right;white-space:nowrap}.ao-badge{align-items:center;border:1px solid transparent;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;gap:6px;min-height:28px;padding:0 10px;white-space:nowrap}.ao-badge.warning{background:#fffbeb;border-color:#fde68a;color:#92400e}.ao-badge.info{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.ao-badge.moving{background:#f5f3ff;border-color:#ddd6fe;color:#6d28d9}.ao-badge.success{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.ao-badge.danger{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.ao-badge.return{background:#eef2ff;border-color:#c7d2fe;color:#3730a3}.ao-badge.dark{background:#f3f4f6;border-color:#d1d5db;color:#374151}.ao-payment{align-items:center;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:26px;padding:0 9px}.ao-payment.cod{background:#e0f2fe;color:#075985}.ao-payment.vnpay{background:#dcfce7;color:#166534}.ao-payment.other{background:#f3f4f6;color:#4b5563}.ao-row-actions{display:flex;gap:7px;justify-content:flex-end}.ao-row-actions .ao-btn{min-height:34px;padding:0 11px}.ao-row-actions form{margin:0}.ao-empty{color:#667085;font-size:14px;font-weight:700;padding:40px 16px;text-align:center}.ao-table-wrap{overflow-x:auto}.ao-pagination{padding:14px}
@media(max-width:1180px){.ao-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.ao-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.ao-filter .ao-actions{grid-column:1/-1;justify-content:flex-start}.ao-row-actions{justify-content:flex-start}}@media(max-width:760px){.ao-page{margin:-24px -12px 0;padding:16px 12px}.ao-head{flex-direction:column}.ao-actions,.ao-btn{width:100%}.ao-stats,.ao-filter{grid-template-columns:1fr}.ao-result{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="ao-page">
    <div class="ao-inner">
        <div class="ao-head">
            <div class="ao-title">
                <small>{{ $isUnconfirmed ? 'Cần xử lý' : 'Quản trị vận hành' }}</small>
                <h4>{{ $isUnconfirmed ? 'Đơn hàng chờ xác nhận' : 'Quản lý đơn hàng' }}</h4>
                <p>{{ $isUnconfirmed ? 'Kiểm tra thông tin thanh toán và địa chỉ trước khi xác nhận đơn.' : 'Theo dõi đơn hàng, cập nhật trạng thái giao hàng và xử lý yêu cầu sau bán.' }}</p>
            </div>
            <div class="ao-actions">
                @if ($isUnconfirmed)
                    <a href="{{ route('admin.orders.index') }}" class="ao-btn"><i class="fas fa-list"></i> Tất cả đơn</a>
                @else
                    <a href="{{ route('admin.orders.unconfirmed') }}" class="ao-btn"><i class="fas fa-clock"></i> Đơn chờ</a>
                    <a href="{{ route('admin.reports.orders') }}" class="ao-btn primary"><i class="fas fa-chart-bar"></i> Báo cáo đơn</a>
                @endif
            </div>
        </div>

        @if (! $isUnconfirmed && ($customerCancellationRequests ?? collect())->isNotEmpty())
            <div class="ao-cancel-alerts">
                @foreach ($customerCancellationRequests as $requestOrder)
                    <div class="ao-cancel-alert">
                        <div>
                            <i class="fas fa-exclamation-circle"></i>
                            Có đơn hàng mã đơn <strong>{{ $requestOrder->order_code ?: '#' . $requestOrder->id }}</strong> khách hàng hủy, hãy xem.
                        </div>
                        <a class="ao-btn" href="{{ route('admin.orders.show', $requestOrder) }}"><i class="fas fa-eye"></i> Xem</a>
                    </div>
                @endforeach
            </div>
        @endif

        @unless ($isUnconfirmed)
            <div class="ao-stats">
                <div class="ao-stat"><i class="fas fa-receipt"></i><span>Tổng đơn</span><strong>{{ number_format($summary['total']) }}</strong></div>
                <div class="ao-stat"><i class="fas fa-clock"></i><span>Chờ xác nhận</span><strong>{{ number_format($summary['pending']) }}</strong></div>
                <div class="ao-stat"><i class="fas fa-truck"></i><span>Đang giao</span><strong>{{ number_format($summary['shipping']) }}</strong></div>
                <div class="ao-stat"><i class="fas fa-check-circle"></i><span>Đã giao</span><strong>{{ number_format($summary['completed']) }}</strong></div>
                <div class="ao-stat"><i class="fas fa-undo-alt"></i><span>Hoàn/đổi</span><strong>{{ number_format($summary['returning']) }}</strong></div>
            </div>
        @endunless

        <div class="ao-panel">
            @unless ($isUnconfirmed)
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
                    <div class="ao-actions">
                        <button class="ao-btn primary" type="submit"><i class="fas fa-search"></i> Lọc</button>
                        <a class="ao-btn" href="{{ route('admin.orders.index') }}"><i class="fas fa-redo"></i> Xóa lọc</a>
                    </div>
                </form>
            @endunless

            <div class="ao-result">
                <h6>{{ $isUnconfirmed ? 'Danh sách đơn chờ' : 'Danh sách đơn hàng' }}</h6>
                <span>Hiển thị {{ number_format($orders->count()) }} / {{ number_format($orders->total()) }} đơn</span>
            </div>

            <div class="ao-table-wrap">
                <div class="ao-list-head">
                    <div>STT</div>
                    <div>Mã đơn</div>
                    <div>Khách hàng</div>
                    <div>Ngày đặt</div>
                    <div>Thanh toán</div>
                    <div>Trạng thái</div>
                    <div style="text-align:right;">Tổng tiền</div>
                    <div style="text-align:right;">Thao tác</div>
                </div>

                @forelse ($orders as $index => $order)
                    @php
                        $meta = $statusLabels[$order->status] ?? [$order->status, 'dark', 'fa-question-circle'];
                        $payment = $paymentBadge($order->payment_method);
                    @endphp
                    <div class="ao-row">
                        <div>{{ $orders->firstItem() + $index }}</div>
                        <div>
                            <a class="ao-id" href="{{ route('admin.orders.show', $order) }}">
                                <i class="fas fa-receipt"></i> {{ $order->order_code ?: '#' . $order->id }}
                            </a>
                        </div>
                        <div class="ao-customer">
                            <strong>{{ $order->user->full_name ?? $order->recipient_name }}</strong>
                            <span>{{ $order->user->email ?? $order->recipient_phone }}</span>
                        </div>
                        <div class="ao-date">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                        <div><span class="ao-payment {{ $payment[1] }}">{{ $payment[0] }}</span></div>
                        <div><span class="ao-badge {{ $meta[1] }}"><i class="fas {{ $meta[2] }}"></i>{{ $meta[0] }}</span></div>
                        <div class="ao-money">{{ number_format($order->total_amount, 0, ',', '.') }}đ</div>
                        <div class="ao-row-actions">
                            <a class="ao-btn soft" href="{{ route('admin.orders.show', $order) }}"><i class="fas fa-eye"></i> Xem</a>
                            @if (! $isUnconfirmed && ! in_array($order->status, $nonCancellableStatuses, true))
                                <form method="post" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Bạn có chắc muốn hủy đơn này?');">
                                    @csrf
                                    @method('PATCH')
                                    <button class="ao-btn danger" type="submit"><i class="fas fa-times-circle"></i> Hủy</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="ao-empty">{{ $isUnconfirmed ? 'Không có đơn chờ xác nhận.' : 'Không có đơn hàng phù hợp.' }}</div>
                @endforelse
            </div>

            <div class="ao-pagination">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
