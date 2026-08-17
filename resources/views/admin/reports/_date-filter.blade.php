@php
    $dateRange = $dateRange ?? [
        'from' => now()->subDays(29)->toDateString(),
        'to' => now()->toDateString(),
        'label' => now()->subDays(29)->format('d/m/Y') . ' - ' . now()->format('d/m/Y'),
    ];
    $baseQuery = request()->except(['date_from', 'date_to', 'page']);
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
    <form class="report-date-form" method="get" action="{{ url()->current() }}">
        @foreach (request()->except(['date_from', 'date_to', 'page']) as $name => $value)
            @if (is_scalar($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <label>
            <span>Từ</span>
            <input type="date" name="date_from" value="{{ $dateRange['from'] }}">
        </label>
        <label>
            <span>Đến</span>
            <input type="date" name="date_to" value="{{ $dateRange['to'] }}">
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
