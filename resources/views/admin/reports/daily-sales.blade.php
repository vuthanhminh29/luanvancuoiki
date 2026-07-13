@extends('admin.layouts.app')

@section('title', 'Lượt bán theo ngày')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
    $date = fn ($value) => \Carbon\Carbon::parse($value)->format('d/m/Y');
@endphp

@section('content')
<div class="report-page">
    <div class="report-head">
        <div>
            <div class="report-kicker">Báo cáo theo ngày</div>
            <h1 class="report-title">Xu hướng bán hàng {{ $int($limitDay) }} ngày</h1>
            <p class="report-subtitle">Theo dõi số đơn, số lượng kính đã bán và doanh thu theo từng ngày.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn" href="{{ route('admin.reports.daily-sales', ['limit_day' => 7, 'type_chart' => $chartType]) }}">7 ngày</a>
            <a class="report-btn" href="{{ route('admin.reports.daily-sales', ['limit_day' => 14, 'type_chart' => $chartType]) }}">14 ngày</a>
            <a class="report-btn" href="{{ route('admin.reports.daily-sales', ['limit_day' => 30, 'type_chart' => $chartType]) }}">30 ngày</a>
            <a class="report-btn" href="{{ route('admin.reports.daily-sales', ['limit_day' => 90, 'type_chart' => $chartType]) }}">90 ngày</a>
            <a class="report-btn primary" href="{{ route('admin.reports.daily-sales', ['limit_day' => $limitDay, 'type_chart' => $chartType === 'bar' ? 'line' : 'bar']) }}"><i class="fas fa-chart-line"></i> Đổi dạng</a>
        </div>
    </div>

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-label">Số đơn</div>
            <div class="report-metric-value">{{ $int($totalOrders) }}</div>
            <p class="report-metric-note">Trong khoảng đã chọn</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Số lượng bán</div>
            <div class="report-metric-value">{{ $int($totalSold) }}</div>
            <p class="report-metric-note">Tổng sản phẩm trong đơn</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Doanh thu</div>
            <div class="report-metric-value">{{ $money($totalRevenue) }}</div>
            <p class="report-metric-note">Không tính đơn hủy/mất hàng</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Doanh thu TB/ngày</div>
            <div class="report-metric-value">{{ $money($avgRevenue) }}</div>
            <p class="report-metric-note">Trên ngày có phát sinh dữ liệu</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Biểu đồ xu hướng</h2>
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
        </div>
        @if ($dailySales->isNotEmpty())
            <div class="table-responsive">
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
