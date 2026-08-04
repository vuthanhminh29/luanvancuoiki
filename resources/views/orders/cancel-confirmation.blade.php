@extends('layouts.app')

@section('title', 'Xác nhận hủy đơn hàng - ' . config('app.name'))

@push('styles')
<style>
.oc-page { background:#f5f7fb; min-height:70vh; padding:36px 16px; color:#111827; }
.oc-shell { max-width:920px; margin:0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.oc-head { padding:22px 24px; border-bottom:1px solid #eef0f3; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; }
.oc-head h1 { margin:0; font-size:24px; font-weight:800; letter-spacing:0; }
.oc-head p { margin:6px 0 0; color:#6b7280; }
.oc-badge { display:inline-flex; min-height:30px; align-items:center; padding:0 11px; border-radius:999px; font-size:12px; font-weight:800; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; white-space:nowrap; }
.oc-body { padding:24px; }
.oc-alert { padding:14px 16px; border-radius:8px; margin-bottom:18px; font-weight:700; }
.oc-alert.error { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; }
.oc-alert.success { color:#047857; background:#ecfdf5; border:1px solid #a7f3d0; }
.oc-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:18px; }
.oc-item { border:1px solid #eef0f3; border-radius:8px; padding:12px; }
.oc-item span { display:block; color:#6b7280; font-size:12px; font-weight:800; margin-bottom:5px; }
.oc-item strong { display:block; font-size:14px; line-height:1.45; }
.oc-products { border-top:1px solid #eef0f3; margin-top:18px; padding-top:8px; }
.oc-product { display:grid; grid-template-columns:60px 1fr auto; gap:16px; padding:12px 0; border-bottom:1px solid #f1f3f5; align-items: center; }
.oc-product img { width:100%; aspect-ratio:1/1; border-radius:4px; object-fit:cover; border:1px solid #eef0f3; }
.oc-product:last-child { border-bottom:0; }
.oc-product strong { display:block; }
.oc-product span { display:block; color:#6b7280; font-size:13px; margin-top:2px; }
.oc-total { border-top:1px solid #eef0f3; padding-top:16px; margin-top:14px; display:flex; justify-content:space-between; gap:16px; font-size:18px; font-weight:800; }
.oc-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:22px; }
.oc-btn { min-height:40px; border-radius:6px; padding:0 15px; border:1px solid transparent; display:inline-flex; align-items:center; justify-content:center; gap:7px; font-size:14px; font-weight:800; cursor:pointer; text-decoration:none; }
.oc-btn.danger { background:#b91c1c; color:#fff; }
.oc-btn.light { background:#fff; color:#374151; border-color:#d1d5db; }
@media (max-width:680px) { .oc-head { flex-direction:column; } .oc-grid { grid-template-columns:1fr; } .oc-product { grid-template-columns:50px 1fr; align-items:start; gap:12px; } .oc-product-price { grid-column:2; margin-top:-4px; } .oc-actions { flex-direction:column; } .oc-btn { width:100%; } }
</style>
@endpush

@section('content')
<main class="oc-page">
    <section class="oc-shell">
        <div class="oc-head">
            <div>
                <h1>Xác nhận hủy đơn hàng</h1>
                <p>{{ $order->order_code ?: '#' . $order->id }}</p>
            </div>
            <span class="oc-badge">{{ $order->status === 'CANCELLED' ? 'Đã hủy' : 'Chờ khách xác nhận' }}</span>
        </div>

        <div class="oc-body">
            @if ($confirmed)
                <div class="oc-alert success">Đơn hàng đã được hủy thành công. Cảm ơn bạn đã xác nhận.</div>
            @elseif ($error)
                <div class="oc-alert error">{{ $error }}</div>
            @else
                <div class="oc-alert error">Cửa hàng đang yêu cầu hủy đơn hàng này. Vui lòng kiểm tra thông tin bên dưới trước khi xác nhận.</div>
            @endif

            <div class="oc-grid">
                <div class="oc-item"><span>Khách hàng</span><strong>{{ $order->user->full_name ?? $order->recipient_name }}</strong></div>
                <div class="oc-item"><span>Email</span><strong>{{ $order->user->email ?? '-' }}</strong></div>
                <div class="oc-item"><span>Người nhận</span><strong>{{ $order->recipient_name }}</strong></div>
                <div class="oc-item"><span>Số điện thoại</span><strong>{{ $order->recipient_phone }}</strong></div>
                <div class="oc-item"><span>Địa chỉ giao hàng</span><strong>{{ $order->shipping_address }}</strong></div>
                <div class="oc-item"><span>Lý do hủy</span><strong>{{ $order->cancel_reason ?: 'Không có' }}</strong></div>
            </div>

            <div class="oc-products">
                @foreach ($order->items as $item)
                    <div class="oc-product">
                        <img src="{{ $item->product->image_url ?? asset('upload/no-image.jpg') }}" alt="{{ $item->product_name }}">
                        <div>
                            <strong>{{ $item->product_name }}</strong>
                            @if (trim(implode(' ', array_filter([$item->color_name, $item->lens_size_name]))))
                                <span>Phân loại: {{ trim(implode(' ', array_filter([$item->color_name, $item->lens_size_name]))) }}</span>
                            @endif
                            <span>Số lượng: {{ $item->quantity }}</span>
                        </div>
                        <strong class="oc-product-price">{{ number_format($item->total_price, 0, ',', '.') }}đ</strong>
                    </div>
                @endforeach
            </div>

            <div class="oc-total">
                <span>Tổng thanh toán</span>
                <strong>{{ number_format($order->total_amount, 0, ',', '.') }}đ</strong>
            </div>

            <div class="oc-actions">
                <a class="oc-btn light" href="{{ route('home') }}">Về trang chủ</a>
                @if (! $confirmed && ! $error)
                    <form method="post" action="{{ url()->full() }}">
                        @csrf
                        <button class="oc-btn danger" type="submit">Xác nhận hủy đơn</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
