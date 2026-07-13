@extends('admin.layouts.app')

@section('title', 'Chi tiết bình luận')

@php
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $statusBadge = fn ($value) => match ($value) {
        'VISIBLE' => ['Đang hiển thị', 'success'],
        'PENDING' => ['Đang hiển thị', 'success'],
        'HIDDEN' => ['Đã ẩn', 'muted'],
        default => [$value ?: '-', 'muted'],
    };
    $stars = str_repeat('★', (int) $review->rating) . str_repeat('☆', max(0, 5 - (int) $review->rating));
    [$statusLabel, $statusClass] = $statusBadge($review->status);
@endphp

@push('styles')
<style>
.rd-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.rd-inner{max-width:1300px;margin:0 auto}.rd-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.rd-title small{color:#0f8a7a;font-size:13px;font-weight:900;text-transform:uppercase}.rd-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.rd-title p{color:#667085;font-size:14px;margin:0}.rd-actions{display:flex;gap:9px;justify-content:flex-end}.rd-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.rd-btn.primary{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.rd-btn:hover{filter:brightness(.98);color:inherit}.rd-btn.primary:hover{color:#fff}
.rd-layout{display:grid;gap:16px;grid-template-columns:minmax(0,1.35fr) minmax(320px,.75fr)}.rd-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.rd-card-head{border-bottom:1px solid #eef2f6;padding:15px}.rd-card-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.rd-body{padding:15px}.rd-stars{color:#f59e0b;font-size:20px;font-weight:900;letter-spacing:1px}.rd-content{background:#fbfcfd;border:1px solid #eef2f6;border-radius:8px;color:#344054;font-size:15px;font-weight:700;line-height:1.65;margin-top:14px;min-height:140px;padding:16px}.rd-form{display:grid;gap:10px;margin-top:14px}.rd-form label{color:#667085;font-size:11px;font-weight:900;text-transform:uppercase}.rd-form select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:800;min-height:38px;padding:8px 10px;width:100%}.rd-info{align-items:center;display:flex;gap:12px}.rd-avatar,.rd-thumb{align-items:center;background:#e0f2fe;border:1px solid #bae6fd;border-radius:50%;color:#0369a1;display:inline-flex;flex:0 0 48px;font-size:14px;font-weight:900;height:48px;justify-content:center;overflow:hidden;text-transform:uppercase;width:48px}.rd-thumb{background:#f8fafc;border-color:#e4e7ec;border-radius:8px}.rd-thumb img{height:100%;object-fit:cover;width:100%}.rd-name{color:#111827;font-size:14px;font-weight:900}.rd-sub{color:#667085;font-size:12px;line-height:1.5;margin-top:3px}.rd-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px}.rd-badge.success{background:#dcfce7;color:#166534}.rd-badge.warning{background:#fef3c7;color:#92400e}.rd-badge.muted{background:#f3f4f6;color:#4b5563}.rd-table{border-collapse:collapse;width:100%}.rd-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:10px 0}.rd-table td:first-child{color:#667085;font-weight:900;width:130px}.rd-side{display:grid;gap:16px}
@media(max-width:1000px){.rd-layout{grid-template-columns:1fr}.rd-head{flex-direction:column}.rd-actions,.rd-btn{width:100%}}@media(max-width:760px){.rd-page{margin:-24px -12px 0;padding:16px 12px}}
</style>
@endpush

@section('content')
<div class="rd-page">
    <div class="rd-inner">
        <div class="rd-head">
            <div class="rd-title">
                <small>Mắt kính admin</small>
                <h4>Chi tiết bình luận #{{ $review->id }}</h4>
                <p>Xem nội dung đánh giá, thông tin khách hàng và ẩn/hiển thị khi cần.</p>
            </div>
            <div class="rd-actions">
                <a class="rd-btn" href="{{ route('admin.reviews.index') }}"><i class="fa fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div class="rd-layout">
            <div class="rd-card">
                <div class="rd-card-head">
                    <h6>Nội dung đánh giá</h6>
                </div>
                <div class="rd-body">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div class="rd-stars">{{ $stars }}</div>
                        <span class="rd-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="rd-content">{{ $review->content ?: 'Khách hàng không nhập nội dung.' }}</div>

                    <form class="rd-form" method="post" action="{{ route('admin.reviews.update', $review) }}">
                        @csrf
                        @method('PUT')
                        <label>Cập nhật trạng thái</label>
                        <select name="status">
                            <option value="VISIBLE" @selected(in_array($review->status, ['VISIBLE', 'PENDING'], true))>Đang hiển thị</option>
                            <option value="HIDDEN" @selected($review->status === 'HIDDEN')>Đã ẩn</option>
                        </select>
                        <button class="rd-btn primary" type="submit"><i class="fa fa-save"></i> Lưu trạng thái</button>
                    </form>
                </div>
            </div>

            <div class="rd-side">
                <div class="rd-card">
                    <div class="rd-card-head"><h6>Khách hàng</h6></div>
                    <div class="rd-body">
                        <div class="rd-info">
                            <div class="rd-avatar">{{ mb_strtoupper(mb_substr($review->user?->full_name ?: $review->user?->email ?: 'K', 0, 1)) }}</div>
                            <div>
                                <div class="rd-name">{{ $review->user?->full_name ?: 'Khách hàng' }}</div>
                                <div class="rd-sub">{{ $review->user?->email ?: '-' }}<br>{{ $review->user?->phone ?: 'Chưa có số điện thoại' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rd-card">
                    <div class="rd-card-head"><h6>Sản phẩm</h6></div>
                    <div class="rd-body">
                        <div class="rd-info">
                            <div class="rd-thumb">
                                @if ($review->product)
                                    <img src="{{ $review->product->image_url }}" alt="{{ $review->product->name }}">
                                @else
                                    <i class="fa fa-image"></i>
                                @endif
                            </div>
                            <div>
                                <div class="rd-name">{{ $review->product?->name ?: 'Sản phẩm đã xóa' }}</div>
                                <div class="rd-sub">{{ $review->product?->product_code ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rd-card">
                    <div class="rd-card-head"><h6>Thông tin</h6></div>
                    <div class="rd-body">
                        <table class="rd-table">
                            <tr><td>Ngày gửi</td><td>{{ $date($review->created_at) }}</td></tr>
                            <tr><td>Cập nhật</td><td>{{ $date($review->updated_at) }}</td></tr>
                            <tr><td>Order item</td><td>{{ $review->order_item_id ?: 'Không gắn' }}</td></tr>
                            <tr><td>ID sản phẩm</td><td>{{ $review->product_id }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
