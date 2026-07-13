@extends('layouts.app')

@section('title', 'Hoàn đổi - ' . config('app.name'))

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
                    <span>Hoàn/Đổi</span>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="view-return-review-inline-7">
    <div class="container view-return-review-inline-8">
        <div class="view-return-review-inline-9">
            <div>
                <h3 class="view-return-review-inline-10">Yêu cầu hoàn/đổi của tôi</h3>
                <div class="view-return-review-inline-11">Theo dõi trạng thái các yêu cầu sau bán hàng.</div>
            </div>
        </div>

        <div class="view-return-review-inline-22">
            <div class="view-return-review-inline-23">
                <h4 class="view-return-review-inline-24">Danh sách yêu cầu</h4>
            </div>
            <div class="view-return-review-inline-25">
                <table class="view-return-review-inline-26">
                    <thead>
                        <tr class="view-return-review-inline-27">
                            <th class="view-return-review-inline-28">Mã yêu cầu</th>
                            <th class="view-return-review-inline-28">Đơn hàng</th>
                            <th class="view-return-review-inline-28">Loại</th>
                            <th class="view-return-review-inline-28">Trạng thái</th>
                            <th class="view-return-review-inline-29"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td class="view-return-review-inline-31">{{ $request->return_code }}</td>
                                <td class="view-return-review-inline-33">{{ $request->order->order_code }}</td>
                                <td class="view-return-review-inline-33">{{ $request->type }}</td>
                                <td class="view-return-review-inline-32">{{ $request->status }}</td>
                                <td class="view-return-review-inline-30">
                                    <a class="btn btn-sm btn-dark" href="{{ route('returns.show', $request) }}">Chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="view-return-review-inline-34">Chưa có yêu cầu hoàn/đổi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $requests->links() }}
    </div>
</section>
@endsection
