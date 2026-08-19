@extends('layouts.app')

@section('title', $order->order_code . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/my-orderdetails.css') }}">
@endpush

@section('content')
@php
    $statusMeta = fn ($status) => match ($status) {
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning', 'fa-credit-card'],
        'PENDING' => ['Chờ xác nhận', 'warning', 'fa-clock'],
        'CONFIRMED' => ['Đã xác nhận', 'info', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'moving', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'success', 'fa-check-circle'],
        'CANCELLED' => ['Đã hủy', 'danger', 'fa-times-circle'],
        'RETURN_PENDING' => ['Chờ xử lý hoàn/đổi', 'return', 'fa-rotate-left'],
        'RETURNED' => ['Đã hoàn trả', 'dark', 'fa-undo'],
        'EXCHANGED' => ['Đã đổi hàng', 'success', 'fa-right-left'],
        default => [$status ?: 'Chờ xác nhận', 'warning', 'fa-clock'],
    };

    $latestReturn = $order->returnRequests->first();
    $displayStatusForOrder = function ($order) {
        if ($order->returnRequests->whereIn('status', ['PENDING', 'APPROVED', 'RECEIVED'])->isNotEmpty()) {
            return 'RETURN_PENDING';
        }

        $completed = $order->returnRequests
            ->where('status', 'COMPLETED')
            ->sortByDesc('requested_at')
            ->first();

        return match ($completed?->type) {
            'RETURN' => 'RETURNED',
            'EXCHANGE' => 'EXCHANGED',
            default => $order->status,
        };
    };
    $orderDisplayStatus = $displayStatusForOrder($order);
    [$statusLabel, $statusClass, $statusIcon] = $statusMeta($orderDisplayStatus);
    $progressSteps = [
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'fa-credit-card'],
        'PENDING' => ['Chờ xác nhận', 'fa-clock'],
        'CONFIRMED' => ['Đã xác nhận', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'fa-check-circle'],
    ];
    $progressOrder = array_keys($progressSteps);
    $progressCurrent = in_array($orderDisplayStatus, ['RETURN_PENDING', 'RETURNED', 'EXCHANGED'], true) ? 'DELIVERED' : $orderDisplayStatus;
    $progressIndex = array_search($progressCurrent, $progressOrder, true);
    $progressClasses = [
        'AWAITING_PAYMENT' => 'payment',
        'PENDING' => 'pending',
        'CONFIRMED' => 'confirmed',
        'DELIVERING' => 'shipping',
        'DELIVERED' => 'success',
    ];
    $paymentMap = ['COD' => 'COD - Nhận hàng', 'VNPAY' => 'VNPay'];
    $returnStatusMeta = function ($request) {
        $typeLabel = $request?->type === 'EXCHANGE' ? 'đổi hàng' : 'hoàn trả';

        return match ($request?->status) {
            'APPROVED' => ['Đã duyệt ' . $typeLabel, 'return-progress', 'fa-clipboard-check'],
            'RECEIVED' => ['Đã nhận hàng hoàn/đổi', 'return-progress', 'fa-box-open'],
            'COMPLETED' => ['Hoàn tất ' . $typeLabel, 'return-done', 'fa-check-circle'],
            'REJECTED' => ['Từ chối ' . $typeLabel, 'return-rejected', 'fa-ban'],
            'CANCELLED' => ['Đã hủy ' . $typeLabel, 'dark', 'fa-times-circle'],
            default => ['Đang yêu cầu ' . $typeLabel, 'return', 'fa-rotate-left'],
        };
    };
    [$returnLabel, $returnClass, $returnIcon] = $latestReturn
        ? $returnStatusMeta($latestReturn)
        : [null, null, null];
    $returnItemLabels = $order->returnRequests->flatMap(function ($request) {
        return $request->items->mapWithKeys(function ($item) use ($request) {
            $typeLabel = $request->type === 'EXCHANGE' ? 'Yêu cầu đổi hàng' : 'Yêu cầu hoàn trả';
            $statusLabel = match ($request->status) {
                'APPROVED' => 'Đã duyệt',
                'REJECTED' => 'Từ chối',
                'RECEIVED' => 'Đã nhận hàng',
                'COMPLETED' => 'Hoàn tất',
                'CANCELLED' => 'Đã hủy',
                default => 'Đang xử lý',
            };

            return [$item->order_item_id => $typeLabel . ' - ' . $statusLabel];
        });
    });
    $remainingReturnQuantities = $order->items->mapWithKeys(function ($item) use ($order, $countedReturnStatuses) {
        $requestedQuantity = $order->returnRequests
            ->whereIn('status', $countedReturnStatuses)
            ->flatMap->items
            ->where('order_item_id', $item->id)
            ->sum('quantity');

        return [$item->id => max(0, (int) $item->quantity - (int) $requestedQuantity)];
    });
    $canCreateReturnRequest = $order->hasReturnableStatus()
        && $isReturnWindowOpen
        && $remainingReturnQuantities->contains(fn ($quantity) => $quantity > 0);
    $customerCancellationRequested = $order->status === 'CONFIRMED'
        && $order->cancel_requested_at !== null
        && str_contains((string) $order->note, '[Khách yêu cầu hủy đơn');
    $adminCancellationRequested = $order->status !== 'CANCELLED'
        && $order->cancel_requested_at !== null
        && $order->cancel_confirmation_token_hash !== null;
    $canCustomerCancel = in_array($order->status, ['PENDING', 'AWAITING_PAYMENT', 'CONFIRMED'], true)
        && ! $adminCancellationRequested
        && ! $customerCancellationRequested;
    $cancelConfirmMessage = in_array($order->status, ['PENDING', 'AWAITING_PAYMENT'], true)
        ? 'Hủy đơn hàng này? Đơn sẽ được hủy ngay.'
        : 'Gửi yêu cầu hủy đơn này đến admin?';
@endphp

<div class="od-breadcrumb">
    <div class="od-container">
        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.orders.index') }}">Đơn mua</a>
        <span>/</span>
        <strong>Chi tiết đơn hàng</strong>
    </div>
</div>

<main class="od-shell">
    <div class="od-container">
        <div class="od-head">
            <div class="od-title">
                <h1>Đơn hàng #{{ $order->order_code }}</h1>
                <p>Đặt lúc {{ $order->created_at?->format('d/m/Y H:i') }}</p>
            </div>
            <div class="od-actions">
                <span class="od-badge {{ $statusClass }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusLabel }}</span>
                @if ($latestReturn)
                    <span class="od-badge {{ $returnClass }}" title="Mã yêu cầu {{ $latestReturn->return_code }}">
                        <i class="fas {{ $returnIcon }}"></i>{{ $returnLabel }} · {{ $latestReturn->return_code }}
                    </span>
                @endif
                <a href="{{ route('account.orders.invoice', $order) }}" class="od-btn primary" target="_blank" rel="noopener">
                    <i class="fas fa-file-invoice"></i> Xuất hóa đơn
                </a>
                <form action="{{ route('account.orders.invoice.email', $order) }}" method="post" class="od-inline-form">
                    @csrf
                    <button type="submit" class="od-btn light">
                        <i class="fas fa-envelope"></i> Gửi hóa đơn
                    </button>
                </form>
                @if ($canCustomerCancel)
                    <form action="{{ route('account.orders.cancel', $order) }}" method="post" class="od-inline-form" onsubmit="return confirm(@json($cancelConfirmMessage));">
                        @csrf
                        <input type="hidden" name="cancel_reason" value="Khách hàng yêu cầu hủy đơn.">
                        <button type="submit" class="od-btn danger">
                            <i class="fas fa-times-circle"></i> Hủy đơn
                        </button>
                    </form>
                @elseif ($customerCancellationRequested)
                    <span class="od-badge warning"><i class="fas fa-clock"></i> Chờ admin xử lý hủy đơn</span>
                @elseif ($adminCancellationRequested)
                    <span class="od-badge warning"><i class="fas fa-envelope"></i> Kiểm tra email hủy đơn</span>
                @endif
                <a href="{{ route('account.orders.index') }}" class="od-btn light"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div class="od-card">
            <div class="od-card-body">
                <div class="od-meta-grid">
                    <div class="od-meta">
                        <span>Ngày đặt hàng</span>
                        <strong>{{ $order->created_at?->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="od-meta">
                        <span>Dự kiến giao</span>
                        <strong>{{ $order->status === 'CANCELLED' ? 'Không áp dụng' : $order->created_at?->copy()->addDays(5)->format('d/m/Y') }}</strong>
                    </div>
                    <div class="od-meta">
                        <span>Thanh toán</span>
                        <strong>{{ $paymentMap[$order->payment_method] ?? $order->payment_method }}</strong>
                    </div>
                </div>

                @if ($order->status !== 'CANCELLED')
                    <div class="od-progress">
                        @foreach ($progressSteps as $stepStatus => [$stepLabel, $stepIcon])
                            <div class="od-step {{ $progressClasses[$stepStatus] ?? '' }} {{ $progressIndex !== false && $progressIndex >= $loop->index ? 'active' : '' }}">
                                <div class="od-step-icon"><i class="fas {{ $stepIcon }}"></i></div>
                                {{ $stepLabel }}
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="od-alert danger view-my-orderdetails-inline-8">
                        <i class="fas fa-times-circle"></i>
                        Đơn hàng này đã bị hủy.
                    </div>
                @endif

                @if ($customerCancellationRequested)
                    <div class="od-alert warning view-my-orderdetails-inline-8">
                        <i class="fas fa-clock"></i>
                        Yêu cầu hủy đơn của bạn đã được gửi đến admin và đang chờ xử lý.
                    </div>
                @endif
                @if ($adminCancellationRequested)
                    <div class="od-alert warning view-my-orderdetails-inline-8">
                        <i class="fas fa-envelope"></i>
                        Cửa hàng đã gửi yêu cầu xác nhận hủy đơn qua email. Vui lòng kiểm tra email để xác nhận.
                    </div>
                @endif
            </div>
        </div>

        <div class="od-grid view-my-orderdetails-inline-4">
            <section class="od-card">
                <div class="od-card-head">
                    <h2>Sản phẩm trong đơn</h2>
                    <span class="view-my-orderdetails-inline-5">{{ $order->items->count() }} sản phẩm</span>
                </div>
                <div class="od-card-body">
                    @foreach ($order->items as $item)
                        <div class="od-product">
                            <img src="{{ $item->product->image_url ?? asset('upload/no-image.jpg') }}" alt="{{ $item->product_name }}">
                            <div>
                                <p class="od-product-name">{{ $item->product_name }}</p>
                                <div class="od-product-spec" style="font-size: 11px; font-family: var(--font-mono); color: var(--ink-soft); margin-bottom: 4px;">
                                    @if (!empty($item->product->frame_size))
                                        <span>{{ str_replace([' ', '□', '-'], [' ', '▭', '-'], $item->product->frame_size) }}</span>
                                    @else
                                        <span>52▭18-145</span>
                                    @endif
                                </div>
                                <div class="od-product-meta">
                                    <span>Số lượng: <strong>x{{ $item->quantity }}</strong></span>
                                    @if ($returnItemLabels->has($item->id))
                                        <span class="od-badge return">{{ $returnItemLabels->get($item->id) }}</span>
                                    @endif
                                </div>
                                @if (in_array($order->status, ['DELIVERED', 'RETURN_PENDING'], true))
                                    <a href="{{ route('returns.create', $order) }}" class="od-link">Yêu cầu hoàn/đổi</a>
                                @endif
                            </div>
                            <div class="od-product-price">
                                <strong>{{ number_format($item->total_price, 0, ',', '.') }}đ</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="od-summary">
                <section class="od-card">
                    <div class="od-card-head">
                        <h2>Thông tin giao hàng</h2>
                    </div>
                    <div class="od-card-body">
                        <div class="od-summary-row">
                            <span>Người nhận</span>
                            <strong>{{ $order->recipient_name }}</strong>
                        </div>
                        <div class="od-summary-row">
                            <span>Số điện thoại</span>
                            <strong>{{ $order->recipient_phone }}</strong>
                        </div>
                        <div class="od-summary-row">
                            <span>Địa chỉ</span>
                            <strong class="view-my-orderdetails-inline-7">{{ $order->shipping_address }}</strong>
                        </div>
                        @if ($order->note)
                            <div class="od-summary-row">
                                <span>Ghi chú</span>
                                <strong>{{ $order->note }}</strong>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="od-card">
                    <div class="od-card-head">
                        <h2>Tổng thanh toán</h2>
                    </div>
                    <div class="od-card-body">
                        <div class="od-summary-row">
                            <span>Tổng tiền hàng</span>
                            <strong>{{ number_format($order->subtotal_amount, 0, ',', '.') }}đ</strong>
                        </div>
                        <div class="od-summary-row">
                            <span>Phí vận chuyển</span>
                            <strong>Miễn phí</strong>
                        </div>
                        <div class="od-summary-row">
                            <span>Phương thức</span>
                            <strong>{{ $paymentMap[$order->payment_method] ?? $order->payment_method }}</strong>
                        </div>
                        <div class="od-summary-row od-summary-total">
                            <span>Thành tiền</span>
                            <strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong>
                        </div>

                        @if (in_array($order->status, ['DELIVERED', 'RETURN_PENDING', 'RETURNED', 'EXCHANGED'], true))
                            <a class="od-btn light" href="{{ route('returns.create', $order) }}">Yêu cầu hoàn/đổi</a>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>
@endsection
