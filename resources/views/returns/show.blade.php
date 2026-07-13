@extends('layouts.app')

@section('title', $returnRequest->return_code . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/return-review.css') }}?v={{ filemtime(public_path('css/views/return-review.css')) }}">
@endpush

@section('content')
<div class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__links">
                    <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                    <a href="{{ route('returns.index') }}">Hoàn/Đổi</a>
                    <span>{{ $returnRequest->return_code }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="view-return-review-inline-7">
    <div class="container view-return-review-inline-8">
        <div class="view-return-review-inline-9">
            <div>
                <h3 class="view-return-review-inline-10">{{ $returnRequest->return_code }}</h3>
                <div class="view-return-review-inline-11">Đơn hàng: {{ $returnRequest->order->order_code }}</div>
            </div>
            <span class="view-return-review-inline-16">{{ $returnRequest->status }}</span>
        </div>

        <div class="view-return-review-inline-12">
            @foreach ($returnRequest->items as $item)
                <div class="view-return-review-inline-13">
                    <img src="{{ $item->orderItem->product->image_url ?? asset('upload/no-image.jpg') }}" alt="" class="view-return-review-inline-36">
                    <div>
                        <h4 class="view-return-review-inline-14">{{ $item->orderItem->product_name }}</h4>
                        <div class="view-return-review-inline-15">
                            <span class="view-return-review-inline-16">x {{ $item->quantity }}</span>
                        </div>
                        <div class="view-return-review-inline-17">{{ $item->condition_note ?: 'Chưa có ghi chu tinh trang' }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="view-return-review-inline-18">
            <h4 class="view-return-review-inline-19">Thông tin yêu cầu</h4>
            <div class="view-return-review-inline-20">
                <p><strong>Loại:</strong> {{ $returnRequest->type }}</p>
                <p><strong>Lý do:</strong> {{ $returnRequest->reason->name ?? 'Khác' }}</p>
                <p><strong>Mô tả:</strong> {{ $returnRequest->reason_detail ?: 'Không có' }}</p>
                <p><strong>Ghi chú admin:</strong> {{ $returnRequest->admin_note ?: 'Chưa có' }}</p>
            </div>
        </div>

        <a href="{{ route('returns.index') }}" class="btn btn-light view-return-review-inline-35">Quay lại</a>
    </div>
</section>
@endsection
