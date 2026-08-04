@extends('admin.layouts.app')

@section('title', 'Top lượt bán')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
@endphp

@section('content')
<div class="report-page">
    <div class="report-head">
        <div>
            <div class="report-kicker">Top bán chạy</div>
            <h1 class="report-title">Sản phẩm kính bán tốt nhất</h1>
            <p class="report-subtitle">Xếp hạng sản phẩm theo số lượng bán, doanh thu và lượng tồn kho còn lại.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn" href="{{ route('admin.reports.top-sales', ['top' => 5]) }}">Top 5</a>
            <a class="report-btn" href="{{ route('admin.reports.top-sales', ['top' => 10]) }}">Top 10</a>
            <a class="report-btn" href="{{ route('admin.reports.top-sales', ['top' => 15]) }}">Top 15</a>
            <a class="report-btn" href="{{ route('admin.reports.top-sales', ['top' => 30]) }}">Top 30</a>
            <a class="report-btn primary" href="{{ route('admin.reports.orders') }}"><i class="fas fa-table"></i> Bảng bán hàng</a>
        </div>
    </div>

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-label">Số sản phẩm</div>
            <div class="report-metric-value">{{ $int($topProducts->count()) }}</div>
            <p class="report-metric-note">Trong danh sách top {{ $int($top) }}</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Tổng đã bán</div>
            <div class="report-metric-value">{{ $int($totalSold) }}</div>
            <p class="report-metric-note">Số lượng theo order_items</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Doanh thu top</div>
            <div class="report-metric-value">{{ $money($totalRevenue) }}</div>
            <p class="report-metric-note">Không tính đơn hủy</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Bán TB / sản phẩm</div>
            <div class="report-metric-value">{{ $int($topProducts->count() > 0 ? $totalSold / $topProducts->count() : 0) }}</div>
            <p class="report-metric-note">Dùng để cân đối nhập hàng</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Biểu đồ top sản phẩm</h2>
        </div>
        @if ($topProducts->isNotEmpty())
            <div class="report-chart">
                <canvas id="topProductsChart"></canvas>
            </div>
        @else
            <div class="report-empty">Chưa có sản phẩm phát sinh bán hàng.</div>
        @endif
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Chi tiết top sản phẩm</h2>
        </div>
        @if ($topProducts->isNotEmpty())
            <div class="table-responsive">
                <table class="table report-table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Thương hiệu</th>
                            <th class="text-end">Đơn</th>
                            <th class="text-end">Đã bán</th>
                            <th class="text-end">Tồn kho</th>
                            <th class="text-end">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $row->product_name }}</strong></td>
                                <td>{{ $row->category_name }}</td>
                                <td>{{ $row->brand_name }}</td>
                                <td class="text-end">{{ $int($row->order_count) }}</td>
                                <td class="text-end">{{ $int($row->sold_quantity) }}</td>
                                <td class="text-end">{{ $int($row->available_stock) }}</td>
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

@if ($topProducts->isNotEmpty())
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('topProductsChart'), {
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
