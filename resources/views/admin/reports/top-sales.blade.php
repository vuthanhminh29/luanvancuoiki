@extends('admin.layouts.app')

@section('title', 'Top lượt bán')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
@endphp

@section('content')
<div class="report-page">
<div class="report-inner">
    <div class="report-head">
        <div>
            <div class="report-kicker">Top bán chạy</div>
            <h1 class="report-title">Sản phẩm kính bán tốt nhất</h1>
            <p class="report-subtitle">Xếp hạng sản phẩm theo số lượng bán, doanh thu và lượng tồn kho còn lại.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn {{ $top === 5 ? 'active' : '' }}" href="{{ route('admin.reports.top-sales', ['top' => 5, 'date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}">Top 5</a>
            <a class="report-btn {{ $top === 10 ? 'active' : '' }}" href="{{ route('admin.reports.top-sales', ['top' => 10, 'date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}">Top 10</a>
            <a class="report-btn {{ $top === 15 ? 'active' : '' }}" href="{{ route('admin.reports.top-sales', ['top' => 15, 'date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}">Top 15</a>
            <a class="report-btn {{ $top === 30 ? 'active' : '' }}" href="{{ route('admin.reports.top-sales', ['top' => 30, 'date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}">Top 30</a>
            <a class="report-btn primary" href="{{ route('admin.reports.orders', ['date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}"><i class="fas fa-table"></i> Bảng bán hàng</a>
        </div>
    </div>

    @include('admin.reports._date-filter')

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Số sản phẩm</div>
                <span class="report-metric-icon"><i class="fas fa-trophy"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($topProducts->count()) }}</div>
            <p class="report-metric-note">Trong danh sách top {{ $int($top) }}</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Tổng đã bán</div>
                <span class="report-metric-icon"><i class="fas fa-shopping-bag"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($totalSold) }}</div>
            <p class="report-metric-note">Số lượng theo order_items</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Doanh thu top</div>
                <span class="report-metric-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="report-metric-value">{{ $money($totalRevenue) }}</div>
            <p class="report-metric-note">Không tính đơn hủy</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Bán TB / sản phẩm</div>
                <span class="report-metric-icon"><i class="fas fa-balance-scale"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($topProducts->count() > 0 ? $totalSold / $topProducts->count() : 0) }}</div>
            <p class="report-metric-note">Dùng để cân đối nhập hàng</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Biểu đồ top sản phẩm</h2>
            <p class="report-section-note">Xếp theo số lượng bán</p>
        </div>
        @if ($topProducts->isNotEmpty())
            <div class="report-chart" style="height: {{ min(max($topProducts->count() * 34, 300), 560) }}px">
                <canvas id="topProductsChart"></canvas>
            </div>
        @else
            <div class="report-empty">Chưa có sản phẩm phát sinh bán hàng.</div>
        @endif
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Chi tiết top sản phẩm</h2>
            <p class="report-section-note">Danh mục và thương hiệu nằm dưới tên sản phẩm</p>
        </div>
        @if ($topProducts->isNotEmpty())
            <div class="table-responsive report-table-shell">
                <table class="table report-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 54px;">#</th>
                            <th>Sản phẩm</th>
                            <th class="text-end">Đơn</th>
                            <th class="text-end">Đã bán</th>
                            <th class="text-end">Tồn kho</th>
                            <th class="text-end">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $row)
                            <tr>
                                <td class="report-rank">{{ $loop->iteration }}</td>
                                <td class="report-main-cell">
                                    <strong>{{ $row->product_name }}</strong>
                                    <div class="report-meta">
                                        <span>{{ $row->category_name }}</span>
                                        <span>{{ $row->brand_name }}</span>
                                    </div>
                                </td>
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
                borderRadius: 6
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + Number(context.parsed.x).toLocaleString('vi-VN');
                    }
                }
            }
        },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0 } },
            y: { ticks: { autoSkip: false } }
        }
    }
});
</script>
@endpush
@endif
