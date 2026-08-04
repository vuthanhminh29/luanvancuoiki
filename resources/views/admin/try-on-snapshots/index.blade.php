@extends('admin.layouts.app')

@section('title', 'Kết quả thử kính')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
@endphp

@push('styles')
<style>
.tos-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.tos-inner{max-width:1500px;margin:0 auto}.tos-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.tos-title small{color:#0f766e;font-size:13px;font-weight:900;text-transform:uppercase}.tos-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.tos-title p{color:#667085;font-size:14px;margin:0}.tos-summary{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}.tos-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.tos-stat i{align-items:center;border-radius:8px;display:inline-flex;height:34px;justify-content:center;width:34px}.tos-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.tos-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.tos-stat:nth-child(1) i{background:#ecfeff;color:#0e7490}.tos-stat:nth-child(2) i{background:#eef2ff;color:#4f46e5}.tos-stat:nth-child(3) i{background:#dcfce7;color:#166534}.tos-stat:nth-child(4) i{background:#fff7ed;color:#c2410c}.tos-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.tos-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:10px;grid-template-columns:minmax(260px,1fr) auto auto;padding:14px}.tos-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:6px;text-transform:uppercase}.tos-field input{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.tos-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.tos-btn.primary{background:#0f766e;border-color:#0f766e;color:#fff}.tos-table-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.tos-table-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.tos-table-head small{color:#667085;font-size:12px;font-weight:800}.tos-list-head,.tos-row{display:grid;gap:12px;grid-template-columns:128px minmax(220px,1fr) minmax(220px,1fr) minmax(170px,.7fr) 150px;align-items:center}.tos-list-head{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:.03em;padding:10px 14px;text-transform:uppercase}.tos-row{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;min-height:118px;padding:12px 14px}.tos-row:hover{background:#fafafa}.tos-photo{background:#f8fafc;border:1px solid #e4e7ec;border-radius:8px;height:92px;overflow:hidden;width:118px}.tos-photo img{display:block;height:100%;object-fit:cover;width:100%}.tos-name{color:#111827;font-size:13px;font-weight:900;line-height:1.35;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.tos-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.tos-cell{min-width:0}.tos-model{background:#ecfeff;border-radius:999px;color:#0e7490;display:inline-flex;font-size:12px;font-weight:900;margin-top:8px;min-height:25px;padding:4px 9px}.tos-empty{color:#667085;padding:32px 12px;text-align:center}.tos-pagination{align-items:center;display:flex;gap:10px;justify-content:space-between;padding:13px 14px}.tos-page-info{color:#667085;font-size:12px;font-weight:800}.tos-pager{display:flex;gap:6px;justify-content:flex-end}.tos-page-link{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:6px;color:#344054;display:inline-flex;font-size:12px;font-weight:900;height:32px;justify-content:center;min-width:32px;padding:0 10px;text-decoration:none}.tos-page-link.active{background:#0f766e;border-color:#0f766e;color:#fff}.tos-page-link.disabled{background:#f8fafc;color:#98a2b3;pointer-events:none}@media(max-width:1180px){.tos-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.tos-filter{grid-template-columns:1fr}.tos-list-head{display:none}.tos-row{border:1px solid #eef2f6;border-radius:8px;grid-template-columns:120px 1fr;margin:10px 12px;min-height:auto}.tos-row .tos-cell{grid-column:2}.tos-row .tos-photo{grid-row:1/span 4}}@media(max-width:720px){.tos-page{margin:-24px -12px 0;padding:16px 12px}.tos-summary{grid-template-columns:1fr}.tos-row{grid-template-columns:1fr}.tos-row .tos-cell,.tos-row .tos-photo{grid-column:1;grid-row:auto}.tos-photo{height:180px;width:100%}.tos-pagination{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="tos-page">
    <div class="tos-inner">
        <div class="tos-head">
            <div class="tos-title">
                <small>Mắt kính admin</small>
                <h4>Kết quả thử kính</h4>
                <p>Danh sách ảnh chụp sau khi khách thử kính, kèm email, tên khách và model kính đã thử.</p>
            </div>
        </div>

        <div class="tos-summary">
            <div class="tos-stat"><i class="fa fa-camera-retro"></i><span>Tổng ảnh chụp</span><strong>{{ $num($summary['total']) }}</strong></div>
            <div class="tos-stat"><i class="fa fa-users"></i><span>Khách đã thử</span><strong>{{ $num($summary['users']) }}</strong></div>
            <div class="tos-stat"><i class="fa fa-glasses"></i><span>Model kính</span><strong>{{ $num($summary['models']) }}</strong></div>
            <div class="tos-stat"><i class="fa fa-calendar-day"></i><span>Hôm nay</span><strong>{{ $num($summary['today']) }}</strong></div>
        </div>

        <div class="tos-card">
            <form class="tos-filter" method="get" action="{{ route('admin.tryon-snapshots.index') }}">
                <div class="tos-field">
                    <label>Tìm kiếm</label>
                    <input name="keyword" value="{{ $keyword }}" placeholder="Tên khách, email, tên kính hoặc mã model">
                </div>
                <button class="tos-btn primary" type="submit"><i class="fa fa-search"></i> Lọc</button>
                <a class="tos-btn" href="{{ route('admin.tryon-snapshots.index') }}"><i class="fa fa-redo"></i> Xóa lọc</a>
            </form>

            <div class="tos-table-head">
                <h6>Danh sách ảnh thử kính</h6>
                <small>{{ $snapshots->total() }} kết quả</small>
            </div>

            <div class="tos-list">
                <div class="tos-list-head">
                    <div>Ảnh chụp</div>
                    <div>Khách hàng</div>
                    <div>Kính đã thử</div>
                    <div>Giá</div>
                    <div>Thời gian</div>
                </div>

                @forelse ($snapshots as $snapshot)
                    <div class="tos-row">
                        <a class="tos-photo" href="{{ $snapshot->image_url }}" target="_blank" rel="noopener">
                            <img src="{{ $snapshot->image_url }}" alt="{{ $snapshot->product_name }}">
                        </a>

                        <div class="tos-cell">
                            <div class="tos-name">{{ $snapshot->user_name }}</div>
                            <div class="tos-sub">{{ $snapshot->user_email }}</div>
                        </div>

                        <div class="tos-cell">
                            <div class="tos-name">{{ $snapshot->product_name }}</div>
                            <div class="tos-model">Model: {{ $snapshot->model_sku }}</div>
                            <div class="tos-sub">{{ $snapshot->tryon_mode === 'image' ? 'Thử bằng ảnh tải lên' : 'Thử bằng camera' }}</div>
                        </div>

                        <div class="tos-cell">
                            <div class="tos-name">{{ number_format((float) $snapshot->price, 0, ',', '.') }}đ</div>
                            <div class="tos-sub">ID: {{ $snapshot->id }}</div>
                        </div>

                        <div class="tos-cell">
                            <div class="tos-name">{{ $date($snapshot->created_at) }}</div>
                            <div class="tos-sub">{{ $snapshot->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="tos-empty">Chưa có kết quả thử kính nào.</div>
                @endforelse
            </div>

            <div class="tos-pagination">
                <div class="tos-page-info">Hiển thị {{ $snapshots->firstItem() ?? 0 }}-{{ $snapshots->lastItem() ?? 0 }} / {{ $snapshots->total() }} kết quả</div>
                @if ($snapshots->hasPages())
                    <div class="tos-pager">
                        <a class="tos-page-link {{ $snapshots->onFirstPage() ? 'disabled' : '' }}" href="{{ $snapshots->previousPageUrl() ?: '#' }}"><i class="fa fa-chevron-left"></i></a>
                        @foreach ($snapshots->getUrlRange(1, $snapshots->lastPage()) as $page => $url)
                            @if ($page === 1 || $page === $snapshots->lastPage() || abs($page - $snapshots->currentPage()) <= 1)
                                <a class="tos-page-link {{ $page === $snapshots->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                            @elseif (abs($page - $snapshots->currentPage()) === 2)
                                <span class="tos-page-link disabled">...</span>
                            @endif
                        @endforeach
                        <a class="tos-page-link {{ $snapshots->hasMorePages() ? '' : 'disabled' }}" href="{{ $snapshots->nextPageUrl() ?: '#' }}"><i class="fa fa-chevron-right"></i></a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
