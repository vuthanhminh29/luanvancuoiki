@extends('layouts.app')

@section('title', 'Đơn hàng của tôi - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/my-order.css') }}">
@endpush

@section('content')
@php
    $statusOptions = [
        '' => 'Tất cả',
        'AWAITING_PAYMENT' => 'Chờ thanh toán',
        'PENDING' => 'Chờ xác nhận',
        'CONFIRMED' => 'Đã xác nhận',
        'DELIVERING' => 'Đang giao',
        'DELIVERED' => 'Đã giao',
        'CANCELLED' => 'Đã hủy',
        'RETURN_PENDING' => 'Chờ hoàn/đổi',
        'RETURNED' => 'Hoàn trả',
        'EXCHANGED' => 'Đã đổi hàng',
    ];
    $statusMeta = fn ($status) => match ($status) {
        'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning', 'fa-credit-card'],
        'PENDING' => ['Chờ xác nhận', 'warning', 'fa-clock'],
        'CONFIRMED' => ['Đã xác nhận', 'info', 'fa-clipboard-check'],
        'DELIVERING' => ['Đang giao', 'moving', 'fa-truck'],
        'DELIVERED' => ['Giao thành công', 'success', 'fa-check-circle'],
        'CANCELLED' => ['Đã hủy', 'danger', 'fa-times-circle'],
        'RETURN_PENDING' => ['Chờ hoàn/đổi', 'return', 'fa-rotate-left'],
        'RETURNED' => ['Đã hoàn trả', 'dark', 'fa-undo'],
        'EXCHANGED' => ['Đã đổi hàng', 'success', 'fa-right-left'],
        default => [$status ?: 'Chưa xác nhận', 'warning', 'fa-clock'],
    };
@endphp

<div class="orders-breadcrumb">
    <div class="orders-container">
        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.index') }}">Tài khoản</a>
        <span>/</span>
        <strong>Đơn mua</strong>
    </div>
</div>

<main class="orders-shell">
    <div class="orders-container">
        <div class="orders-head">
            <div class="orders-title">
                <h1>Đơn hàng của tôi</h1>
                <p>Theo dõi trạng thái giao hàng, hoàn/đổi và lịch sử mua kính.</p>
            </div>
            <div class="orders-count">
                <span>Tổng đơn phù hợp</span>
                <strong>{{ number_format($orders->total()) }}</strong>
            </div>
        </div>

        <form method="get" class="orders-filter">
            <div class="orders-field">
                <label>Trạng thái</label>
                <select name="status" class="orders-select">
                    @foreach ($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="orders-field">
                <label>Từ ngày</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="orders-input">
            </div>
            <div class="orders-field">
                <label>Đến ngày</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="orders-input">
            </div>
            <button class="orders-btn primary" type="submit"><i class="fas fa-filter"></i> Lọc</button>
            <a class="orders-btn light" href="{{ route('account.orders.index') }}">Xóa lọc</a>
        </form>

        @if ($orders->count() > 0)
            <div class="orders-list">
                @foreach ($orders as $order)
                    @php([$label, $class, $icon] = $statusMeta($order->status))
                    <article class="order-card">
                        <div class="order-card-head">
                            <div class="order-id">
                                <i class="fas fa-receipt"></i>
                                <div>
                                    Đơn hàng #{{ $order->order_code }}
                                    <small><i class="far fa-calendar-alt"></i> {{ $order->created_at?->format('d/m/Y H:i') }}</small>
                                </div>
                            </div>
                            <div class="order-badges">
                                <span class="order-badge {{ $class }}"><i class="fas {{ $icon }}"></i>{{ $label }}</span>
                            </div>
                        </div>

                        <div class="order-products">
                            @foreach ($order->items->take(3) as $item)
                                <div class="order-product">
                                    <img src="{{ $item->product->image_url ?? asset('upload/no-image.jpg') }}" alt="{{ $item->product_name }}">
                                    <div>
                                        <p class="order-product-name">{{ $item->product_name }}</p>
                                        <div class="order-product-meta">
                                            <strong>{{ number_format($item->unit_price, 0, ',', '.') }}đ</strong>
                                            <span>x{{ $item->quantity }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if ($order->items->count() > 3)
                                <div class="order-more">
                                    <span><i class="fas fa-box-open"></i> +{{ $order->items->count() - 3 }} sản phẩm khác</span>
                                </div>
                            @endif
                        </div>

                        <div class="order-card-foot">
                            <div class="order-total">
                                <span>Thành tiền</span>
                                <strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong>
                            </div>
                            <a href="{{ route('account.orders.show', $order) }}" class="orders-btn primary">
                                Xem chi tiết <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="orders-pages">
                {{ $orders->links() }}
            </div>
        @else
            <div class="orders-empty">
                <i class="fas fa-shopping-bag"></i>
                <h3>Chưa có đơn hàng</h3>
                <p>Bạn chưa có đơn hàng nào phù hợp với bộ lọc hiện tại.</p>
                <a href="{{ route('products.index') }}" class="orders-btn primary">Khám phá sản phẩm <i class="fas fa-arrow-right"></i></a>
            </div>
        @endif
    </div>
</main>
@endsection
