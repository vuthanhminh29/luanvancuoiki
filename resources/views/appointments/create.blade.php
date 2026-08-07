@extends('layouts.app')

@section('title', 'Đặt lịch đo thị lực - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/advisor-shared.css') }}?v={{ file_exists(public_path('css/views/advisor-shared.css')) ? filemtime(public_path('css/views/advisor-shared.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/views/appointment.css') }}?v={{ file_exists(public_path('css/views/appointment.css')) ? filemtime(public_path('css/views/appointment.css')) : time() }}">
@endpush

@section('content')
    @php
        $todayIso = now()->toDateString();
    @endphp

    <nav class="advisor-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}"><i class="fa fa-home" aria-hidden="true"></i> Trang chủ</a>
        <span aria-hidden="true">/</span>
        <strong>Đặt lịch đo thị lực</strong>
    </nav>

    <section class="advisor-page appt-page">
        <div class="advisor-container">
            <header class="advisor-header">
                <h1>Đặt lịch đo thị lực</h1>
                <p>Chọn dịch vụ, khung giờ và để lại thông tin — nhân viên Atelier Optique sẽ xác nhận lịch hẹn của bạn.</p>
            </header>

            @if ($confirmed)
                <div class="appt-confirm" role="status">
                    <div class="appt-confirm-icon" aria-hidden="true"><i class="fas fa-check"></i></div>
                    <h2>Đặt lịch thành công</h2>
                    <p class="appt-confirm-code">Mã lịch hẹn: <strong>{{ $confirmed->code }}</strong></p>

                    <dl class="appt-confirm-details">
                        <div>
                            <dt>Dịch vụ</dt>
                            <dd>{{ $confirmed->service_name }} @if ($confirmed->price > 0)&mdash; {{ number_format((float) $confirmed->price, 0, ',', '.') }}đ @else &mdash; Miễn phí @endif</dd>
                        </div>
                        <div>
                            <dt>Thời gian</dt>
                            <dd>{{ $confirmed->appointment_time }}, {{ \Illuminate\Support\Carbon::parse($confirmed->appointment_date)->translatedFormat('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt>Địa điểm</dt>
                            <dd>{{ $storeName }} &mdash; {{ $storeAddress }}</dd>
                        </div>
                        <div>
                            <dt>Khách hàng</dt>
                            <dd>{{ $confirmed->customer_name }} &bull; {{ $confirmed->customer_phone }}</dd>
                        </div>
                    </dl>

                    <p class="appt-confirm-note">Vui lòng có mặt trước giờ hẹn 10 phút. Cần đổi lịch, gọi hotline 1900 6789.</p>

                    <div class="appt-confirm-actions">
                        <a class="advisor-btn advisor-btn--ghost" href="{{ route('home') }}">Về trang chủ</a>
                        <a class="advisor-btn advisor-btn--solid" href="{{ route('appointments.create') }}">Đặt thêm lịch khác</a>
                    </div>
                </div>
            @else
                <ol class="advisor-steps" data-appt-steps>
                    <li class="is-active" data-step-label="1"><span>Chọn dịch vụ</span></li>
                    <li data-step-label="2"><span>Chọn thời gian</span></li>
                    <li data-step-label="3"><span>Thông tin đặt lịch</span></li>
                </ol>

                @if ($errors->any())
                    <div class="appt-alert" role="alert">
                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form action="{{ route('appointments.store') }}" method="post" class="appt-wizard" id="apptForm" novalidate>
                    @csrf
                    <input type="hidden" name="service_code" id="apptServiceCode" value="{{ old('service_code') }}">
                    <input type="hidden" name="appointment_time" id="apptTime" value="{{ old('appointment_time') }}">

                    <div class="appt-panel is-active" data-panel="1">
                        <div class="appt-services">
                            @foreach ($services as $code => $service)
                                <button type="button" class="appt-service-card" data-service="{{ $code }}"
                                    data-service-name="{{ $service['name'] }}" data-service-price="{{ $service['price'] }}">
                                    <span class="appt-service-name">{{ mb_strtoupper($service['name']) }}</span>
                                    <span class="appt-service-price">{{ $service['price'] > 0 ? number_format($service['price'], 0, ',', '.') . 'đ' : 'Miễn phí' }}</span>
                                    <span class="appt-service-desc">{{ $service['description'] }}</span>
                                    <span class="appt-service-duration"><i class="far fa-clock" aria-hidden="true"></i> {{ $service['duration'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="appt-panel-actions">
                            <button type="button" class="advisor-btn advisor-btn--solid" data-next disabled>Tiếp tục</button>
                        </div>
                    </div>

                    <div class="appt-panel" data-panel="2">
                        <div class="appt-schedule">
                            <div class="appt-store-card">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <div>
                                    <strong>{{ $storeName }}</strong>
                                    <span>{{ $storeAddress }}</span>
                                </div>
                            </div>

                            <label class="appt-field">
                                <span>Chọn ngày</span>
                                <input type="date" name="appointment_date" id="apptDate" min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ old('appointment_date', $todayIso) }}" required>
                            </label>

                            <div class="appt-field">
                                <span>Chọn giờ</span>
                                <div class="appt-time-grid" role="group" aria-label="Khung giờ">
                                    @foreach ($timeSlots as $slot)
                                        <button type="button" class="appt-time-btn" data-time="{{ $slot }}">{{ $slot }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="appt-panel-actions">
                            <button type="button" class="advisor-btn advisor-btn--ghost" data-back>Quay lại</button>
                            <button type="button" class="advisor-btn advisor-btn--solid" data-next disabled>Tiếp tục</button>
                        </div>
                    </div>

                    <div class="appt-panel" data-panel="3">
                        <div class="appt-summary" id="apptSummary" aria-live="polite"></div>

                        <div class="appt-form-grid">
                            <label class="appt-field">
                                <span>Họ và tên *</span>
                                <input type="text" name="customer_name" maxlength="100" value="{{ old('customer_name') }}" required>
                            </label>
                            <label class="appt-field">
                                <span>Số điện thoại *</span>
                                <input type="tel" name="customer_phone" maxlength="15" value="{{ old('customer_phone') }}" required>
                            </label>
                            <label class="appt-field appt-field--wide">
                                <span>Email (không bắt buộc)</span>
                                <input type="email" name="customer_email" maxlength="190" value="{{ old('customer_email') }}">
                            </label>
                            <label class="appt-field appt-field--wide">
                                <span>Ghi chú (không bắt buộc)</span>
                                <textarea name="note" rows="3" maxlength="500">{{ old('note') }}</textarea>
                            </label>
                        </div>

                        <div class="appt-panel-actions">
                            <button type="button" class="advisor-btn advisor-btn--ghost" data-back>Quay lại</button>
                            <button type="submit" class="advisor-btn advisor-btn--solid">Xác nhận đặt lịch</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('apptForm');
            if (!form) return;

            const steps = document.querySelectorAll('[data-appt-steps] li');
            const panels = form.querySelectorAll('.appt-panel');
            const serviceInput = document.getElementById('apptServiceCode');
            const timeInput = document.getElementById('apptTime');
            const dateInput = document.getElementById('apptDate');
            const summaryBox = document.getElementById('apptSummary');
            let selectedServiceName = '';
            let selectedServicePrice = 0;

            function showPanel(index) {
                panels.forEach((panel, i) => panel.classList.toggle('is-active', i === index));
                steps.forEach((step, i) => step.classList.toggle('is-active', i === index));
            }

            form.querySelectorAll('[data-next]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const currentPanel = this.closest('.appt-panel');
                    const currentIndex = Array.from(panels).indexOf(currentPanel);
                    if (currentIndex === 2) return;
                    if (currentIndex === 1) updateSummary();
                    showPanel(currentIndex + 1);
                });
            });

            form.querySelectorAll('[data-back]').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const currentPanel = this.closest('.appt-panel');
                    const currentIndex = Array.from(panels).indexOf(currentPanel);
                    showPanel(Math.max(0, currentIndex - 1));
                });
            });

            form.querySelectorAll('.appt-service-card').forEach((card) => {
                card.addEventListener('click', function () {
                    form.querySelectorAll('.appt-service-card').forEach((c) => c.classList.remove('is-selected'));
                    this.classList.add('is-selected');
                    serviceInput.value = this.dataset.service;
                    selectedServiceName = this.dataset.serviceName;
                    selectedServicePrice = parseInt(this.dataset.servicePrice || '0', 10);
                    const nextBtn = this.closest('.appt-panel').querySelector('[data-next]');
                    if (nextBtn) nextBtn.disabled = false;
                });
            });

            function checkScheduleReady() {
                const nextBtn = document.querySelector('.appt-panel[data-panel="2"] [data-next]');
                if (nextBtn) nextBtn.disabled = !(dateInput.value && timeInput.value);
            }

            form.querySelectorAll('.appt-time-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    form.querySelectorAll('.appt-time-btn').forEach((b) => b.classList.remove('is-selected'));
                    this.classList.add('is-selected');
                    timeInput.value = this.dataset.time;
                    checkScheduleReady();
                });
            });

            dateInput && dateInput.addEventListener('change', checkScheduleReady);

            function updateSummary() {
                if (!summaryBox) return;
                const priceText = selectedServicePrice > 0
                    ? new Intl.NumberFormat('vi-VN').format(selectedServicePrice) + 'đ'
                    : 'Miễn phí';
                const dateText = dateInput.value
                    ? new Date(dateInput.value + 'T00:00:00').toLocaleDateString('vi-VN')
                    : '';
                summaryBox.innerHTML =
                    '<div><span>Dịch vụ</span><strong>' + selectedServiceName + ' &mdash; ' + priceText + '</strong></div>' +
                    '<div><span>Thời gian</span><strong>' + timeInput.value + ', ' + dateText + '</strong></div>';
            }
        });
    </script>
@endpush
