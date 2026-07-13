@extends('admin.layouts.app')

@section('title', 'Hoàn đổi')

@push('styles')
<style>
.ra-page { background:#f5f7fb; min-height:100vh; padding:24px; color:#111827; }
.ra-shell { background:#fff; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
.ra-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; padding:20px 22px; border-bottom:1px solid #e5e7eb; }
.ra-title h4 { margin:0; font-size:22px; font-weight:800; letter-spacing:0; color:#111827; }
.ra-title p { margin:6px 0 0; color:#6b7280; font-size:13px; }
.ra-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
.ra-btn { min-height:38px; border-radius:6px; padding:0 13px; display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid transparent; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; white-space:nowrap; }
.ra-btn.primary { background:#111827; color:#fff; }
.ra-btn.light { background:#fff; color:#374151; border-color:#d1d5db; }
.ra-btn.soft { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.ra-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; padding:18px 22px; border-bottom:1px solid #eef0f3; background:#fbfcfd; }
.ra-stat { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:13px; }
.ra-stat span { display:block; color:#6b7280; font-size:12px; font-weight:700; }
.ra-stat strong { display:block; margin-top:4px; font-size:22px; color:#111827; }
.ra-filter { display:grid; grid-template-columns:1fr 1fr 1.5fr auto auto; gap:10px; padding:18px 22px; border-bottom:1px solid #eef0f3; align-items:end; }
.ra-field label { display:block; margin:0 0 5px; color:#6b7280; font-size:12px; font-weight:800; }
.ra-input, .ra-select { width:100%; min-height:38px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827; padding:0 10px; font-size:13px; }
.ra-result { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 22px; border-bottom:1px solid #eef0f3; color:#6b7280; font-size:13px; }
.ra-result strong { color:#111827; }
.ra-table-wrap { overflow-x:auto; padding:0 22px 22px; }
.ra-table { width:100%; min-width:760px; border-collapse:collapse; }
.ra-table th { padding:13px 10px; color:#6b7280; background:#fff; border-bottom:1px solid #e5e7eb; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
.ra-table td { padding:13px 10px; border-bottom:1px solid #f1f3f5; vertical-align:middle; color:#111827; font-size:14px; }
.ra-table tr:hover td { background:#fafafa; }
.ra-code { display:inline-flex; align-items:center; gap:6px; color:#111827; text-decoration:none; font-weight:800; }
.ra-code:hover { color:#2563eb; }
.ra-customer strong { display:block; font-size:14px; }
.ra-customer span { display:block; margin-top:3px; color:#6b7280; font-size:12px; }
.ra-badge, .ra-type { display:inline-flex; align-items:center; gap:6px; min-height:28px; padding:0 10px; border-radius:999px; font-size:12px; font-weight:800; border:1px solid transparent; white-space:nowrap; }
.ra-badge.warning { color:#92400e; background:#fffbeb; border-color:#fde68a; }
.ra-badge.info { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.ra-badge.moving { color:#6d28d9; background:#f5f3ff; border-color:#ddd6fe; }
.ra-badge.success { color:#047857; background:#ecfdf5; border-color:#a7f3d0; }
.ra-badge.danger { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.ra-badge.dark { color:#374151; background:#f3f4f6; border-color:#d1d5db; }
.ra-type.return { color:#075985; background:#e0f2fe; border-color:#bae6fd; }
.ra-type.exchange { color:#6d28d9; background:#f5f3ff; border-color:#ddd6fe; }
.ra-empty { padding:42px 16px; text-align:center; color:#6b7280; }
.ra-empty i { display:block; font-size:34px; color:#9ca3af; margin-bottom:10px; }
@media (max-width:1100px) { .ra-page { padding:14px; } .ra-head { flex-direction:column; } .ra-actions { justify-content:flex-start; } .ra-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .ra-filter { grid-template-columns:1fr 1fr; } }
@media (max-width:620px) { .ra-stats, .ra-filter { grid-template-columns:1fr; } .ra-result { align-items:flex-start; flex-direction:column; } .ra-btn { width:100%; } }
</style>
@endpush

@section('content')
@php
    $statusMeta = fn ($status) => match ($status) {
        'PENDING' => ['Chờ xét duyệt', 'warning', 'fa-clock'],
        'COMPLETED' => ['Đã xử lý', 'success', 'fa-check-circle'],
        'REJECTED' => ['Từ chối', 'danger', 'fa-times-circle'],
        'CANCELLED' => ['Đã hủy', 'dark', 'fa-ban'],
        'APPROVED' => ['Đã duyệt', 'info', 'fa-clipboard-check'],
        'RECEIVED' => ['Đã nhận hàng', 'moving', 'fa-box-open'],
        default => [$status, 'dark', 'fa-circle-question'],
    };
    $typeMeta = fn ($type) => $type === 'EXCHANGE'
        ? ['Đổi hàng', 'exchange', 'fa-right-left']
        : ['Hoàn trả', 'return', 'fa-rotate-left'];
    $collection = $requests->getCollection();
@endphp

<div class="ra-page">
    <div class="ra-shell">
        <div class="ra-head">
            <div class="ra-title">
                <h4>Yêu cầu hoàn/đổi</h4>
                <p>Kiểm tra tình trạng kính, xử lý hoàn trả, đổi hàng và phản hồi cho khách.</p>
            </div>
            <div class="ra-actions">
                <a href="{{ route('admin.returns.index', ['status' => 'PENDING']) }}" class="ra-btn light"><i class="fas fa-clock"></i> Chờ xử lý</a>
                <a href="{{ route('admin.orders.index') }}" class="ra-btn primary"><i class="fas fa-receipt"></i> Đơn hàng</a>
            </div>
        </div>

        <div class="ra-stats">
            <div class="ra-stat"><span>Tổng yêu cầu</span><strong>{{ number_format($requests->total()) }}</strong></div>
            <div class="ra-stat"><span>Chờ xử lý</span><strong>{{ number_format($collection->where('status', 'PENDING')->count()) }}</strong></div>
            <div class="ra-stat"><span>Hoàn trả</span><strong>{{ number_format($collection->where('type', 'RETURN')->count()) }}</strong></div>
            <div class="ra-stat"><span>Đổi hàng</span><strong>{{ number_format($collection->where('type', 'EXCHANGE')->count()) }}</strong></div>
            <div class="ra-stat"><span>Từ chối</span><strong>{{ number_format($collection->where('status', 'REJECTED')->count()) }}</strong></div>
        </div>

        <form class="ra-filter" method="get">
            <div class="ra-field">
                <label>Trạng thái</label>
                <select name="status" class="ra-select">
                    <option value="">Tất cả trạng thái</option>
                    @foreach (['PENDING' => 'Chờ xét duyệt', 'APPROVED' => 'Đã duyệt', 'RECEIVED' => 'Đã nhận hàng', 'COMPLETED' => 'Đã xử lý', 'REJECTED' => 'Từ chối', 'CANCELLED' => 'Đã hủy'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ra-field">
                <label>Loại yêu cầu</label>
                <select name="type" class="ra-select">
                    <option value="">Tất cả loại</option>
                    <option value="RETURN" @selected(request('type') === 'RETURN')>Hoàn trả</option>
                    <option value="EXCHANGE" @selected(request('type') === 'EXCHANGE')>Đổi hàng</option>
                </select>
            </div>
            <div class="ra-field">
                <label>Tìm kiếm</label>
                <input type="text" name="keyword" class="ra-input" placeholder="Mã yêu cầu, mã đơn, tên, email, lý do..." value="{{ request('keyword') }}">
            </div>
            <button class="ra-btn primary" type="submit"><i class="fas fa-search"></i> Tìm / lọc</button>
            <a class="ra-btn light" href="{{ route('admin.returns.index') }}">Xóa lọc</a>
        </form>

        <div class="ra-result">
            <span>Đang hiển thị <strong>{{ number_format($requests->count()) }}</strong> yêu cầu</span>
            @if (request('keyword'))
                <span>Từ khóa: <strong>{{ request('keyword') }}</strong></span>
            @endif
        </div>

        <div class="ra-table-wrap">
            <table class="ra-table">
                <thead>
                    <tr>
                        <th style="width:66px;">STT</th>
                        <th style="width:150px;">Mã yêu cầu</th>
                        <th style="width:130px;">Mã đơn</th>
                        <th>Khách hàng</th>
                        <th style="width:130px;">Loại</th>
                        <th>Trạng thái</th>
                        <th style="width:110px;text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $request)
                        @php
                            [$typeText, $typeClass, $typeIcon] = $typeMeta($request->type);
                            [$statusText, $statusClass, $statusIcon] = $statusMeta($request->status);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($requests->currentPage() - 1) * $requests->perPage() }}</td>
                            <td>
                                <a class="ra-code" href="{{ route('admin.returns.show', $request) }}">
                                    <i class="fas fa-rotate-left"></i>{{ $request->return_code }}
                                </a>
                            </td>
                            <td>{{ $request->order->order_code }}</td>
                            <td class="ra-customer">
                                <strong>{{ $request->user->full_name ?? '-' }}</strong>
                                <span>{{ $request->user->email ?? '' }}</span>
                            </td>
                            <td><span class="ra-type {{ $typeClass }}"><i class="fas {{ $typeIcon }}"></i>{{ $typeText }}</span></td>
                            <td><span class="ra-badge {{ $statusClass }}"><i class="fas {{ $statusIcon }}"></i>{{ $statusText }}</span></td>
                            <td style="text-align:right;">
                                <a class="ra-btn soft" href="{{ route('admin.returns.show', $request) }}"><i class="fas fa-eye"></i> Xem</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="ra-empty">
                                <i class="fas fa-inbox"></i>
                                Chưa có yêu cầu hoàn/đổi phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
