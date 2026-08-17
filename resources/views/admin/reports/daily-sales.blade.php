@extends('admin.layouts.app')

@section('title', 'Lượt bán theo ngày')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => \Carbon\Carbon::parse($value)->format('d/m/Y');
    $periodUrl = function ($days) use ($chartType) {
        $to = now();

        return route('admin.reports.daily-sales', [
            'limit_day' => $days,
            'type_chart' => $chartType,
            'date_from' => $to->copy()->subDays($days - 1)->toDateString(),
            'date_to' => $to->toDateString(),
        ]);
    };
@endphp

@section('content')
<div class="report-page">
<div class="report-inner">
    <div class="report-head">
        <div>
            <div class="report-kicker">Báo cáo theo ngày</div>
            <h1 class="report-title">Xu hướng bán hàng theo ngày</h1>
            <p class="report-subtitle">Theo dõi số đơn, số lượng kính đã bán và doanh thu theo từng ngày.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn {{ $limitDay === 7 ? 'active' : '' }}" href="{{ $periodUrl(7) }}">7 ngày</a>
            <a class="report-btn {{ $limitDay === 14 ? 'active' : '' }}" href="{{ $periodUrl(14) }}">14 ngày</a>
            <a class="report-btn {{ $limitDay === 30 ? 'active' : '' }}" href="{{ $periodUrl(30) }}">30 ngày</a>
            <a class="report-btn {{ $limitDay === 90 ? 'active' : '' }}" href="{{ $periodUrl(90) }}">90 ngày</a>
            <a class="report-btn primary" href="{{ route('admin.reports.daily-sales', ['limit_day' => $limitDay, 'type_chart' => $chartType === 'bar' ? 'line' : 'bar', 'date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}"><i class="fas fa-chart-line"></i> Đổi dạng</a>
        </div>
    </div>

    @include('admin.reports._date-filter')

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Số đơn</div>
                <span class="report-metric-icon"><i class="fas fa-receipt"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($totalOrders) }}</div>
            <p class="report-metric-note">Trong khoảng đã chọn</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Số lượng bán</div>
                <span class="report-metric-icon"><i class="fas fa-glasses"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($totalSold) }}</div>
            <p class="report-metric-note">Tổng sản phẩm trong đơn</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Doanh thu</div>
                <span class="report-metric-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="report-metric-value">{{ $money($totalRevenue) }}</div>
            <p class="report-metric-note">Không tính đơn hủy</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Doanh thu TB/ngày</div>
                <span class="report-metric-icon"><i class="fas fa-calculator"></i></span>
            </div>
            <div class="report-metric-value">{{ $money($avgRevenue) }}</div>
            <p class="report-metric-note">Trên ngày có phát sinh dữ liệu</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Biểu đồ xu hướng</h2>
            <p class="report-section-note">{{ $chartType === 'bar' ? 'Cột số đơn, đường doanh thu' : 'Đường số đơn và doanh thu' }}</p>
        </div>
        @if ($dailySales->isNotEmpty())
            <div class="report-chart">
                <canvas id="dailySalesChart"></canvas>
            </div>
        @else
            <div class="report-empty">Chưa có đơn hàng trong khoảng thời gian này.</div>
        @endif
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Dữ liệu theo ngày</h2>
            <p class="report-section-note">Ngày mới nhất ở trên</p>
        </div>
        @if ($dailySales->isNotEmpty())
            <div class="table-responsive report-table-shell">
                <table class="table report-table align-middle">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th class="text-end">Số đơn</th>
                            <th class="text-end">Đã bán</th>
                            <th class="text-end">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailySales->reverse() as $row)
                            <tr>
                                <td><strong>{{ $date($row->order_date) }}</strong></td>
                                <td class="text-end">{{ $int($row->order_count) }}</td>
                                <td class="text-end">{{ $int($row->sold_quantity) }}</td>
                                <td class="text-end"><strong>{{ $money($row->revenue) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
</div>
@endsection

@if ($dailySales->isNotEmpty())
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('dailySalesChart'), {
    data: {
        labels: @json($labels),
        datasets: [
            {
                type: @json($chartType),
                label: 'Số đơn',
                data: @json($orders),
                backgroundColor: 'rgba(15, 118, 110, .18)',
                borderColor: '#0f766e',
                borderWidth: 2,
                borderRadius: 6,
                tension: .35,
                yAxisID: 'orders'
            },
            {
                type: 'line',
                label: 'Doanh thu',
                data: @json($revenue),
                borderColor: '#be123c',
                backgroundColor: 'rgba(190, 18, 60, .08)',
                borderWidth: 3,
                pointRadius: 4,
                tension: .35,
                fill: true,
                yAxisID: 'money'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.yAxisID === 'money') {
                            return context.dataset.label + ': ' + Number(context.parsed.y).toLocaleString('vi-VN') + 'đ';
                        }
                        return context.dataset.label + ': ' + Number(context.parsed.y).toLocaleString('vi-VN');
                    }
                }
            }
        },
        scales: {
            orders: { beginAtZero: true, position: 'left', ticks: { precision: 0 } },
            money: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: {
                    callback: function(value) {
                        return Number(value).toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});
</script>
@endpush
@endif
