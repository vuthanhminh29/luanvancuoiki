@extends('admin.layouts.app')

@section('title', 'Lịch đo mắt')

@php
    $statusClass = fn ($status) => match ($status) {
        'PENDING' => 'warning',
        'CONFIRMED' => 'info',
        'COMPLETED' => 'success',
        'CANCELLED' => 'danger',
        'NO_SHOW' => 'muted',
        default => 'muted',
    };
@endphp

@push('styles')
<style>
.ap-page{background:#f5f7fb;color:#172033;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}.ap-inner{max-width:1500px;margin:0 auto}.ap-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}.ap-title small{color:#2563eb;font-size:13px;font-weight:900;text-transform:uppercase}.ap-title h4{color:#111827;font-size:27px;font-weight:900;line-height:1.2;margin:6px 0}.ap-title p{color:#667085;font-size:14px;margin:0}.ap-summary{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}.ap-stat{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);min-height:94px;padding:15px}.ap-stat i{align-items:center;background:#eff6ff;border-radius:8px;color:#2563eb;display:inline-flex;height:34px;justify-content:center;width:34px}.ap-stat span{color:#667085;display:block;font-size:12px;font-weight:900;margin-top:11px}.ap-stat strong{color:#111827;display:block;font-size:24px;font-weight:900;line-height:1;margin-top:5px}.ap-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}.ap-filter{align-items:end;display:grid;gap:10px;grid-template-columns:180px 210px minmax(220px,1fr) auto;padding:14px}.ap-field{display:grid;gap:6px}.ap-field label{color:#667085;font-size:11px;font-weight:900;text-transform:uppercase}.ap-input,.ap-select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.ap-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 12px;text-decoration:none;white-space:nowrap}.ap-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.ap-btn.success{background:#16a34a;border-color:#16a34a;color:#fff}.ap-btn.danger{background:#dc2626;border-color:#dc2626;color:#fff}.ap-btn.dark{background:#111827;border-color:#111827;color:#fff}.ap-btn.muted{background:#f3f4f6;color:#4b5563}.ap-btn:hover{filter:brightness(.98);color:inherit}.ap-btn.primary:hover,.ap-btn.success:hover,.ap-btn.danger:hover,.ap-btn.dark:hover{color:#fff}.ap-table-wrap{overflow-x:auto}.ap-table{border-collapse:collapse;min-width:1180px;width:100%}.ap-table th{background:#111827;color:#fff;font-size:11px;font-weight:900;letter-spacing:.03em;padding:11px 12px;text-align:left;text-transform:uppercase;white-space:nowrap}.ap-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:12px;vertical-align:top}.ap-table tr:hover td{background:#fafafa}.ap-code{color:#111827;font-weight:900}.ap-sub{color:#667085;font-size:12px;line-height:1.45;margin-top:3px}.ap-badge{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;min-height:25px;padding:4px 9px;white-space:nowrap}.ap-badge.success{background:#dcfce7;color:#166534}.ap-badge.warning{background:#fef3c7;color:#92400e}.ap-badge.danger{background:#fee2e2;color:#991b1b}.ap-badge.info{background:#dbeafe;color:#1d4ed8}.ap-badge.muted{background:#f3f4f6;color:#4b5563}.ap-actions{display:flex;flex-wrap:wrap;gap:7px;max-width:360px}.ap-mini-form{display:inline-flex;gap:6px}.ap-cancel-form{display:flex;gap:6px;width:100%}.ap-cancel-form .ap-input{min-width:150px}.ap-empty{color:#667085;padding:32px 12px;text-align:center}.ap-pagination{padding:0 14px 14px}@media(max-width:1100px){.ap-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.ap-filter{grid-template-columns:1fr 1fr}}@media(max-width:700px){.ap-page{margin:-24px -12px 0;padding:16px 12px}.ap-head{flex-direction:column}.ap-summary,.ap-filter{grid-template-columns:1fr}.ap-btn{width:100%}}
</style>
@endpush

@section('content')
<div class="ap-page">
    <div class="ap-inner">
        <div class="ap-head">
            <div class="ap-title">
                <small>Quản lý lịch hẹn</small>
                <h4>Lịch đo mắt</h4>
                <p>Theo dõi lịch hẹn đo thị lực tại cửa hàng 123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM.</p>
            </div>
        </div>

        <div class="ap-summary">
            <div class="ap-stat"><i class="far fa-calendar"></i><span>Tất cả lịch</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div class="ap-stat"><i class="far fa-clock"></i><span>Chờ xác nhận</span><strong>{{ number_format($summary['pending']) }}</strong></div>
            <div class="ap-stat"><i class="far fa-check-circle"></i><span>Đã xác nhận</span><strong>{{ number_format($summary['confirmed']) }}</strong></div>
            <div class="ap-stat"><i class="far fa-calendar-check"></i><span>Hôm nay</span><strong>{{ number_format($summary['today']) }}</strong></div>
        </div>

        <div class="ap-card">
            <form class="ap-filter" method="get" action="{{ route('admin.appointments.index') }}">
                <div class="ap-field">
                    <label>Ngày hẹn</label>
                    <input class="ap-input" type="date" name="date" value="{{ $filters['date'] }}">
                </div>
                <div class="ap-field">
                    <label>Trạng thái</label>
                    <select class="ap-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $code => $label)
                            <option value="{{ $code }}" @selected($filters['status'] === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ap-field">
                    <label>Từ khóa</label>
                    <input class="ap-input" name="keyword" value="{{ $filters['keyword'] }}" placeholder="Mã lịch, tên khách, số điện thoại, email">
                </div>
                <button class="ap-btn primary" type="submit"><i class="fa fa-search"></i> Lọc lịch</button>
            </form>

            <div class="ap-table-wrap">
                <table class="ap-table">
                    <thead>
                        <tr>
                            <th>Mã lịch</th>
                            <th>Thời gian</th>
                            <th>Khách hàng</th>
                            <th>Dịch vụ</th>
                            <th>Trạng thái</th>
                            <th>Đổi lịch</th>
                            <th>Ghi chú</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>
                                <div class="ap-code">{{ $appointment->code }}</div>
                                <div class="ap-sub">Tạo: {{ $appointment->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="ap-code">{{ $appointment->appointment_time }}</div>
                                <div class="ap-sub">{{ $appointment->appointment_date?->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="ap-code">{{ $appointment->customer_name }}</div>
                                <div class="ap-sub">{{ $appointment->customer_phone }}</div>
                                <div class="ap-sub">{{ $appointment->customer_email }}</div>
                                @if ($appointment->user)
                                    <div class="ap-sub">Tài khoản: #{{ $appointment->user->id }}</div>
                                @else
                                    <div class="ap-sub">Khách vãng lai</div>
                                @endif
                            </td>
                            <td>
                                <div class="ap-code">{{ $appointment->service_name }}</div>
                                <div class="ap-sub">{{ (float) $appointment->price > 0 ? number_format((float) $appointment->price, 0, ',', '.') . 'd' : 'Miễn phí' }}</div>
                            </td>
                            <td>
                                <span class="ap-badge {{ $statusClass($appointment->status) }}">{{ $appointment->statusLabel() }}</span>
                                @if ($appointment->confirmed_at)
                                    <div class="ap-sub">Xác nhận: {{ $appointment->confirmed_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @if ($appointment->cancelled_at)
                                    <div class="ap-sub">Hủy: {{ $appointment->cancelled_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="ap-code">{{ $appointment->reschedule_count }} / {{ \App\Models\Appointment::MAX_RESCHEDULE_COUNT }}</div>
                                @if ($appointment->last_rescheduled_at)
                                    <div class="ap-sub">{{ $appointment->last_rescheduled_at->format('d/m/Y H:i') }}</div>
                                @endif
                                @if ($appointment->reschedule_reason)
                                    <div class="ap-sub">{{ $appointment->reschedule_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $appointment->note ?: '-' }}</div>
                                @if ($appointment->cancel_reason)
                                    <div class="ap-sub">Lý do hủy: {{ $appointment->cancel_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="ap-actions">
                                    @if ($appointment->canConfirm())
                                        <form class="ap-mini-form" method="post" action="{{ route('admin.appointments.confirm', $appointment) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="ap-btn success" type="submit"><i class="fa fa-check"></i> Xác nhận</button>
                                        </form>
                                    @endif

                                    @if ($appointment->canComplete())
                                        <form class="ap-mini-form" method="post" action="{{ route('admin.appointments.complete', $appointment) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="ap-btn primary" type="submit"><i class="fa fa-flag-checkered"></i> Hoàn tất</button>
                                        </form>
                                    @endif

                                    @if ($appointment->canMarkNoShow())
                                        <form class="ap-mini-form" method="post" action="{{ route('admin.appointments.no-show', $appointment) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="ap-btn muted" type="submit"><i class="fa fa-user-times"></i> Không đến</button>
                                        </form>
                                    @endif

                                    @if ($appointment->canCancel())
                                        <form class="ap-cancel-form" method="post" action="{{ route('admin.appointments.cancel', $appointment) }}" onsubmit="return confirm('Hủy lịch hẹn này?')">
                                            @csrf
                                            @method('patch')
                                            <input class="ap-input" name="cancel_reason" maxlength="500" placeholder="Lý do hủy" required>
                                            <button class="ap-btn danger" type="submit"><i class="fa fa-times"></i> Hủy</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="ap-empty" colspan="8">Chưa có lịch đo mắt.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="ap-pagination">
                {{ $appointments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
