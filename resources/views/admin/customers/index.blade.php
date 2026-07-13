@extends('admin.layouts.app')

@section('title', 'Thành viên')

@php
    $num = fn ($value) => number_format((float) $value, 0, ',', '.');
    $money = fn ($value) => $num($value) . 'đ';
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    $statusBadge = fn ($status) => match ($status) {
        'ACTIVE' => ['Hoạt động', 'success'],
        'LOCKED' => ['Bị khóa', 'danger'],
        default => [$status ?: '-', 'muted'],
    };
    $roleBadge = fn ($code) => match ($code) {
        'ADMIN' => 'danger',
        'STAFF' => 'warning',
        'USER' => 'info',
        default => 'muted',
    };
@endphp

@push('styles')
<style>
.mem-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.mem-inner{max-width:1500px;margin:0 auto}.mem-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.mem-title small{color:#0f8a7a;font-size:13px;font-weight:900;text-transform:uppercase}.mem-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.mem-title p{color:#667085;font-size:14px;margin:0}.mem-actions{display:flex;gap:9px;justify-content:flex-end}.mem-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.mem-btn.primary{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.mem-btn.danger{background:#fee2e2;border-color:#fecaca;color:#991b1b}.mem-btn.success{background:#dcfce7;border-color:#bbf7d0;color:#166534}.mem-btn:hover{filter:brightness(.98);color:inherit}.mem-btn.primary:hover{color:#fff}
.mem-summary{display:grid;gap:12px;grid-template-columns:repeat(5,minmax(0,1fr));margin-bottom:14px}.mem-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:96px;padding:15px}.mem-stat i{align-items:center;border-radius:8px;display:inline-flex;height:34px;justify-content:center;width:34px}.mem-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.mem-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.mem-stat:nth-child(1) i{background:#eef2ff;color:#4f46e5}.mem-stat:nth-child(2) i{background:#dcfce7;color:#166534}.mem-stat:nth-child(3) i{background:#e0f2fe;color:#0369a1}.mem-stat:nth-child(4) i{background:#fef3c7;color:#92400e}.mem-stat:nth-child(5) i{background:#fee2e2;color:#991b1b}
.mem-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.mem-filter{align-items:end;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:grid;gap:10px;grid-template-columns:minmax(240px,1.3fr) minmax(150px,.7fr) minmax(150px,.7fr) auto;padding:14px}.mem-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:6px;text-transform:uppercase}.mem-field input,.mem-field select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.mem-table-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}.mem-table-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.mem-table-head small{color:#667085;font-size:12px;font-weight:800}
.mem-list{display:grid}.mem-list-head,.mem-row{display:grid;gap:10px;grid-template-columns:44px minmax(200px,1.25fr) minmax(180px,1fr) minmax(118px,.7fr) minmax(122px,.75fr) minmax(120px,.7fr) 78px;align-items:center}.mem-list-head{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:.03em;padding:10px 12px;text-transform:uppercase}.mem-row{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;min-height:74px;padding:12px}.mem-row:hover{background:#fafafa}.mem-user{align-items:center;display:flex;gap:10px;min-width:0}.mem-avatar{align-items:center;background:linear-gradient(135deg,#e0f2fe,#dbeafe);border:1px solid #bae6fd;border-radius:50%;color:#0369a1;display:inline-flex;flex:0 0 38px;font-size:13px;font-weight:900;height:38px;justify-content:center;overflow:hidden;text-transform:uppercase;width:38px}.mem-name{color:#111827;font-size:13px;font-weight:900;line-height:1.35;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.mem-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.mem-cell{min-width:0}.mem-clip{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.mem-badges{display:flex;flex-wrap:wrap;gap:5px}.mem-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px;white-space:nowrap}.mem-badge.success{background:#dcfce7;color:#166534}.mem-badge.warning{background:#fef3c7;color:#92400e}.mem-badge.danger{background:#fee2e2;color:#991b1b}.mem-badge.info{background:#dbeafe;color:#1d4ed8}.mem-badge.dark{background:#e5e7eb;color:#111827}.mem-badge.purple{background:#ede9fe;color:#6d28d9}.mem-badge.muted{background:#f3f4f6;color:#4b5563}.mem-number{color:#111827;font-weight:900;white-space:nowrap}.mem-kpi{display:grid;gap:3px}.mem-actions-row{display:flex;gap:6px;justify-content:flex-end}.mem-actions-row form{margin:0}.mem-icon-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;height:34px;justify-content:center;width:36px}.mem-icon-btn.primary{border-color:#bfdbfe;color:#1d4ed8}.mem-icon-btn.success{border-color:#bbf7d0;color:#166534}.mem-icon-btn.danger{border-color:#fecaca;color:#991b1b}.mem-empty{color:#667085;padding:32px 12px;text-align:center}.mem-pagination{align-items:center;display:flex;gap:10px;justify-content:space-between;padding:13px 14px}.mem-page-info{color:#667085;font-size:12px;font-weight:800}.mem-pager{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}.mem-page-link{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:6px;color:#344054;display:inline-flex;font-size:12px;font-weight:900;height:32px;justify-content:center;min-width:32px;padding:0 10px;text-decoration:none}.mem-page-link:hover{background:#f8fafc;color:#111827}.mem-page-link.active{background:#0f8a7a;border-color:#0f8a7a;color:#fff}.mem-page-link.disabled{background:#f8fafc;color:#98a2b3;pointer-events:none}
@media(max-width:1180px){.mem-summary{grid-template-columns:repeat(3,minmax(0,1fr))}.mem-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.mem-filter .mem-actions{grid-column:1/-1}.mem-list-head{display:none}.mem-row{border:1px solid #eef2f6;border-radius:8px;grid-template-columns:1fr auto;margin:10px 12px;min-height:auto}.mem-row:hover{background:#fff}.mem-row .mem-id{grid-column:1/-1}.mem-row .mem-cell{grid-column:1/2}.mem-row .mem-actions-row{align-self:start;grid-column:2;grid-row:2/span 4}}@media(max-width:760px){.mem-page{margin:-24px -12px 0;padding:16px 12px}.mem-head{flex-direction:column}.mem-actions,.mem-btn{width:100%}.mem-summary,.mem-filter{grid-template-columns:1fr}.mem-row{grid-template-columns:1fr}.mem-row .mem-actions-row{grid-column:1;grid-row:auto;justify-content:flex-start}}
</style>
@endpush

@section('content')
<div class="mem-page">
    <div class="mem-inner">
        <div class="mem-head">
            <div class="mem-title">
                <small>Mắt kính admin</small>
                <h4>Quản lý thành viên</h4>
                <p>Theo dõi tài khoản, vai trò, trạng thái đăng nhập và lịch sử mua hàng của khách.</p>
            </div>
            <div class="mem-actions">
                <a class="mem-btn primary" href="{{ route('admin.customers.create') }}"><i class="fa fa-user-plus"></i> Thêm tài khoản</a>
            </div>
        </div>

        <div class="mem-summary">
            <div class="mem-stat"><i class="fa fa-users"></i><span>Tổng thành viên</span><strong>{{ $num($summary['total']) }}</strong></div>
            <div class="mem-stat"><i class="fa fa-user-check"></i><span>Đang hoạt động</span><strong>{{ $num($summary['active']) }}</strong></div>
            <div class="mem-stat"><i class="fa fa-shopping-bag"></i><span>Khách hàng</span><strong>{{ $num($summary['customers']) }}</strong></div>
            <div class="mem-stat"><i class="fa fa-user-tie"></i><span>Nhân sự</span><strong>{{ $num($summary['staff']) }}</strong></div>
            <div class="mem-stat"><i class="fa fa-user-lock"></i><span>Bị khóa</span><strong>{{ $num($summary['locked']) }}</strong></div>
        </div>

        <div class="mem-card">
            <form class="mem-filter" method="get" action="{{ route('admin.customers.index') }}">
                <div class="mem-field">
                    <label>Tìm kiếm</label>
                    <input name="keyword" value="{{ $keyword }}" placeholder="Tên, email, số điện thoại hoặc ID">
                </div>
                <div class="mem-field">
                    <label>Vai trò</label>
                    <select name="role">
                        <option value="">Tất cả vai trò</option>
                        @foreach ($roles as $item)
                            <option value="{{ $item->code }}" @selected($role === $item->code)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mem-field">
                    <label>Trạng thái</label>
                    <select name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="ACTIVE" @selected($status === 'ACTIVE')>Hoạt động</option>
                        <option value="LOCKED" @selected($status === 'LOCKED')>Bị khóa</option>
                    </select>
                </div>
                <div class="mem-actions">
                    <button class="mem-btn primary" type="submit"><i class="fa fa-search"></i> Lọc</button>
                    <a class="mem-btn" href="{{ route('admin.customers.index') }}"><i class="fa fa-redo"></i> Xóa lọc</a>
                </div>
            </form>

            <div class="mem-table-head">
                <h6>Danh sách tài khoản</h6>
                <small>{{ $users->total() }} tài khoản</small>
            </div>

            <div class="mem-list">
                <div class="mem-list-head">
                    <div>#</div>
                    <div>Thành viên</div>
                    <div>Liên hệ</div>
                    <div>Vai trò</div>
                    <div>Trạng thái</div>
                    <div>Hoạt động</div>
                    <div>Thao tác</div>
                </div>

                @forelse ($users as $user)
                    @php
                        [$label, $class] = $statusBadge($user->status);
                        $rolesForUser = $roleMap[$user->id] ?? [];
                        $initial = mb_strtoupper(mb_substr($user->full_name ?: $user->email, 0, 1));
                    @endphp
                    <div class="mem-row">
                        <div class="mem-number mem-id">#{{ $user->id }}</div>

                        <div class="mem-user mem-cell">
                            <div class="mem-avatar">{{ $initial }}</div>
                            <div class="mem-cell">
                                <div class="mem-name">{{ $user->full_name }}</div>
                                <div class="mem-sub">ID: {{ $user->id }} · {{ $user->provider }}</div>
                            </div>
                        </div>

                        <div class="mem-cell">
                            <div class="mem-clip">{{ $user->email }}</div>
                            <div class="mem-sub">{{ $user->phone ?: 'Chưa có số điện thoại' }}</div>
                        </div>

                        <div class="mem-cell">
                            <div class="mem-badges">
                                @forelse ($rolesForUser as $roleItem)
                                    <span class="mem-badge {{ $roleBadge($roleItem['code']) }}">{{ $roleItem['name'] }}</span>
                                @empty
                                    <span class="mem-badge muted">Chưa gán</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="mem-cell">
                            <span class="mem-badge {{ $class }}">{{ $label }}</span>
                            <div class="mem-sub">Đăng nhập: {{ $date($user->last_login_at) }}</div>
                        </div>

                        <div class="mem-cell mem-kpi">
                            <div><span class="mem-number">{{ $num($user->orders_count) }}</span> đơn</div>
                            <div class="mem-sub">Đã mua {{ $money($user->delivered_total ?? 0) }}</div>
                        </div>

                        <div class="mem-actions-row">
                            <a class="mem-icon-btn primary" href="{{ route('admin.customers.edit', $user) }}" title="Sửa"><i class="fa fa-edit"></i></a>
                            @if ($user->status === 'LOCKED')
                                <form method="post" action="{{ route('admin.customers.status', $user) }}" onsubmit="return confirm('Mở khóa tài khoản này?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="ACTIVE">
                                    <button class="mem-icon-btn success" title="Mở khóa"><i class="fa fa-unlock"></i></button>
                                </form>
                            @else
                                <form method="post" action="{{ route('admin.customers.status', $user) }}" onsubmit="return confirm('Khóa tài khoản này?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="LOCKED">
                                    <button class="mem-icon-btn danger" title="Khóa"><i class="fa fa-lock"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="mem-empty">Không tìm thấy thành viên phù hợp.</div>
                @endforelse
            </div>

            <div class="mem-pagination">
                <div class="mem-page-info">
                    Hiển thị {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} / {{ $users->total() }} tài khoản
                </div>
                @if ($users->hasPages())
                    <div class="mem-pager">
                        <a class="mem-page-link {{ $users->onFirstPage() ? 'disabled' : '' }}" href="{{ $users->previousPageUrl() ?: '#' }}" aria-label="Trang trước">
                            <i class="fa fa-chevron-left"></i>
                        </a>
                        @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                            @if ($page === 1 || $page === $users->lastPage() || abs($page - $users->currentPage()) <= 1)
                                <a class="mem-page-link {{ $page === $users->currentPage() ? 'active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                            @elseif (abs($page - $users->currentPage()) === 2)
                                <span class="mem-page-link disabled">...</span>
                            @endif
                        @endforeach
                        <a class="mem-page-link {{ $users->hasMorePages() ? '' : 'disabled' }}" href="{{ $users->nextPageUrl() ?: '#' }}" aria-label="Trang sau">
                            <i class="fa fa-chevron-right"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
