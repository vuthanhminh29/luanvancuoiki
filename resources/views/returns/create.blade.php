@extends('layouts.app')

@section('title', 'Tạo yêu cầu hoàn đổi - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/return-request.css') }}?v={{ filemtime(public_path('css/views/return-request.css')) }}">
@endpush

@section('content')
<div class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__links">
                    <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                    <a href="{{ route('account.orders.show', $order) }}">Chi tiết đơn hàng</a>
                    <span>Yêu cầu hoàn/đổi</span>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $firstItem = $order->items->first();
@endphp

<section class="view-return-request-inline-2">
    <div class="container view-return-request-inline-3">
        @if ($firstItem)
            <div class="view-return-request-inline-4">
                <div class="view-return-request-inline-5">
                    <img src="{{ $firstItem->product->image_url ?? asset('upload/no-image.jpg') }}" alt="" class="view-return-request-inline-13">
                    <div>
                        <h4 class="view-return-request-inline-6">{{ $firstItem->product_name }}</h4>
                        <div class="view-return-request-inline-7">Số lượng đã mua: {{ $firstItem->quantity }}</div>
                        <div class="view-return-request-inline-8">{{ number_format($firstItem->unit_price, 0, ',', '.') }}d</div>
                    </div>
                </div>
            </div>
        @endif

        <form method="post" action="{{ route('returns.store', $order) }}" class="view-return-request-inline-9">
            @csrf
            <div class="form-group">
                <label>Loại yêu cầu</label>
                <select name="type" class="form-control" required>
                    <option value="RETURN" @selected(old('type') === 'RETURN')>Hoàn trả</option>
                    <option value="EXCHANGE" @selected(old('type') === 'EXCHANGE')>Đổi hàng</option>
                </select>
            </div>

            <div class="form-group">
                <label>Sản phẩm</label>
                <select name="order_item_id" class="form-control" required>
                    @foreach ($order->items as $item)
                        <option value="{{ $item->id }}" @selected(old('order_item_id') == $item->id)>
                            {{ $item->product_name }} x {{ $item->quantity }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Lý do</label>
                <select name="reason_id" class="form-control" required>
                    <option value="">-- Chọn lý do --</option>
                    @foreach ($reasons as $reason)
                        <option value="{{ $reason->id }}" @selected(old('reason_id') == $reason->id)>{{ $reason->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Số lượng</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Mô tả thêm</label>
                <textarea name="reason_detail" rows="4" maxlength="1000" class="form-control" placeholder="Mô tả tình trạng sản phẩm hoặc mong muốn đổi hàng...">{{ old('reason_detail') }}</textarea>
            </div>

            <div class="form-group">
                <label>Tình trạng sản phẩm</label>
                <textarea name="condition_note" rows="3" maxlength="500" class="form-control" placeholder="Ví dụ: còn tem, còn hộp, bị trầy xước...">{{ old('condition_note') }}</textarea>
            </div>

            <div class="view-return-request-inline-12">
                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-light view-return-request-inline-14">Quay lại</a>
                <button type="submit" class="btn btn-dark">Gửi yêu cầu</button>
            </div>
        </form>
    </div>
</section>
@endsection
