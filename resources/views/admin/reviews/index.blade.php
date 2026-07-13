@extends('admin.layouts.app')

@section('title', 'Bình luận')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $statusBadge = fn ($value) => match ($value) {
        'VISIBLE' => ['Đang hiển thị', 'success'],
        'PENDING' => ['Đang hiển thị', 'success'],
        'HIDDEN' => ['Đã ẩn', 'muted'],
        default => [$value ?: '-', 'muted'],
    };
    $stars = fn ($value) => str_repeat('★', (int) $value) . str_repeat('☆', max(0, 5 - (int) $value));
@endphp

@push('styles')
<style>
.rv-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.rv-inner{max-width:1500px;margin:0 auto}.rv-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.rv-title small{color:#0f8a7a;font-size:13px;font-weight:900;text-transform:uppercase}.rv-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.rv-title p{color:#667085;font-size:14px;margin:0}.rv-actions{display:flex;gap:9px;justify-content:flex-end}.rv-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.rv-btn.primary{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.rv-btn:hover{filter:brightness(.98);color:inherit}.rv-btn.primary:hover{color:#fff}
.rv-summary{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}.rv-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.rv-stat i{align-items:center;border-radius:8px;display:inline-flex;height:34px;justify-content:center;width:34px}.rv-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.rv-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.rv-stat:nth-child(1) i{background:#eef2ff;color:#4f46e5}.rv-stat:nth-child(2) i{background:#dcfce7;color:#166534}.rv-stat:nth-child(3) i{background:#f3f4f6;color:#4b5563}.rv-stat:nth-child(4) i{background:#fff7ed;color:#c2410c}
.rv-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.rv-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:10px;grid-template-columns:minmax(240px,1.2fr) minmax(140px,.65fr) minmax(120px,.55fr) auto;padding:14px}.rv-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:6px;text-transform:uppercase}.rv-field input,.rv-field select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.rv-table-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.rv-table-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.rv-table-head small{color:#667085;font-size:12px;font-weight:800}
.rv-list-head,.rv-row{display:grid;gap:12px;grid-template-columns:minmax(220px,1.05fr) minmax(240px,1.1fr) 92px minmax(210px,1fr) 160px;align-items:center}.rv-list-head{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:.03em;padding:10px 14px;text-transform:uppercase}.rv-row{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;min-height:86px;padding:12px 14px}.rv-row:hover{background:#fafafa}.rv-person,.rv-product{align-items:center;display:flex;gap:10px;min-width:0}.rv-avatar,.rv-thumb{align-items:center;background:#e0f2fe;border:1px solid #bae6fd;border-radius:50%;color:#0369a1;display:inline-flex;flex:0 0 42px;font-size:13px;font-weight:900;height:42px;justify-content:center;overflow:hidden;text-transform:uppercase;width:42px}.rv-thumb{background:#f8fafc;border-color:#e4e7ec;border-radius:7px}.rv-thumb img{height:100%;object-fit:cover;width:100%}.rv-name{color:#111827;font-size:13px;font-weight:900;line-height:1.35;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.rv-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.rv-cell{min-width:0}.rv-content{color:#344054;display:-webkit-box;line-height:1.5;max-width:420px;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2}.rv-stars{color:#f59e0b;font-size:15px;font-weight:900;letter-spacing:1px;white-space:nowrap}.rv-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px;white-space:nowrap}.rv-badge.success{background:#dcfce7;color:#166534}.rv-badge.warning{background:#fef3c7;color:#92400e}.rv-badge.muted{background:#f3f4f6;color:#4b5563}.rv-actions-row{display:flex;gap:6px;justify-content:flex-start;margin-top:8px}.rv-actions-row form{margin:0}.rv-icon-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;height:32px;justify-content:center;width:34px}.rv-icon-btn.primary{border-color:#bfdbfe;color:#1d4ed8}.rv-icon-btn.success{border-color:#bbf7d0;color:#166534}.rv-icon-btn.muted{border-color:#d1d5db;color:#4b5563}.rv-icon-btn.danger{border-color:#fecaca;color:#991b1b}.rv-empty{color:#667085;padding:32px 12px;text-align:center}.rv-pagination{align-items:center;display:flex;gap:10px;justify-content:space-between;padding:13px 14px}.rv-page-info{color:#667085;font-size:12px;font-weight:800}.rv-pager{display:flex;gap:6px;justify-content:flex-end}.rv-page-link{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:6px;color:#344054;display:inline-flex;font-size:12px;font-weight:900;height:32px;justify-content:center;min-width:32px;padding:0 10px;text-decoration:none}.rv-page-link.active{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.rv-page-link.disabled{background:#f8fafc;color:#98a2b3;pointer-events:none}
@media(max-width:1180px){.rv-summary{grid-template-columns:repeat(3,minmax(0,1fr))}.rv-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.rv-filter .rv-actions{grid-column:1/-1}.rv-list-head{display:none}.rv-row{border:1px solid #eef2f6;border-radius:8px;grid-template-columns:1fr;margin:10px 12px;min-height:auto}.rv-row .rv-cell{grid-column:1}}@media(max-width:760px){.rv-page{margin:-24px -12px 0;padding:16px 12px}.rv-head{flex-direction:column}.rv-actions,.rv-btn{width:100%}.rv-summary,.rv-filter{grid-template-columns:1fr}.rv-actions-row{justify-content:flex-start}.rv-pagination{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="rv-page">
    <div class="rv-inner">
        <div class="rv-head">
            <div class="rv-title">
                <small>Mắt kính admin</small>
                <h4>Quản lý bình luận</h4>
                <p>Xem chi tiết, ẩn hoặc hiển thị đánh giá sản phẩm của khách hàng.</p>
            </div>
            <div class="rv-actions">
                <a class="rv-btn" href="{{ route('admin.reviews.index', ['status' => 'VISIBLE']) }}"><i class="fa fa-eye"></i> Đang hiển thị</a>
                <a class="rv-btn" href="{{ route('admin.reviews.index', ['status' => 'HIDDEN']) }}"><i class="fa fa-eye-slash"></i> Đã ẩn</a>
                <a class="rv-btn primary" href="{{ route('admin.reviews.index') }}"><i class="fa fa-sync"></i> Tất cả</a>
            </div>
        </div>

        <div class="rv-summary">
            <div class="rv-stat"><i class="fa fa-comments"></i><span>Tổng bình luận</span><strong>{{ $num($summary['total']) }}</strong></div>
            <div class="rv-stat"><i class="fa fa-eye"></i><span>Đang hiển thị</span><strong>{{ $num($summary['visible']) }}</strong></div>
            <div class="rv-stat"><i class="fa fa-eye-slash"></i><span>Đã ẩn</span><strong>{{ $num($summary['hidden']) }}</strong></div>
            <div class="rv-stat"><i class="fa fa-star"></i><span>Điểm trung bình</span><strong>{{ number_format($summary['average'], 1, ',', '.') }}</strong></div>
        </div>

        <div class="rv-card">
            <form class="rv-filter" method="get" action="{{ route('admin.reviews.index') }}">
                <div class="rv-field">
                    <label>Tìm kiếm</label>
                    <input name="keyword" value="{{ $keyword }}" placeholder="Tên khách, email, sản phẩm hoặc nội dung">
                </div>
                <div class="rv-field">
                    <label>Trạng thái</label>
                    <select name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="VISIBLE" @selected($status === 'VISIBLE')>Đang hiển thị</option>
                        <option value="HIDDEN" @selected($status === 'HIDDEN')>Đã ẩn</option>
                    </select>
                </div>
                <div class="rv-field">
                    <label>Số sao</label>
                    <select name="rating">
                        <option value="0">Tất cả sao</option>
                        @for ($star = 5; $star >= 1; $star--)
                            <option value="{{ $star }}" @selected((int) $rating === $star)>{{ $star }} sao</option>
                        @endfor
                    </select>
                </div>
                <div class="rv-actions">
                    <button class="rv-btn primary" type="submit"><i class="fa fa-search"></i> Lọc</button>
                    <a class="rv-btn" href="{{ route('admin.reviews.index') }}"><i class="fa fa-redo"></i> Xóa lọc</a>
                </div>
            </form>

            <div class="rv-table-head">
                <h6>Danh sách bình luận</h6>
                <small>{{ $reviews->total() }} bình luận</small>
            </div>

            <div class="rv-list">
                <div class="rv-list-head">
                    <div>Khách hàng</div>
                    <div>Sản phẩm</div>
                    <div>Sao</div>
                    <div>Nội dung</div>
                    <div>Trạng thái</div>
                </div>

                @forelse ($reviews as $review)
                    @php
                        [$label, $class] = $statusBadge($review->status);
                        $initial = mb_strtoupper(mb_substr($review->user?->full_name ?: $review->user?->email ?: 'K', 0, 1));
                    @endphp
                    <div class="rv-row">
                        <div class="rv-person rv-cell">
                            <div class="rv-avatar">{{ $initial }}</div>
                            <div class="rv-cell">
                                <div class="rv-name">{{ $review->user?->full_name ?: 'Khách hàng' }}</div>
                                <div class="rv-sub">{{ $review->user?->email ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="rv-product rv-cell">
                            <div class="rv-thumb">
                                @if ($review->product)
                                    <img src="{{ $review->product->image_url }}" alt="{{ $review->product->name }}">
                                @else
                                    <i class="fa fa-image"></i>
                                @endif
                            </div>
                            <div class="rv-cell">
                                <div class="rv-name">{{ $review->product?->name ?: 'Sản phẩm đã xóa' }}</div>
                                <div class="rv-sub">{{ $review->product?->product_code ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="rv-cell">
                            <div class="rv-stars">{{ $stars($review->rating) }}</div>
                            <div class="rv-sub">{{ $date($review->created_at) }}</div>
                        </div>

                        <div class="rv-cell">
                            <div class="rv-content">{{ $review->content ?: 'Không có nội dung.' }}</div>
                        </div>

                        <div class="rv-cell">
                            <span class="rv-badge {{ $class }}">{{ $label }}</span>
                            <div class="rv-sub">Cập nhật: {{ $date($review->updated_at) }}</div>
                            <div class="rv-actions-row">
                                <a class="rv-icon-btn primary" href="{{ route('admin.reviews.show', $review) }}" title="Chi tiết"><i class="fa fa-eye"></i></a>
                                @if ($review->status !== 'VISIBLE')
                                    <form method="post" action="{{ route('admin.reviews.update', $review) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="VISIBLE">
                                        <button class="rv-icon-btn success" title="Hiển thị"><i class="fa fa-check"></i></button>
                                    </form>
                                @endif
                                @if ($review->status !== 'HIDDEN')
                                    <form method="post" action="{{ route('admin.reviews.update', $review) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="HIDDEN">
                                        <button class="rv-icon-btn muted" title="Ẩn"><i class="fa fa-eye-slash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rv-empty">Không tìm thấy bình luận phù hợp.</div>
                @endforelse
            </div>

            <div class="rv-pagination">
                <div class="rv-page-info">Hiển thị {{ $reviews->firstItem() ?? 0 }}-{{ $reviews->lastItem() ?? 0 }} / {{ $reviews->total() }} bình luận</div>
                @if ($reviews->hasPages())
                    <div class="rv-pager">
                        <a class="rv-page-link {{ $reviews->onFirstPage() ? 'disabled' : '' }}" href="{{ $reviews->previousPageUrl() ?: '#' }}"><i class="fa fa-chevron-left"></i></a>
                        @foreach ($reviews->getUrlRange(1, $reviews->lastPage()) as $page => $url)
                            @if ($page === 1 || $page === $reviews->lastPage() || abs($page - $reviews->currentPage()) <= 1)
                                <a class="rv-page-link {{ $page === $reviews->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                            @elseif (abs($page - $reviews->currentPage()) === 2)
                                <span class="rv-page-link disabled">...</span>
                            @endif
                        @endforeach
                        <a class="rv-page-link {{ $reviews->hasMorePages() ? '' : 'disabled' }}" href="{{ $reviews->nextPageUrl() ?: '#' }}"><i class="fa fa-chevron-right"></i></a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
