@extends('layouts.app')

@section('title', 'Tra cứu lịch hẹn - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/advisor-shared.css') }}?v={{ file_exists(public_path('css/views/advisor-shared.css')) ? filemtime(public_path('css/views/advisor-shared.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/views/appointment.css') }}?v={{ file_exists(public_path('css/views/appointment.css')) ? filemtime(public_path('css/views/appointment.css')) : time() }}">
    <style>
        .appt-lookup-shell{max-width:760px;margin:0 auto;display:grid;gap:22px}.appt-lookup-card{background:var(--paper-card);border:1px solid var(--line);border-radius:var(--radius-lg);padding:26px}.appt-lookup-form{display:grid;gap:16px}.appt-lookup-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.appt-detail-list{border-top:1px solid var(--line);display:grid;gap:10px;margin-top:18px;padding-top:18px}.appt-detail-row{display:flex;justify-content:space-between;gap:16px;font-size:13.5px}.appt-detail-row span{color:var(--ink-soft)}.appt-detail-row strong{color:var(--ink);text-align:right}.appt-status-pill{border-radius:999px;display:inline-flex;font-size:12px;font-weight:800;min-height:25px;padding:4px 10px}.appt-status-pill.pending{background:#fef3c7;color:#92400e}.appt-status-pill.confirmed{background:#dbeafe;color:#1d4ed8}.appt-status-pill.completed{background:#dcfce7;color:#166534}.appt-status-pill.cancelled{background:#fee2e2;color:#991b1b}.appt-status-pill.muted{background:#f3f4f6;color:#4b5563}.appt-reschedule-form{border-top:1px solid var(--line);display:grid;gap:16px;margin-top:22px;padding-top:20px}.appt-reschedule-form h2{color:var(--ink);font-size:18px;font-weight:800;margin:0}.appt-alert.success{background:#ecfdf5;border-color:#a7f3d0;color:#047857}@media(max-width:767.98px){.appt-lookup-grid{grid-template-columns:1fr}.appt-detail-row{display:grid}.appt-detail-row strong{text-align:left}}
    </style>
@endpush

@php
    $statusClass = match ($appointment?->status) {
        'PENDING' => 'pending',
        'CONFIRMED' => 'confirmed',
        'COMPLETED' => 'completed',
        'CANCELLED' => 'cancelled',
        default => 'muted',
    };
@endphp

@section('content')
    <nav class="advisor-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}"><i class="fa fa-home" aria-hidden="true"></i> Trang chủ</a>
        <span aria-hidden="true">/</span>
        <strong>Tra cứu lịch hẹn</strong>
    </nav>

    <section class="advisor-page appt-page">
        <div class="advisor-container">
            <header class="advisor-header">
                <h1>Tra cứu lịch hẹn</h1>
                <p>Nhập mã lịch hẹn và email hoặc số điện thoại đã dùng khi đặt lịch.</p>
            </header>

            <div class="appt-lookup-shell">
                @if (session('success'))
                    <div class="appt-alert success" role="status">
                        <i class="fa fa-check-circle" aria-hidden="true"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="appt-alert" role="alert">
                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="appt-lookup-card">
                    <form class="appt-lookup-form" method="get" action="{{ route('appointments.lookup') }}">
                        <div class="appt-lookup-grid">
                            <label class="appt-field">
                                <span>Mã lịch hẹn</span>
                                <input name="code" value="{{ old('code', request('code')) }}" maxlength="20" required>
                            </label>
                            <label class="appt-field">
                                <span>Email hoặc số điện thoại</span>
                                <input name="contact" value="{{ old('contact', request('contact')) }}" maxlength="190" required>
                            </label>
                        </div>
                        <div class="appt-panel-actions">
                            <a class="advisor-btn advisor-btn--ghost" href="{{ route('appointments.create') }}">Đặt lịch mới</a>
                            <button class="advisor-btn advisor-btn--solid" type="submit">Tra cứu</button>
                        </div>
                    </form>
                </div>

                @if ($lookupAttempted && ! $appointment)
                    <div class="appt-alert" role="alert">
                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                        <span>Không tìm thấy lịch hẹn phù hợp với thông tin đã nhập.</span>
                    </div>
                @endif

                @if ($appointment)
                    <div class="appt-lookup-card">
                        <h2 class="m-0">{{ $appointment->code }}</h2>
                        <div class="appt-detail-list">
                            <div class="appt-detail-row"><span>Trạng thái</span><strong><span class="appt-status-pill {{ $statusClass }}">{{ $appointment->statusLabel() }}</span></strong></div>
                            <div class="appt-detail-row"><span>Dịch vụ</span><strong>{{ $appointment->service_name }}</strong></div>
                            <div class="appt-detail-row"><span>Thời gian</span><strong>{{ $appointment->appointment_time }}, {{ $appointment->appointment_date?->format('d/m/Y') }}</strong></div>
                            <div class="appt-detail-row"><span>Địa điểm</span><strong>{{ $storeName }} - {{ $storeAddress }}</strong></div>
                            <div class="appt-detail-row"><span>Khách hàng</span><strong>{{ $appointment->customer_name }}</strong></div>
                            <div class="appt-detail-row"><span>Số điện thoại</span><strong>{{ substr($appointment->customer_phone, 0, 3) }}***{{ substr($appointment->customer_phone, -2) }}</strong></div>
                            <div class="appt-detail-row"><span>Email</span><strong>{{ $appointment->customer_email }}</strong></div>
                            <div class="appt-detail-row"><span>Số lần đổi lịch</span><strong>{{ $appointment->reschedule_count }} / {{ \App\Models\Appointment::MAX_RESCHEDULE_COUNT }}</strong></div>
                        </div>

                        @if ($appointment->canReschedule())
                            <form class="appt-reschedule-form" method="post" action="{{ route('appointments.reschedule', $appointment) }}" data-slots-url="{{ route('appointments.unavailable-slots') }}" data-exclude-appointment-id="{{ $appointment->id }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="code" value="{{ request('code', $appointment->code) }}">
                                <input type="hidden" name="contact" value="{{ request('contact') }}">

                                <h2>Đổi lịch hẹn</h2>
                                <div class="appt-lookup-grid">
                                    <label class="appt-field">
                                        <span>Ngày mới</span>
                                        <input type="date" name="appointment_date" id="rescheduleDate" min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ old('appointment_date', $appointment->appointment_date?->toDateString()) }}" required>
                                    </label>
                                    <label class="appt-field">
                                        <span>Giờ mới</span>
                                        <select name="appointment_time" id="rescheduleTime" required>
                                            @foreach ($timeSlots as $slot)
                                                <option value="{{ $slot }}" @selected(old('appointment_time', $appointment->appointment_time) === $slot)>{{ $slot }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <label class="appt-field">
                                    <span>Lý do đổi lịch</span>
                                    <textarea name="reschedule_reason" rows="3" maxlength="500">{{ old('reschedule_reason') }}</textarea>
                                </label>
                                <div class="appt-panel-actions">
                                    <button class="advisor-btn advisor-btn--solid" type="submit">Gửi yêu cầu đổi lịch</button>
                                </div>
                            </form>
                        @else
                            <p class="appt-confirm-note mt-3 mb-0">Lịch hẹn này hiện không còn đủ điều kiện đổi lịch trực tuyến.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.appt-reschedule-form');
            if (!form) return;

            const dateInput = document.getElementById('rescheduleDate');
            const timeSelect = document.getElementById('rescheduleTime');
            const slotsUrl = form.dataset.slotsUrl;
            const excludeAppointmentId = form.dataset.excludeAppointmentId;

            async function refreshUnavailableSlots() {
                if (!slotsUrl || !dateInput || !timeSelect || !dateInput.value) return;

                const selectedValue = timeSelect.value;
                const url = new URL(slotsUrl, window.location.origin);
                url.searchParams.set('date', dateInput.value);
                if (excludeAppointmentId) {
                    url.searchParams.set('exclude_appointment_id', excludeAppointmentId);
                }

                try {
                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    Array.from(timeSelect.options).forEach((option) => {
                        const slot = data.slots?.[option.value];
                        const disabled = slot && slot.available === false;
                        const originalLabel = option.dataset.originalLabel || option.textContent;

                        option.dataset.originalLabel = originalLabel;
                        option.disabled = disabled;
                        option.textContent = disabled ? originalLabel + ' - ' + slot.label : originalLabel;
                    });

                    const selectedOption = Array.from(timeSelect.options).find((option) => option.value === selectedValue);
                    if (selectedOption && selectedOption.disabled) {
                        const firstAvailable = Array.from(timeSelect.options).find((option) => !option.disabled);
                        timeSelect.value = firstAvailable ? firstAvailable.value : '';
                    }
                } catch (error) {
                    console.warn('Không thể tải khung giờ khả dụng.', error);
                }
            }

            dateInput.addEventListener('change', refreshUnavailableSlots);
            refreshUnavailableSlots();
        });
    </script>
@endpush
