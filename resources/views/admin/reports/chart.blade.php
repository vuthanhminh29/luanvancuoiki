@extends('admin.layouts.app')

@section('title', 'Biểu đồ lượt bán')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
    $percent = fn ($value, $total) => $total > 0 ? round(((float) $value / (float) $total) * 100, 1) : 0;
@endphp

@section('content')
<div class="report-page">
    <div class="report-head">
        <div>
            <div class="report-kicker">Biểu đồ danh mục</div>
            <h1 class="report-title">Doanh thu theo danh mục kính</h1>
            <p class="report-subtitle">So sánh số lượng bán và doanh thu giữa các nhóm kính, gọng kính và tròng kính.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn" href="{{ route('admin.reports.sales-chart', ['top' => 5]) }}">Top 5</a>
            <a class="report-btn" href="{{ route('admin.reports.sales-chart', ['top' => 10]) }}">Top 10</a>
            <a class="report-btn" href="{{ route('admin.reports.sales-chart', ['top' => 30]) }}">Top 30</a>
            <a class="report-btn primary" href="{{ route('admin.reports.top-sales', ['top' => 10]) }}"><i class="fas fa-trophy"></i> Top sản phẩm</a>
        </div>
    </div>

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-label">Danh mục hiển thị</div>
            <div class="report-metric-value">{{ $int($categorySales->count()) }}</div>
            <p class="report-metric-note">Theo top đã chọn</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Số lượng đã bán</div>
            <div class="report-metric-value">{{ $int($totalSold) }}</div>
            <p class="report-metric-note">Không tính đơn hủy</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Doanh thu ghi nhận</div>
            <div class="report-metric-value">{{ $money($totalRevenue) }}</div>
            <p class="report-metric-note">Theo dòng sản phẩm đã bán</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Giá trị TB / sản phẩm</div>
            <div class="report-metric-value">{{ $money($totalSold > 0 ? $totalRevenue / $totalSold : 0) }}</div>
            <p class="report-metric-note">Doanh thu chia số lượng</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Biểu đồ danh mục</h2>
        </div>
        @if ($categorySales->isNotEmpty())
            <div class="report-chart">
                <canvas id="categorySalesChart"></canvas>
            </div>
        @else
            <div class="report-empty">Chưa có dữ liệu bán hàng theo danh mục.</div>
        @endif
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Bảng dữ liệu</h2>
        </div>
        @if ($categorySales->isNotEmpty())
            <div class="table-responsive">
                <table class="table report-table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Danh mục</th>
                            <th class="text-end">Sản phẩm</th>
                            <th class="text-end">Đã bán</th>
                            <th class="text-end">Doanh thu</th>
                            <th class="text-end">Tỷ trọng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categorySales as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $row->category_name }}</strong></td>
                                <td class="text-end">{{ $int($row->product_count) }}</td>
                                <td class="text-end">{{ $int($row->sold_quantity) }}</td>
                                <td class="text-end"><strong>{{ $money($row->revenue) }}</strong></td>
                                <td class="text-end">{{ $percent($row->revenue, $totalRevenue) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@if ($categorySales->isNotEmpty())
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('categorySalesChart'), {
    data: {
        labels: @json($labels),
        datasets: [
            {
                type: 'bar',
                label: 'Đã bán',
                data: @json($sold),
                backgroundColor: 'rgba(15, 118, 110, .18)',
                borderColor: '#0f766e',
                borderWidth: 2,
                borderRadius: 6,
                yAxisID: 'quantity'
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
            quantity: { beginAtZero: true, position: 'left' },
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
