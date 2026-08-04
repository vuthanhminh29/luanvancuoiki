@extends('admin.layouts.app')

@section('title', 'Slider & banner')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : 'Không giới hạn';
    $positionLabel = fn ($value) => match ($value) {
        'HOME_SLIDER' => 'Slider trang chủ',
        'HOME_BANNER_1' => 'Banner trang chủ 1',
        'HOME_BANNER_2' => 'Banner trang chủ 2',
        'CATEGORY_BANNER' => 'Banner danh mục',
        'PRODUCT_BANNER' => 'Banner sản phẩm',
        default => $value ?: '-',
    };
    $platformLabel = fn ($value) => match ($value) {
        'DESKTOP' => 'Desktop',
        'MOBILE' => 'Mobile',
        'BOTH' => 'Cả hai',
        default => $value ?: '-',
    };
    $statusBadge = fn ($value) => match ($value) {
        'ACTIVE' => ['Đang hiển thị', 'success'],
        'INACTIVE' => ['Tạm ẩn', 'muted'],
        default => [$value ?: '-', 'muted'],
    };
@endphp

@push('styles')
<style>
.bn-page{background:#f4f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.bn-inner{max-width:1500px;margin:0 auto}.bn-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.bn-title small{color:#2563eb;font-size:13px;font-weight:900;text-transform:uppercase}.bn-title h4{color:#111827;font-size:28px;font-weight:900;line-height:1.18;margin:6px 0}.bn-title p{color:#667085;font-size:14px;margin:0}.bn-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}.bn-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.bn-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.bn-btn:hover{filter:brightness(.98);color:#111827}.bn-btn.primary:hover{color:#fff}
.bn-summary{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}.bn-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.bn-stat i{align-items:center;border-radius:8px;display:inline-flex;height:34px;justify-content:center;width:34px}.bn-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.bn-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.bn-stat:nth-child(1) i{background:#eef2ff;color:#4f46e5}.bn-stat:nth-child(2) i{background:#dcfce7;color:#166534}.bn-stat:nth-child(3) i{background:#f3f4f6;color:#4b5563}.bn-stat:nth-child(4) i{background:#e0f2fe;color:#0369a1}
.bn-preview{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);margin-bottom:14px;overflow:hidden}.bn-preview-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:14px 16px}.bn-preview-head h5{color:#111827;font-size:17px;font-weight:900;margin:0}.bn-preview-head span{color:#667085;font-size:12px;font-weight:800}.bn-preview-grid{display:grid;gap:12px;grid-template-columns:repeat(5,minmax(0,1fr));padding:14px}.bn-preview-item{background:#f8fafc;border:1px solid #e4e7ec;border-radius:8px;overflow:hidden}.bn-preview-item img{aspect-ratio:16/7;background:#eef2f6;display:block;height:auto;object-fit:cover;width:100%}.bn-preview-copy{padding:10px 11px}.bn-preview-copy strong{color:#111827;display:block;font-size:13px;font-weight:900;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.bn-preview-copy small{color:#667085;display:block;font-size:12px;font-weight:800;margin-top:4px}.bn-empty{color:#667085;font-size:14px;font-weight:700;padding:32px 12px;text-align:center}
.bn-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.bn-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:10px;grid-template-columns:minmax(220px,1fr) minmax(160px,.7fr) minmax(150px,.65fr) minmax(135px,.55fr) auto;padding:14px}.bn-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:6px;text-transform:uppercase}.bn-field input,.bn-field select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.bn-table-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.bn-table-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.bn-table-head small{color:#667085;font-size:12px;font-weight:800}
.bn-list-head,.bn-row{align-items:center;display:grid;gap:12px;grid-template-columns:190px minmax(230px,1fr) minmax(150px,.72fr) minmax(110px,.45fr) minmax(170px,.68fr) 92px}.bn-list-head{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:0;padding:10px 14px;text-transform:uppercase}.bn-row{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;min-height:108px;padding:12px 14px}.bn-row:hover{background:#fafafa}.bn-img{aspect-ratio:16/7;background:#f8fafc;border:1px solid #e4e7ec;border-radius:8px;display:block;height:auto;object-fit:cover;width:180px}.bn-name{color:#111827;font-size:13px;font-weight:900;line-height:1.35}.bn-sub{color:#667085;font-size:12px;font-weight:700;line-height:1.45;margin-top:3px}.bn-cell{min-width:0}.bn-clip{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.bn-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px;white-space:nowrap}.bn-badge.success{background:#dcfce7;color:#166534}.bn-badge.muted{background:#f3f4f6;color:#4b5563}.bn-number{color:#111827;font-weight:900;white-space:nowrap}.bn-actions-row{display:flex;gap:6px;justify-content:flex-end}.bn-actions-row form{margin:0}.bn-icon-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;height:34px;justify-content:center;width:36px}.bn-icon-btn.primary{border-color:#bfdbfe;color:#1d4ed8}.bn-icon-btn.danger{border-color:#fecaca;color:#991b1b}.bn-icon-btn.disabled{background:#f8fafc;color:#98a2b3}.bn-pagination{align-items:center;display:flex;gap:10px;justify-content:space-between;padding:13px 14px}.bn-page-info{color:#667085;font-size:12px;font-weight:800}.bn-pager{display:flex;gap:6px;justify-content:flex-end}.bn-page-link{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:6px;color:#344054;display:inline-flex;font-size:12px;font-weight:900;height:32px;justify-content:center;min-width:32px;padding:0 10px;text-decoration:none}.bn-page-link.active{background:#2563eb;border-color:#2563eb;color:#fff}.bn-page-link.disabled{background:#f8fafc;color:#98a2b3;pointer-events:none}
@media(max-width:1180px){.bn-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.bn-preview-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.bn-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.bn-filter .bn-actions{grid-column:1/-1}.bn-list-head{display:none}.bn-row{border:1px solid #eef2f6;border-radius:8px;grid-template-columns:180px 1fr auto;margin:10px 12px;min-height:auto}.bn-actions-row{align-self:start;grid-column:3;grid-row:1/span 5}}@media(max-width:760px){.bn-page{margin:-24px -12px 0;padding:16px 12px}.bn-head,.bn-preview-head{align-items:flex-start;flex-direction:column}.bn-actions,.bn-btn{width:100%}.bn-summary,.bn-preview-grid,.bn-filter{grid-template-columns:1fr}.bn-row{grid-template-columns:1fr}.bn-img{width:100%}.bn-actions-row{grid-column:1;grid-row:auto;justify-content:flex-start}.bn-pagination{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="bn-page">
    <div class="bn-inner">
        <div class="bn-head">
            <div class="bn-title">
                <small>Nội dung hiển thị</small>
                <h4>Quản lý slider & banner</h4>
                <p>Theo dõi ảnh đang chạy trên trang chủ, thứ tự ưu tiên và trạng thái từng banner.</p>
            </div>
            <div class="bn-actions">
                <a class="bn-btn" href="{{ route('home') }}" target="_blank" rel="noopener"><i class="fa fa-external-link-alt"></i> Xem website</a>
                <a class="bn-btn primary" href="{{ route('admin.banners.create') }}"><i class="fa fa-plus"></i> Thêm banner</a>
            </div>
        </div>

        <div class="bn-summary">
            <div class="bn-stat"><i class="fa fa-images"></i><span>Tổng banner</span><strong>{{ $num($summary['total']) }}</strong></div>
            <div class="bn-stat"><i class="fa fa-eye"></i><span>Đang hiển thị</span><strong>{{ $num($summary['active']) }}</strong></div>
            <div class="bn-stat"><i class="fa fa-eye-slash"></i><span>Tạm ẩn</span><strong>{{ $num($summary['inactive']) }}</strong></div>
            <div class="bn-stat"><i class="fa fa-home"></i><span>Slider trang chủ</span><strong>{{ $num($summary['home']) }}</strong></div>
        </div>

        <section class="bn-preview">
            <div class="bn-preview-head">
                <div>
                    <h5>Slider đang chạy</h5>
                    <span>Ưu tiên thấp hơn sẽ xuất hiện trước trên trang chủ.</span>
                </div>
                <a class="bn-btn" href="{{ route('admin.banners.index', ['position' => 'HOME_SLIDER', 'status' => 'ACTIVE']) }}"><i class="fa fa-filter"></i> Xem riêng slider</a>
            </div>
            @if (($sliderPreview ?? collect())->count())
                <div class="bn-preview-grid">
                    @foreach ($sliderPreview as $preview)
                        <a class="bn-preview-item" href="{{ route('admin.banners.edit', $preview) }}" title="Sửa {{ $preview->title }}">
                            <img src="{{ $preview->image_src }}" alt="{{ $preview->title }}">
                            <span class="bn-preview-copy">
                                <strong>{{ $preview->title }}</strong>
                                <small>#{{ $preview->priority }} · {{ $platformLabel($preview->platform) }}</small>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bn-empty">Chưa có slider trang chủ nào đang hiển thị.</div>
            @endif
        </section>

        <div class="bn-card">
            <form class="bn-filter" method="get" action="{{ route('admin.banners.index') }}">
                <div class="bn-field">
                    <label>Tìm kiếm</label>
                    <input name="keyword" value="{{ $keyword }}" placeholder="Tiêu đề, link hoặc tên file ảnh">
                </div>
                <div class="bn-field">
                    <label>Vị trí</label>
                    <select name="position">
                        <option value="">Tất cả vị trí</option>
                        <option value="HOME_SLIDER" @selected($position === 'HOME_SLIDER')>Slider trang chủ</option>
                        <option value="HOME_BANNER_1" @selected($position === 'HOME_BANNER_1')>Banner trang chủ 1</option>
                        <option value="HOME_BANNER_2" @selected($position === 'HOME_BANNER_2')>Banner trang chủ 2</option>
                        <option value="CATEGORY_BANNER" @selected($position === 'CATEGORY_BANNER')>Banner danh mục</option>
                        <option value="PRODUCT_BANNER" @selected($position === 'PRODUCT_BANNER')>Banner sản phẩm</option>
                    </select>
                </div>
                <div class="bn-field">
                    <label>Nền tảng</label>
                    <select name="platform">
                        <option value="">Tất cả nền tảng</option>
                        <option value="DESKTOP" @selected($platform === 'DESKTOP')>Desktop</option>
                        <option value="MOBILE" @selected($platform === 'MOBILE')>Mobile</option>
                        <option value="BOTH" @selected($platform === 'BOTH')>Cả hai</option>
                    </select>
                </div>
                <div class="bn-field">
                    <label>Trạng thái</label>
                    <select name="status">
                        <option value="">Tất cả</option>
                        <option value="ACTIVE" @selected($status === 'ACTIVE')>Đang hiển thị</option>
                        <option value="INACTIVE" @selected($status === 'INACTIVE')>Tạm ẩn</option>
                    </select>
                </div>
                <div class="bn-actions">
                    <button class="bn-btn primary" type="submit"><i class="fa fa-search"></i> Lọc</button>
                    <a class="bn-btn" href="{{ route('admin.banners.index') }}"><i class="fa fa-redo"></i> Xóa lọc</a>
                </div>
            </form>

            <div class="bn-table-head">
                <h6>Danh sách banner</h6>
                <small>{{ $banners->total() }} banner</small>
            </div>

            <div class="bn-list">
                <div class="bn-list-head">
                    <div>Hình ảnh</div>
                    <div>Banner</div>
                    <div>Vị trí</div>
                    <div>Ưu tiên</div>
                    <div>Trạng thái</div>
                    <div>Thao tác</div>
                </div>

                @forelse ($banners as $banner)
                    @php
                        [$label, $class] = $statusBadge($banner->status);
                    @endphp
                    <div class="bn-row">
                        <div><img class="bn-img" src="{{ $banner->image_src }}" alt="{{ $banner->title }}"></div>
                        <div class="bn-cell">
                            <div class="bn-name">{{ $banner->title }}</div>
                            <div class="bn-sub bn-clip">{{ $banner->link_url ?: 'Chưa gắn đường dẫn' }}</div>
                            <div class="bn-sub bn-clip">Ảnh: {{ $banner->image_url }}</div>
                        </div>
                        <div class="bn-cell">
                            <div class="bn-name">{{ $positionLabel($banner->position) }}</div>
                            <div class="bn-sub">{{ $platformLabel($banner->platform) }}</div>
                        </div>
                        <div class="bn-number">#{{ $banner->priority }}</div>
                        <div class="bn-cell">
                            <span class="bn-badge {{ $class }}">{{ $label }}</span>
                            <div class="bn-sub">{{ $date($banner->start_at) }} - {{ $date($banner->end_at) }}</div>
                        </div>
                        <div class="bn-actions-row">
                            <a class="bn-icon-btn primary" href="{{ route('admin.banners.edit', $banner) }}" title="Sửa" aria-label="Sửa banner"><i class="fa fa-edit"></i></a>
                            @if ($banner->status === 'ACTIVE')
                                <form method="post" action="{{ route('admin.banners.hidden', $banner) }}" onsubmit="return confirm('Ẩn banner này?')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="bn-icon-btn danger" title="Ẩn" aria-label="Ẩn banner"><i class="fa fa-eye-slash"></i></button>
                                </form>
                            @else
                                <span class="bn-icon-btn disabled" title="Đã ẩn" aria-label="Đã ẩn"><i class="fa fa-eye-slash"></i></span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bn-empty">Chưa có banner phù hợp.</div>
                @endforelse
            </div>

            <div class="bn-pagination">
                <div class="bn-page-info">Hiển thị {{ $banners->firstItem() ?? 0 }}-{{ $banners->lastItem() ?? 0 }} / {{ $banners->total() }} banner</div>
                @if ($banners->hasPages())
                    <div class="bn-pager">
                        <a class="bn-page-link {{ $banners->onFirstPage() ? 'disabled' : '' }}" href="{{ $banners->previousPageUrl() ?: '#' }}"><i class="fa fa-chevron-left"></i></a>
                        @foreach ($banners->getUrlRange(1, $banners->lastPage()) as $page => $url)
                            <a class="bn-page-link {{ $page === $banners->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                        @endforeach
                        <a class="bn-page-link {{ $banners->hasMorePages() ? '' : 'disabled' }}" href="{{ $banners->nextPageUrl() ?: '#' }}"><i class="fa fa-chevron-right"></i></a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
