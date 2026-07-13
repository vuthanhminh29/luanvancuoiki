@extends('admin.layouts.app')

@section('title', 'Chuyên mục bài viết')

@php
    $statusMeta = fn ($status) => $status === 'ACTIVE'
        ? ['Đang hiển thị', 'active', 'fa-eye']
        : ['Tạm ẩn', 'inactive', 'fa-eye-slash'];
@endphp

@push('styles')
<style>
.pc-page{background:#f5f7fb;min-height:100vh;padding:24px;color:#111827}
.pc-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
.pc-title h4{color:#101828;font-size:26px;font-weight:900;line-height:1.2;margin:0}
.pc-title p{color:#667085;font-size:14px;margin:7px 0 0}
.pc-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}
.pc-btn{align-items:center;border:1px solid transparent;border-radius:7px;cursor:pointer;display:inline-flex;font-size:13px;font-weight:800;gap:7px;justify-content:center;min-height:40px;padding:0 14px;text-decoration:none;white-space:nowrap}
.pc-btn.primary{background:#0f766e;color:#fff}.pc-btn.dark{background:#111827;color:#fff}.pc-btn.light{background:#fff;border-color:#d0d5dd;color:#344054}.pc-btn.soft{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}.pc-btn.danger{background:#fff1f2;border-color:#fecdd3;color:#be123c}
.pc-stats{display:grid;gap:14px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}
.pc-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);padding:16px}
.pc-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-bottom:10px;text-transform:uppercase}
.pc-stat strong{color:#101828;display:block;font-size:28px;font-weight:900;line-height:1}
.pc-stat i{align-items:center;border-radius:8px;display:inline-flex;float:right;height:38px;justify-content:center;width:38px}
.pc-stat.total i{background:#e0f2fe;color:#075985}.pc-stat.active i{background:#dcfce7;color:#15803d}.pc-stat.inactive i{background:#f3f4f6;color:#475467}.pc-stat.posts i{background:#ffedd5;color:#c2410c}
.pc-shell{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}
.pc-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:12px;grid-template-columns:180px minmax(240px,1fr) auto auto;padding:18px}
.pc-field label{color:#667085;display:block;font-size:12px;font-weight:900;margin-bottom:6px}.pc-input,.pc-select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#101828;font-size:14px;font-weight:700;min-height:40px;padding:0 11px;width:100%}
.pc-result{align-items:center;border-bottom:1px solid #eef2f6;color:#667085;display:flex;font-size:13px;justify-content:space-between;gap:12px;padding:13px 18px}.pc-result strong{color:#101828}
.pc-table-wrap{overflow-x:auto}.pc-table{border-collapse:collapse;min-width:980px;width:100%}.pc-table th{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:12px;font-weight:900;letter-spacing:.04em;padding:14px 16px;text-align:left;text-transform:uppercase}.pc-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:14px;padding:15px 16px;vertical-align:middle}.pc-table tr:hover td{background:#fafafa}
.pc-name{display:flex;gap:12px;align-items:center}.pc-icon{align-items:center;background:#eef2ff;border-radius:8px;color:#4338ca;display:inline-flex;flex:0 0 42px;height:42px;justify-content:center;width:42px}.pc-name strong{color:#101828;display:block;font-size:15px;font-weight:900}.pc-name span{color:#667085;display:block;font-size:12px;margin-top:3px}
.pc-slug{background:#f8fafc;border:1px solid #e4e7ec;border-radius:999px;color:#475467;display:inline-flex;font-family:Consolas,monospace;font-size:12px;font-weight:800;max-width:260px;overflow:hidden;padding:6px 10px;text-overflow:ellipsis;white-space:nowrap}
.pc-badge{align-items:center;border:1px solid transparent;border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;gap:6px;min-height:28px;padding:0 10px;white-space:nowrap}.pc-badge.active{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.pc-badge.inactive{background:#f3f4f6;border-color:#d1d5db;color:#475467}
.pc-counts{display:flex;gap:8px;flex-wrap:wrap}.pc-count{background:#f8fafc;border:1px solid #e4e7ec;border-radius:7px;color:#475467;display:inline-flex;font-size:12px;font-weight:900;gap:5px;min-height:30px;align-items:center;padding:0 9px}.pc-count strong{color:#101828}
.pc-row-actions{display:flex;gap:7px;justify-content:flex-end}.pc-row-actions form{margin:0}.pc-empty{color:#667085;padding:46px 16px;text-align:center}.pc-empty i{color:#98a2b3;display:block;font-size:34px;margin-bottom:10px}
.pc-pagination{padding:14px 18px}
@media(max-width:1100px){.pc-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.pc-filter{grid-template-columns:1fr 1fr}}@media(max-width:680px){.pc-page{padding:14px}.pc-head{flex-direction:column}.pc-actions,.pc-btn{width:100%}.pc-stats,.pc-filter{grid-template-columns:1fr}.pc-result{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="pc-page">
    <div class="pc-head">
        <div class="pc-title">
            <h4>Chuyên mục bài viết</h4>
            <p>Sắp xếp nhóm nội dung tin tức, kiểm soát chuyên mục đang hiển thị và số bài trong từng nhóm.</p>
        </div>
        <div class="pc-actions">
            <a class="pc-btn light" href="{{ route('admin.posts.index') }}"><i class="fas fa-newspaper"></i> Bài viết</a>
            <a class="pc-btn primary" href="{{ route('admin.posts.categories.create') }}"><i class="fas fa-plus"></i> Thêm chuyên mục</a>
        </div>
    </div>

    <div class="pc-stats">
        <div class="pc-stat total"><i class="fas fa-layer-group"></i><span>Tổng chuyên mục</span><strong>{{ number_format($summary['total']) }}</strong></div>
        <div class="pc-stat active"><i class="fas fa-eye"></i><span>Đang hiển thị</span><strong>{{ number_format($summary['active']) }}</strong></div>
        <div class="pc-stat inactive"><i class="fas fa-eye-slash"></i><span>Tạm ẩn</span><strong>{{ number_format($summary['inactive']) }}</strong></div>
        <div class="pc-stat posts"><i class="fas fa-file-alt"></i><span>Tổng bài viết</span><strong>{{ number_format($summary['posts']) }}</strong></div>
    </div>

    <div class="pc-shell">
        <form class="pc-filter" method="get">
            <div class="pc-field">
                <label>Trạng thái</label>
                <select class="pc-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="ACTIVE" @selected(($filters['status'] ?? '') === 'ACTIVE')>Đang hiển thị</option>
                    <option value="INACTIVE" @selected(($filters['status'] ?? '') === 'INACTIVE')>Tạm ẩn</option>
                </select>
            </div>
            <div class="pc-field">
                <label>Tìm kiếm</label>
                <input class="pc-input" type="search" name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Tên chuyên mục hoặc slug...">
            </div>
            <button class="pc-btn dark" type="submit"><i class="fas fa-search"></i> Tìm / lọc</button>
            <a class="pc-btn light" href="{{ route('admin.posts.categories') }}">Xóa lọc</a>
        </form>

        <div class="pc-result">
            <span>Đang hiển thị <strong>{{ number_format($categories->count()) }}</strong> / {{ number_format($categories->total()) }} chuyên mục</span>
            @if (($filters['keyword'] ?? '') !== '')
                <span>Từ khóa: <strong>{{ $filters['keyword'] }}</strong></span>
            @endif
        </div>

        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th style="width:72px;">STT</th>
                        <th>Chuyên mục</th>
                        <th>Slug</th>
                        <th style="width:150px;">Trạng thái</th>
                        <th style="width:260px;">Bài viết</th>
                        <th style="width:190px;text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        @php([$statusText, $statusClass, $statusIcon] = $statusMeta($category->status))
                        <tr>
                            <td>{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                            <td>
                                <div class="pc-name">
                                    <span class="pc-icon"><i class="fas fa-folder-open"></i></span>
                                    <div>
                                        <strong>{{ $category->name }}</strong>
                                        <span>ID: {{ $category->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="pc-slug">{{ $category->slug }}</span></td>
                            <td><span class="pc-badge {{ $statusClass }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusText }}</span></td>
                            <td>
                                <div class="pc-counts">
                                    <span class="pc-count"><strong>{{ number_format($category->posts_count) }}</strong> tổng</span>
                                    <span class="pc-count"><strong>{{ number_format($category->published_posts_count) }}</strong> đã đăng</span>
                                    <span class="pc-count"><strong>{{ number_format($category->draft_posts_count) }}</strong> nháp</span>
                                </div>
                            </td>
                            <td>
                                <div class="pc-row-actions">
                                    <a class="pc-btn soft" href="{{ route('admin.posts.categories.edit', $category) }}"><i class="fas fa-pen"></i> Sửa</a>
                                    <form method="post" action="{{ route('admin.posts.categories.hidden', $category) }}" onsubmit="return confirm('Ẩn chuyên mục này? Các bài viết vẫn được giữ lại.');">
                                        @csrf
                                        @method('PATCH')
                                        <button class="pc-btn danger" type="submit"><i class="fas fa-eye-slash"></i> Ẩn</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="pc-empty">
                                <i class="fas fa-folder-open"></i>
                                Chưa có chuyên mục phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pc-pagination">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection
