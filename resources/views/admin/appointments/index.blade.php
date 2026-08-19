@extends('admin.layouts.app')

@section('title', 'Lịch đo mắt')

@php
    use App\Models\Appointment;

    $statusMeta = [
        Appointment::STATUS_PENDING => ['label' => 'Chờ xác nhận', 'class' => 'pending', 'icon' => 'fa-hourglass-half'],
        Appointment::STATUS_CONFIRMED => ['label' => 'Đã xác nhận', 'class' => 'confirmed', 'icon' => 'fa-check-circle'],
        Appointment::STATUS_COMPLETED => ['label' => 'Hoàn tất', 'class' => 'completed', 'icon' => 'fa-flag-checkered'],
        Appointment::STATUS_CANCELLED => ['label' => 'Đã hủy', 'class' => 'cancelled', 'icon' => 'fa-ban'],
        Appointment::STATUS_NO_SHOW => ['label' => 'Không đến', 'class' => 'noshow', 'icon' => 'fa-user-times'],
    ];

    $weekDays = collect(range(0, 6))->map(fn ($offset) => $weekStart->copy()->addDays($offset));
    $timeRows = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
    $calendarItems = $calendarAppointments ?? $appointments->getCollection();
    $itemsByDayTime = $calendarItems->groupBy(fn ($item) => $item->appointment_date?->format('Y-m-d') . '|' . $item->appointment_time);
    $selectedDayItems = $calendarItems
        ->filter(fn ($item) => $item->appointment_date?->isSameDay($selectedDate))
        ->sortBy(function ($item) use ($selectedDate) {
            $appointmentAt = strtotime($selectedDate->toDateString() . ' ' . $item->appointment_time);
            $distance = $appointmentAt === false ? PHP_INT_MAX : $appointmentAt - now()->timestamp;
            $group = $distance >= 0 ? 0 : 1;

            return sprintf('%d-%012d-%s-%08d', $group, abs($distance), $item->appointment_time, $item->id);
        })
        ->values();
    $visibleDayItems = $selectedDayItems->take(2);
    $hiddenDayItems = $selectedDayItems->slice(2)->values();
    $confirmedDayCount = $selectedDayItems->where('status', Appointment::STATUS_CONFIRMED)->count();
    $pendingDayCount = $selectedDayItems->where('status', Appointment::STATUS_PENDING)->count();
    $pendingItems = $calendarItems
        ->where('status', Appointment::STATUS_PENDING)
        ->sortBy(fn ($item) => [$item->appointment_date?->format('Y-m-d'), $item->appointment_time])
        ->values();

    $statusFor = fn ($appointment) => $statusMeta[$appointment->status] ?? ['label' => $appointment->status ?: '-', 'class' => 'muted', 'icon' => 'fa-circle'];
    $money = fn ($value) => (float) $value > 0 ? number_format((float) $value, 0, ',', '.') . 'đ' : 'Miễn phí';
@endphp

@push('styles')
<style>
.ap-page{background:#f4f7fb;color:#111827;margin:-24px -24px 0;min-height:100vh;padding:20px 24px 64px}.ap-shell{margin:0 auto;max-width:1580px}.ap-topbar{align-items:flex-start;display:flex;gap:18px;justify-content:space-between;margin-bottom:14px}.ap-title small{color:#2563eb;display:block;font-size:12px;font-weight:900;text-transform:uppercase}.ap-title h4{font-size:28px;font-weight:900;letter-spacing:0;margin:4px 0}.ap-title p{color:#64748b;font-size:14px;font-weight:700;margin:0}.ap-week-nav{align-items:center;display:flex;gap:8px}.ap-nav-btn{align-items:center;background:#fff;border:1px solid #dbe3ef;border-radius:8px;color:#334155;display:inline-flex;font-size:13px;font-weight:900;height:38px;justify-content:center;padding:0 12px;text-decoration:none}.ap-nav-btn:hover{background:#eff6ff;color:#2563eb}.ap-stats{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:12px}.ap-stat{align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:8px;display:flex;gap:11px;min-height:70px;padding:12px}.ap-stat i{align-items:center;background:#eff6ff;border-radius:8px;color:#2563eb;display:flex;height:36px;justify-content:center;width:36px}.ap-stat span{color:#64748b;display:block;font-size:11px;font-weight:900;text-transform:uppercase}.ap-stat strong{color:#0f172a;display:block;font-size:22px;font-weight:900;line-height:1;margin-top:3px}.ap-filter{align-items:end;background:#fff;border:1px solid #e2e8f0;border-radius:8px;display:grid;gap:10px;grid-template-columns:180px 210px minmax(230px,1fr) auto auto;margin-bottom:14px;padding:13px}.ap-field{display:grid;gap:6px}.ap-field label{color:#64748b;font-size:11px;font-weight:900;text-transform:uppercase}.ap-input,.ap-select{background:#fff;border:1px solid #cbd5e1;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:8px 10px;width:100%}.ap-btn{align-items:center;background:#fff;border:1px solid #cbd5e1;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 12px;text-decoration:none;white-space:nowrap}.ap-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.ap-btn.success{background:#16a34a;border-color:#16a34a;color:#fff}.ap-btn.danger{background:#dc2626;border-color:#dc2626;color:#fff}.ap-btn.muted{background:#f8fafc;color:#475569}.ap-main{align-items:start;display:grid;gap:14px;grid-template-columns:minmax(0,1fr) 390px}.ap-calendar-panel,.ap-agenda{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden}.ap-panel-head{align-items:center;border-bottom:1px solid #e2e8f0;display:flex;gap:12px;justify-content:space-between;min-height:56px;padding:12px 14px}.ap-panel-head h5{color:#0f172a;font-size:16px;font-weight:900;margin:0}.ap-panel-head span{color:#64748b;font-size:12px;font-weight:800}.ap-week-grid{display:grid;grid-template-columns:74px repeat(7,minmax(126px,1fr));overflow:auto}.ap-corner,.ap-day-head,.ap-hour,.ap-cell{border-bottom:1px solid #edf2f7;border-right:1px solid #edf2f7}.ap-corner{background:#f8fafc;min-height:58px}.ap-day-head{align-content:center;background:#f8fafc;display:grid;gap:3px;justify-items:center;min-height:58px;padding:8px}.ap-day-head strong{color:#0f172a;font-size:13px;font-weight:900}.ap-day-head span{align-items:center;border-radius:999px;color:#64748b;display:inline-flex;font-size:12px;font-weight:900;height:24px;justify-content:center;min-width:24px;padding:0 8px}.ap-day-head.today span,.ap-day-head.selected span{background:#2563eb;color:#fff}.ap-hour{align-items:start;background:#fff;color:#64748b;display:flex;font-size:12px;font-weight:900;justify-content:center;min-height:94px;padding-top:10px}.ap-cell{background:#fff;display:grid;gap:6px;min-height:94px;padding:7px}.ap-cell.today{background:#fbfdff}.ap-event{border-left:4px solid #94a3b8;border-radius:7px;box-shadow:0 1px 0 rgba(15,23,42,.04);display:grid;gap:5px;padding:8px;text-decoration:none}.ap-event.pending{background:#fff7df;border-left-color:#f59e0b}.ap-event.confirmed{background:#eef5ff;border-left-color:#2563eb}.ap-event.completed{background:#ecfdf3;border-left-color:#16a34a}.ap-event.cancelled{background:#fff1f2;border-left-color:#dc2626}.ap-event.noshow,.ap-event.muted{background:#f8fafc;border-left-color:#64748b}.ap-event-title{color:#0f172a;font-size:12px;font-weight:900;line-height:1.35}.ap-event-meta{color:#64748b;font-size:11px;font-weight:800;line-height:1.35}.ap-agenda-phone{background:#fff;display:grid}.ap-phone-head{align-items:center;display:flex;justify-content:space-between;padding:14px}.ap-phone-head strong{font-size:18px;font-weight:900}.ap-phone-head span{color:#64748b;font-size:12px;font-weight:800}.ap-date-strip{border-bottom:1px solid #e2e8f0;display:grid;gap:6px;grid-template-columns:repeat(7,1fr);padding:0 12px 12px}.ap-mini-day{align-items:center;border-radius:8px;color:#64748b;display:grid;font-size:11px;font-weight:900;justify-items:center;min-height:46px;text-decoration:none}.ap-mini-day strong{color:#0f172a;font-size:13px}.ap-mini-day.active{background:#fee2e2;color:#991b1b}.ap-mini-day.active strong{color:#dc2626}.ap-section-label{color:#94a3b8;font-size:11px;font-weight:900;letter-spacing:.04em;padding:14px 14px 7px;text-transform:uppercase}.ap-task-list{display:grid;gap:8px;padding:0 14px 14px}.ap-task{border:1px solid #e2e8f0;border-radius:8px;display:grid;gap:8px;padding:10px}.ap-task-top{align-items:flex-start;display:flex;gap:10px;justify-content:space-between}.ap-check{border:1px solid #cbd5e1;border-radius:4px;flex:0 0 auto;height:16px;margin-top:2px;width:16px}.ap-task-body{min-width:0}.ap-task strong{display:block;font-size:13px;font-weight:900;line-height:1.35}.ap-task small{color:#64748b;display:block;font-size:12px;font-weight:700;line-height:1.45;margin-top:2px}.ap-badge{align-items:center;border-radius:999px;display:inline-flex;font-size:11px;font-weight:900;gap:5px;line-height:1;padding:6px 8px;white-space:nowrap}.ap-badge.pending{background:#fef3c7;color:#92400e}.ap-badge.confirmed{background:#dbeafe;color:#1d4ed8}.ap-badge.completed{background:#dcfce7;color:#166534}.ap-badge.cancelled{background:#fee2e2;color:#991b1b}.ap-badge.noshow,.ap-badge.muted{background:#f1f5f9;color:#475569}.ap-actions{display:flex;flex-wrap:wrap;gap:7px}.ap-cancel-form{display:grid;gap:7px;grid-template-columns:minmax(120px,1fr) auto}.ap-empty{color:#64748b;font-size:13px;font-weight:800;padding:18px;text-align:center}.ap-pending{border-top:1px solid #e2e8f0}.ap-pagination{padding:0 14px 14px}@media(max-width:1250px){.ap-main{grid-template-columns:1fr}.ap-agenda{order:-1}.ap-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.ap-filter{grid-template-columns:1fr 1fr}}@media(max-width:760px){.ap-page{margin:-24px -12px 0;padding:16px 12px}.ap-topbar{display:grid}.ap-week-nav{justify-content:start}.ap-stats,.ap-filter{grid-template-columns:1fr}.ap-week-grid{grid-template-columns:58px repeat(7,118px)}.ap-actions,.ap-cancel-form{display:grid}.ap-btn{width:100%}}
.ap-calendar-panel{min-width:0}.ap-week-grid{cursor:grab;max-height:calc(100vh - 300px);overscroll-behavior:contain;scroll-behavior:smooth;scrollbar-gutter:stable;touch-action:none}.ap-week-grid.is-dragging{cursor:grabbing;scroll-behavior:auto}.ap-week-grid.is-dragging *{cursor:grabbing;user-select:none}.ap-corner{left:0;position:sticky;top:0;z-index:6}.ap-day-head{position:sticky;top:0;z-index:5}.ap-hour{left:0;position:sticky;z-index:4}.ap-cell{min-width:126px}.ap-event,.ap-event *{cursor:grab;touch-action:none;user-select:none}.ap-day-head,.ap-hour,.ap-corner{box-shadow:1px 0 0 #edf2f7}
.ap-page{background:#f3f6fb;color:#172033;padding:22px 26px 72px}.ap-shell{max-width:1600px}.ap-topbar{align-items:center;margin-bottom:18px}.ap-title small{color:#0f766e;letter-spacing:.08em}.ap-title h4{color:#111827;font-size:30px;margin-top:6px}.ap-title p{color:#667085;font-weight:700}.ap-week-nav{background:#fff;border:1px solid #dde5f0;border-radius:8px;box-shadow:0 12px 28px rgba(15,23,42,.06);padding:6px}.ap-nav-btn{border:0;border-radius:7px;height:36px}.ap-nav-btn:hover{background:#eaf3ff}.ap-stats{gap:12px}.ap-stat{background:#fff;border:1px solid #e3e9f2;border-left:4px solid #2563eb;box-shadow:0 10px 28px rgba(15,23,42,.055);min-height:78px;padding:14px}.ap-stat:nth-child(2){border-left-color:#f59e0b}.ap-stat:nth-child(3){border-left-color:#0d9488}.ap-stat:nth-child(4){border-left-color:#e11d48}.ap-stat i{background:#f4f8ff;border-radius:8px}.ap-stat:nth-child(2) i{background:#fff7e6;color:#b45309}.ap-stat:nth-child(3) i{background:#eafaf6;color:#0f766e}.ap-stat:nth-child(4) i{background:#fff1f2;color:#be123c}.ap-filter{border:1px solid #dfe7f1;border-radius:8px;box-shadow:0 12px 30px rgba(15,23,42,.055);margin-bottom:16px;padding:14px}.ap-input,.ap-select{border-color:#d7e0eb;border-radius:8px;box-shadow:inset 0 1px 0 rgba(15,23,42,.02)}.ap-input:focus,.ap-select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);outline:0}.ap-btn{border-radius:8px;transition:transform .14s ease,box-shadow .14s ease,background .14s ease}.ap-btn:hover{box-shadow:0 8px 18px rgba(15,23,42,.08);transform:translateY(-1px)}.ap-btn.primary{background:#2563eb}.ap-btn.success{background:#059669;border-color:#059669}.ap-btn.danger{background:#e11d48;border-color:#e11d48}.ap-main{gap:16px;grid-template-columns:minmax(0,1fr) 405px}.ap-calendar-panel,.ap-agenda{border:1px solid #dfe7f1;border-radius:8px;box-shadow:0 18px 45px rgba(15,23,42,.075)}.ap-panel-head{background:#fff;border-bottom-color:#e4ebf5;min-height:68px;padding:16px 18px}.ap-panel-head h5{font-size:19px}.ap-panel-head span{color:#667085}.ap-week-grid{background:#edf3fa;border-top:1px solid #edf2f7;grid-template-columns:76px repeat(7,minmax(148px,1fr));max-height:calc(100vh - 284px)}.ap-corner,.ap-day-head,.ap-hour,.ap-cell{border-color:#e5edf6}.ap-corner,.ap-day-head{background:#f8fbff}.ap-day-head{min-height:72px;text-decoration:none}.ap-day-head strong{font-size:14px}.ap-day-head span{background:#eef3f9;color:#667085;height:30px;min-width:30px}.ap-day-head.today span{background:#2563eb;color:#fff;box-shadow:0 8px 18px rgba(37,99,235,.28)}.ap-day-head.selected{background:#eff6ff}.ap-hour{background:#fbfdff;color:#73839a;font-size:13px;min-height:108px;padding-top:14px}.ap-cell{background:#fff;gap:8px;min-height:108px;min-width:148px;padding:9px;transition:background .14s ease}.ap-cell:hover{background:#f8fbff}.ap-cell.today{background:#f7fbff}.ap-event{border:1px solid transparent;border-left-width:5px;border-radius:8px;box-shadow:0 10px 22px rgba(15,23,42,.08);gap:6px;padding:10px 10px 10px 11px;transition:box-shadow .14s ease,transform .14s ease}.ap-event:hover{box-shadow:0 14px 28px rgba(15,23,42,.13);transform:translateY(-1px)}.ap-event.pending{background:#fff7df;border-color:#fde68a;border-left-color:#f59e0b}.ap-event.confirmed{background:#eff6ff;border-color:#bfdbfe;border-left-color:#2563eb}.ap-event.completed{background:#ecfdf5;border-color:#bbf7d0;border-left-color:#059669}.ap-event.cancelled{background:#fff1f2;border-color:#fecdd3;border-left-color:#e11d48}.ap-event.noshow,.ap-event.muted{background:#f8fafc;border-color:#e2e8f0;border-left-color:#64748b}.ap-event-title{font-size:13px}.ap-event-meta{color:#667085;font-size:11px}.ap-agenda{background:#fff}.ap-phone-head{background:#111827;color:#fff;min-height:72px;padding:16px 18px}.ap-phone-head span{color:#cbd5e1}.ap-phone-head i{background:#253044;border-radius:8px;color:#e5e7eb;padding:10px}.ap-date-strip{background:#fff;border-bottom-color:#e5edf6;gap:7px;padding:13px}.ap-mini-day{background:#f8fafc;border:1px solid transparent;color:#667085}.ap-mini-day:hover{background:#eff6ff;text-decoration:none}.ap-mini-day.active{background:#fff1f2;border-color:#fecdd3;color:#be123c}.ap-section-label{color:#6b7a90;padding:16px 16px 8px}.ap-task-list{gap:10px;padding:0 16px 16px}.ap-task{background:#fff;border-color:#e1e8f2;border-left:4px solid #2563eb;box-shadow:0 10px 24px rgba(15,23,42,.055);padding:12px}.ap-task:hover{box-shadow:0 14px 30px rgba(15,23,42,.09)}.ap-check{background:#fff;border-color:#94a3b8;border-radius:5px}.ap-badge{border-radius:7px}.ap-pending{background:#fbfdff;border-top-color:#e5edf6}.ap-empty{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;margin:0 16px 16px;padding:16px}@media(max-width:1250px){.ap-main{grid-template-columns:1fr}.ap-week-grid{max-height:66vh}}@media(max-width:760px){.ap-page{padding:16px 12px 56px}.ap-main{gap:12px}.ap-week-grid{grid-template-columns:64px repeat(7,142px);max-height:62vh}.ap-cell{min-width:142px}.ap-panel-head{align-items:flex-start;display:grid}.ap-week-nav{width:max-content}}
.ap-task-extra.is-hidden{display:none}.ap-more-wrap{padding:0 16px 16px}.ap-more-btn{align-items:center;background:#f8fafc;border:1px solid #d7e0eb;border-radius:8px;color:#2563eb;display:flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:40px;width:100%}.ap-more-btn:hover{background:#eff6ff}
.ap-task.is-hidden{display:none}.ap-agenda-filter{display:grid;gap:8px;grid-template-columns:repeat(3,minmax(0,1fr));padding:12px 16px 4px}.ap-filter-chip{align-items:center;background:#f8fafc;border:1px solid #d7e0eb;border-radius:8px;color:#475569;display:flex;font-size:12px;font-weight:900;gap:6px;justify-content:center;min-height:38px;padding:0 8px}.ap-filter-chip span{align-items:center;background:#e2e8f0;border-radius:999px;color:#334155;display:inline-flex;font-size:11px;height:20px;justify-content:center;min-width:20px;padding:0 6px}.ap-filter-chip.active{background:#111827;border-color:#111827;color:#fff}.ap-filter-chip.active span{background:#2563eb;color:#fff}.ap-filter-chip[data-agenda-status="CONFIRMED"].active{background:#2563eb;border-color:#2563eb}.ap-filter-chip[data-agenda-status="PENDING"].active{background:#f59e0b;border-color:#f59e0b;color:#111827}.ap-filter-chip[data-agenda-status="PENDING"].active span{background:#fff7df;color:#92400e}@media(max-width:420px){.ap-agenda-filter{grid-template-columns:1fr}.ap-filter-chip{justify-content:space-between}}
.ap-filter-empty.is-hidden{display:none}
.ap-cancel-form{margin-top:8px}.ap-cancel-form[hidden]{display:none}.ap-cancel-form:not([hidden]){display:grid;gap:8px;grid-template-columns:1fr}.ap-cancel-actions{display:flex;flex-wrap:wrap;gap:7px}.ap-cancel-actions .ap-btn{flex:1 1 120px}
</style>
@endpush

@section('content')
<div class="ap-page">
    <div class="ap-shell">
        <div class="ap-topbar">
            <div class="ap-title">
                <small>Quản lý lịch hẹn</small>
                <h4>Lịch đo mắt</h4>
                <p>Dạng tuần như calendar, kèm danh sách việc hôm nay để xử lý nhanh.</p>
            </div>
            <div class="ap-week-nav">
                <a class="ap-nav-btn" href="{{ route('admin.appointments.index', array_filter(['date' => $weekStart->copy()->subWeek()->toDateString(), 'status' => $filters['status'], 'keyword' => $filters['keyword']])) }}"><i class="fa fa-chevron-left"></i></a>
                <a class="ap-nav-btn" href="{{ route('admin.appointments.index', array_filter(['date' => today()->toDateString(), 'status' => $filters['status'], 'keyword' => $filters['keyword']])) }}">Hôm nay</a>
                <a class="ap-nav-btn" href="{{ route('admin.appointments.index', array_filter(['date' => $weekStart->copy()->addWeek()->toDateString(), 'status' => $filters['status'], 'keyword' => $filters['keyword']])) }}"><i class="fa fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="ap-stats">
            <div class="ap-stat"><i class="far fa-calendar"></i><div><span>Tất cả lịch</span><strong>{{ number_format($summary['total']) }}</strong></div></div>
            <div class="ap-stat"><i class="far fa-clock"></i><div><span>Chờ xác nhận</span><strong>{{ number_format($summary['pending']) }}</strong></div></div>
            <div class="ap-stat"><i class="far fa-check-circle"></i><div><span>Đã xác nhận</span><strong>{{ number_format($summary['confirmed']) }}</strong></div></div>
            <div class="ap-stat"><i class="far fa-calendar-check"></i><div><span>Hôm nay</span><strong>{{ number_format($summary['today']) }}</strong></div></div>
        </div>

        <form class="ap-filter" method="get" action="{{ route('admin.appointments.index') }}">
            <div class="ap-field">
                <label>Ngày trong tuần</label>
                <input class="ap-input" type="date" name="date" value="{{ $selectedDate->toDateString() }}">
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
            <a class="ap-btn" href="{{ route('admin.appointments.index') }}"><i class="fa fa-rotate-left"></i> Xóa lọc</a>
        </form>

        <div class="ap-main">
            <section class="ap-calendar-panel">
                <div class="ap-panel-head">
                    <div>
                        <h5>Tuần {{ $weekStart->format('d/m') }} - {{ $weekEnd->format('d/m/Y') }}</h5>
                        <span>{{ $calendarItems->count() }} lịch trong tuần đang xem</span>
                    </div>
                    <span>{{ $appointments->total() }} lịch phù hợp bộ lọc</span>
                </div>

                <div class="ap-week-grid">
                    <div class="ap-corner"></div>
                    @foreach ($weekDays as $day)
                        <a class="ap-day-head {{ $day->isToday() ? 'today' : '' }} {{ $day->isSameDay($selectedDate) ? 'selected' : '' }}" href="{{ route('admin.appointments.index', array_filter(['date' => $day->toDateString(), 'status' => $filters['status'], 'keyword' => $filters['keyword']])) }}">
                            <strong>{{ ucfirst($day->locale('vi')->isoFormat('ddd')) }}</strong>
                            <span>{{ $day->format('d') }}</span>
                        </a>
                    @endforeach

                    @foreach ($timeRows as $time)
                        <div class="ap-hour">{{ $time }}</div>
                        @foreach ($weekDays as $day)
                            @php
                                $cellKey = $day->format('Y-m-d') . '|' . $time;
                                $cellItems = $itemsByDayTime->get($cellKey, collect());
                            @endphp
                            <div class="ap-cell {{ $day->isToday() ? 'today' : '' }}">
                                @foreach ($cellItems as $appointment)
                                    @php($meta = $statusFor($appointment))
                                    <div class="ap-event {{ $meta['class'] }}">
                                        <div class="ap-event-title">{{ $appointment->customer_name }}</div>
                                        <div class="ap-event-meta">{{ $appointment->service_name }}</div>
                                        <div class="ap-event-meta">{{ $appointment->customer_phone }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="ap-pagination">
                    {{ $appointments->links() }}
                </div>
            </section>

            <aside class="ap-agenda">
                <div class="ap-agenda-phone">
                    <div class="ap-phone-head">
                        <div>
                            <strong>{{ $selectedDate->isToday() ? 'Hôm nay' : $selectedDate->format('d/m/Y') }}</strong>
                            <span>{{ ucfirst($selectedDate->locale('vi')->isoFormat('dddd')) }}</span>
                        </div>
                        <i class="fa fa-search"></i>
                    </div>

                    <div class="ap-date-strip">
                        @foreach ($weekDays as $day)
                            <a class="ap-mini-day {{ $day->isSameDay($selectedDate) ? 'active' : '' }}" href="{{ route('admin.appointments.index', array_filter(['date' => $day->toDateString(), 'status' => $filters['status'], 'keyword' => $filters['keyword']])) }}">
                                {{ $day->locale('vi')->isoFormat('dd') }}
                                <strong>{{ $day->format('d') }}</strong>
                            </a>
                        @endforeach
                    </div>

                    <div class="ap-agenda-filter" data-agenda-filter>
                        <button class="ap-filter-chip active" type="button" data-agenda-status="">
                            Tất cả <span>{{ $selectedDayItems->count() }}</span>
                        </button>
                        <button class="ap-filter-chip" type="button" data-agenda-status="CONFIRMED">
                            Đã xác nhận <span>{{ $confirmedDayCount }}</span>
                        </button>
                        <button class="ap-filter-chip" type="button" data-agenda-status="PENDING">
                            Chờ xác nhận <span>{{ $pendingDayCount }}</span>
                        </button>
                    </div>

                    <div class="ap-section-label">Lịch trong ngày</div>
                    <div class="ap-task-list">
                        @forelse ($selectedDayItems as $index => $appointment)
                            @php($meta = $statusFor($appointment))
                            <div class="ap-task ap-day-task {{ $index >= 2 ? 'ap-task-extra is-hidden' : '' }}" data-agenda-task data-status="{{ $appointment->status }}">
                                <div class="ap-task-top">
                                    <div class="ap-check"></div>
                                    <div class="ap-task-body">
                                        <strong>{{ $appointment->appointment_time }} · {{ $appointment->customer_name }}</strong>
                                        <small>{{ $appointment->service_name }} · {{ $money($appointment->price) }}</small>
                                        <small>{{ $appointment->customer_phone }} · {{ $appointment->customer_email }}</small>
                                    </div>
                                    <span class="ap-badge {{ $meta['class'] }}"><i class="fa {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                                </div>

                                @if ($appointment->note || $appointment->reschedule_reason || $appointment->cancel_reason)
                                    <small>
                                        {{ $appointment->note ?: '' }}
                                        {{ $appointment->reschedule_reason ? 'Đổi lịch: ' . $appointment->reschedule_reason : '' }}
                                        {{ $appointment->cancel_reason ? 'Hủy: ' . $appointment->cancel_reason : '' }}
                                    </small>
                                @endif

                                <div class="ap-actions">
                                    @if ($appointment->canConfirm())
                                        <form method="post" action="{{ route('admin.appointments.confirm', $appointment) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="ap-btn success" type="submit"><i class="fa fa-check"></i> Xác nhận</button>
                                        </form>
                                    @endif

                                    @if ($appointment->canComplete())
                                        <form method="post" action="{{ route('admin.appointments.complete', $appointment) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="ap-btn primary" type="submit"><i class="fa fa-flag-checkered"></i> Hoàn tất</button>
                                        </form>
                                    @endif

                                    @if ($appointment->canMarkNoShow())
                                        <form method="post" action="{{ route('admin.appointments.no-show', $appointment) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="ap-btn muted" type="submit"><i class="fa fa-user-times"></i> Không đến</button>
                                        </form>
                                    @endif

                                    @if ($appointment->canCancel())
                                        <button class="ap-btn danger" type="button" data-cancel-toggle><i class="fa fa-times"></i> Hủy</button>
                                    @endif
                                </div>

                                @if ($appointment->canCancel())
                                    <form class="ap-cancel-form" method="post" action="{{ route('admin.appointments.cancel', $appointment) }}" data-cancel-panel hidden>
                                        @csrf
                                        @method('patch')
                                        <input class="ap-input" name="cancel_reason" maxlength="500" placeholder="Lý do hủy" required data-cancel-input>
                                        <div class="ap-cancel-actions">
                                            <button class="ap-btn danger" type="submit"><i class="fa fa-check"></i> Xác nhận</button>
                                            <button class="ap-btn muted" type="button" data-cancel-dismiss>Hủy</button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="ap-empty">Ngày này chưa có lịch.</div>
                        @endforelse
                    </div>

                    <div class="ap-empty ap-filter-empty is-hidden" data-agenda-empty>
                        Không có lịch thuộc trạng thái này.
                    </div>

                    @if ($hiddenDayItems->isNotEmpty())
                        <div class="ap-more-wrap">
                            <button class="ap-more-btn" type="button" data-agenda-more>
                                <i class="fa fa-chevron-down"></i>
                                Xem thêm {{ $hiddenDayItems->count() }} lịch
                            </button>
                        </div>
                    @endif

                    <div class="ap-pending">
                        <div class="ap-section-label">Cần xác nhận trong tuần</div>
                        <div class="ap-task-list">
                            @forelse ($pendingItems->take(4) as $appointment)
                                <div class="ap-task">
                                    <strong>{{ $appointment->appointment_date?->format('d/m') }} {{ $appointment->appointment_time }} · {{ $appointment->customer_name }}</strong>
                                    <small>{{ $appointment->service_name }} · {{ $appointment->customer_phone }}</small>
                                    <form method="post" action="{{ route('admin.appointments.confirm', $appointment) }}">
                                        @csrf
                                        @method('patch')
                                        <button class="ap-btn success" type="submit"><i class="fa fa-check"></i> Xác nhận</button>
                                    </form>
                                </div>
                            @empty
                                <div class="ap-empty">Không còn lịch chờ xác nhận trong tuần.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-cancel-toggle]').forEach(function (button) {
        var task = button.closest('.ap-task');
        var form = task ? task.querySelector('[data-cancel-panel]') : null;

        if (!form) {
            return;
        }

        var input = form.querySelector('[data-cancel-input]');
        var dismiss = form.querySelector('[data-cancel-dismiss]');

        button.addEventListener('click', function () {
            form.hidden = false;
            button.hidden = true;

            if (input) {
                input.focus();
            }
        });

        if (dismiss) {
            dismiss.addEventListener('click', function () {
                form.reset();
                form.hidden = true;
                button.hidden = false;
                button.focus();
            });
        }
    });

    document.querySelectorAll('.ap-week-grid').forEach(function (grid) {
        var dragging = false;
        var moved = false;
        var startX = 0;
        var startY = 0;
        var scrollLeft = 0;
        var scrollTop = 0;

        function stopDrag(event) {
            if (!dragging) {
                return;
            }

            dragging = false;
            grid.classList.remove('is-dragging');

            if (event && grid.hasPointerCapture && grid.hasPointerCapture(event.pointerId)) {
                grid.releasePointerCapture(event.pointerId);
            }
        }

        grid.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 || event.target.closest('button, input, select, textarea')) {
                return;
            }

            event.preventDefault();
            dragging = true;
            moved = false;
            startX = event.clientX;
            startY = event.clientY;
            scrollLeft = grid.scrollLeft;
            scrollTop = grid.scrollTop;
            grid.classList.add('is-dragging');
            grid.setPointerCapture(event.pointerId);
        });

        grid.addEventListener('pointermove', function (event) {
            if (!dragging) {
                return;
            }

            var deltaX = event.clientX - startX;
            var deltaY = event.clientY - startY;

            if (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3) {
                moved = true;
            }

            grid.scrollLeft = scrollLeft - deltaX;
            grid.scrollTop = scrollTop - deltaY;
            event.preventDefault();
        }, { passive: false });

        grid.addEventListener('pointerup', stopDrag);
        grid.addEventListener('pointercancel', stopDrag);
        grid.addEventListener('lostpointercapture', stopDrag);
        grid.addEventListener('dragstart', function (event) {
            event.preventDefault();
        });

        grid.addEventListener('click', function (event) {
            if (!moved) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            moved = false;
        }, true);

        grid.addEventListener('wheel', function (event) {
            if (!event.shiftKey || Math.abs(event.deltaX) >= Math.abs(event.deltaY)) {
                return;
            }

            grid.scrollLeft += event.deltaY;
            event.preventDefault();
        }, { passive: false });
    });

    var agendaExpanded = false;
    var agendaStatus = '';
    var agendaTasks = Array.prototype.slice.call(document.querySelectorAll('[data-agenda-task]'));
    var moreButton = document.querySelector('[data-agenda-more]');
    var emptyState = document.querySelector('[data-agenda-empty]');

    function applyAgendaFilter() {
        var matched = 0;
        var hidden = 0;

        agendaTasks.forEach(function (task) {
            var matches = !agendaStatus || task.dataset.status === agendaStatus;

            if (!matches) {
                task.classList.add('is-hidden');
                return;
            }

            if (!agendaExpanded && matched >= 2) {
                task.classList.add('is-hidden');
                hidden += 1;
            } else {
                task.classList.remove('is-hidden');
            }

            matched += 1;
        });

        if (emptyState) {
            emptyState.classList.toggle('is-hidden', matched > 0);
        }

        if (!moreButton) {
            return;
        }

        if (hidden > 0) {
            moreButton.closest('.ap-more-wrap').style.display = '';
            moreButton.disabled = false;
            moreButton.innerHTML = '<i class="fa fa-chevron-down"></i> Xem thêm ' + hidden + ' lịch';
        } else {
            moreButton.closest('.ap-more-wrap').style.display = 'none';
        }
    }

    document.querySelectorAll('[data-agenda-status]').forEach(function (button) {
        button.addEventListener('click', function () {
            agendaExpanded = false;
            agendaStatus = button.dataset.agendaStatus || '';

            document.querySelectorAll('[data-agenda-status]').forEach(function (item) {
                item.classList.toggle('active', item === button);
            });

            applyAgendaFilter();
        });
    });

    if (moreButton) {
        moreButton.addEventListener('click', function () {
            agendaExpanded = true;
            applyAgendaFilter();
        });
    }

    applyAgendaFilter();
});
</script>
@endpush
