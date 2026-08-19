@php
    $dateRange = $dateRange ?? [
        'from' => now()->subDays(29)->toDateString(),
        'to' => now()->toDateString(),
        'label' => now()->subDays(29)->format('d/m/Y') . ' - ' . now()->format('d/m/Y'),
        'max_range_days' => 31,
        'max_to' => now()->toDateString(),
    ];
    $manualMaxRangeDays = 31;
    $baseQuery = request()->except(['date_from', 'date_to', 'date_filter', 'page']);
    $resetQuery = $baseQuery;
    $resetUrl = url()->current() . (count($resetQuery) ? '?' . http_build_query($resetQuery) : '');
    $today = now();
    $presetUrl = function ($from, $to) use ($baseQuery) {
        $query = array_merge($baseQuery, [
            'date_from' => $from,
            'date_to' => $to,
        ]);

        return url()->current() . '?' . http_build_query($query);
    };
@endphp

<div class="report-toolbar">
    <div class="report-filter-main">
        <div class="report-period">
            <span><i class="fas fa-calendar-day"></i> Khoảng thời gian</span>
            <strong>{{ $dateRange['label'] }}</strong>
        </div>
        <p class="report-data-note">Số liệu tính theo ngày tạo đơn; doanh thu không tính đơn hủy.</p>
    </div>
    <form class="report-date-form" method="get" action="{{ url()->current() }}" data-report-date-form data-max-range-days="{{ $manualMaxRangeDays }}">
        @foreach (request()->except(['date_from', 'date_to', 'date_filter', 'page']) as $name => $value)
            @if (is_scalar($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <input type="hidden" name="date_filter" value="manual">
        <label>
            <span>Từ</span>
            <input type="date" name="date_from" value="{{ $dateRange['from'] }}" data-report-date-from>
        </label>
        <label>
            <span>Đến</span>
            <input type="date" name="date_to" value="{{ $dateRange['to'] }}" data-report-date-to>
        </label>
        <button class="report-btn primary" type="submit"><i class="fas fa-filter"></i> Lọc</button>
        <a class="report-btn" href="{{ $resetUrl }}"><i class="fas fa-undo"></i> Mặc định</a>
        <div class="report-shortcuts">
            <a class="report-btn" href="{{ $presetUrl($today->toDateString(), $today->toDateString()) }}">Hôm nay</a>
            <a class="report-btn" href="{{ $presetUrl($today->copy()->subDays(6)->toDateString(), $today->toDateString()) }}">7 ngày</a>
            <a class="report-btn" href="{{ $presetUrl($today->copy()->subDays(29)->toDateString(), $today->toDateString()) }}">30 ngày</a>
            <a class="report-btn" href="{{ $presetUrl($today->copy()->startOfMonth()->toDateString(), $today->toDateString()) }}">Tháng này</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-report-date-form]').forEach(function (form) {
        var fromInput = form.querySelector('[data-report-date-from]');
        var toInput = form.querySelector('[data-report-date-to]');
        var maxRangeDays = parseInt(form.dataset.maxRangeDays || '31', 10);

        if (!fromInput || !toInput || !Number.isFinite(maxRangeDays)) {
            return;
        }

        function parseDate(value) {
            return value ? new Date(value + 'T00:00:00') : null;
        }

        function formatDate(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');

            return year + '-' + month + '-' + day;
        }

        function addDays(date, days) {
            var next = new Date(date.getTime());
            next.setDate(next.getDate() + days);

            return next;
        }

        function setDateLimits() {
            var fromDate = parseDate(fromInput.value);

            if (fromDate) {
                var maxTo = formatDate(addDays(fromDate, maxRangeDays - 1));
                toInput.min = fromInput.value;
                toInput.max = maxTo;
            }
        }

        function syncDateLimits() {
            var fromDate = parseDate(fromInput.value);
            var toDate = parseDate(toInput.value);

            setDateLimits();

            if (fromDate) {
                var maxTo = formatDate(addDays(fromDate, maxRangeDays - 1));

                if (!toDate || toInput.value < fromInput.value) {
                    toInput.value = fromInput.value;
                } else if (toInput.value > maxTo) {
                    toInput.value = maxTo;
                }
            }

            setDateLimits();
        }

        fromInput.addEventListener('focus', setDateLimits);
        toInput.addEventListener('focus', setDateLimits);

        fromInput.addEventListener('change', function () {
            syncDateLimits();
        });

        toInput.addEventListener('change', function () {
            syncDateLimits();
        });

        form.addEventListener('submit', function () {
            syncDateLimits();
        });
    });
});
</script>
@endpush
