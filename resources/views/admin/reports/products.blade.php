@extends('admin.layouts.app')

@section('title', 'Báo cáo sản phẩm - danh mục')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
    $percent = fn ($value, $total) => $total > 0 ? round(((float) $value / (float) $total) * 100, 1) : 0;
@endphp

@section('content')
<div class="report-page">
<div class="report-inner">
    <div class="report-head">
        <div>
            <div class="report-kicker">Báo cáo danh mục</div>
            <h1 class="report-title">Hiệu quả danh mục kính</h1>
            <p class="report-subtitle">Theo dõi số sản phẩm, biến thể, tồn kho và doanh thu theo từng danh mục kính.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn primary" href="{{ route('admin.reports.sales-chart', ['date_from' => $dateRange['from'], 'date_to' => $dateRange['to']]) }}"><i class="fas fa-chart-bar"></i> Xem biểu đồ</a>
        </div>
    </div>

    @include('admin.reports._date-filter')

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Danh mục đang bán</div>
                <span class="report-metric-icon"><i class="fas fa-tags"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($summary->active_categories ?? 0) }}</div>
            <p class="report-metric-note">Danh mục kính hiển thị</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Sản phẩm active</div>
                <span class="report-metric-icon"><i class="fas fa-glasses"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($summary->active_products ?? 0) }}</div>
            <p class="report-metric-note">Gọng kính, kính mát, tròng kính</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Biến thể màu/size</div>
                <span class="report-metric-icon"><i class="fas fa-palette"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($summary->total_variants ?? 0) }}</div>
            <p class="report-metric-note">Theo màu và size sản phẩm</p>
        </div>
        <div class="report-card">
            <div class="report-metric-top">
                <div class="report-metric-label">Tồn kho khả dụng</div>
                <span class="report-metric-icon"><i class="fas fa-boxes"></i></span>
            </div>
            <div class="report-metric-value">{{ $int($summary->available_stock ?? 0) }}</div>
            <p class="report-metric-note">Đã trừ số lượng giữ hàng</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Chi tiết theo danh mục</h2>
            <p class="report-section-note">Sắp xếp theo doanh thu và lượng bán</p>
        </div>
        @if ($categoryReports->isNotEmpty())
            <div class="table-responsive report-table-shell">
                <table class="table report-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 54px;">#</th>
                            <th>Danh mục</th>
                            <th class="text-end">Sản phẩm</th>
                            <th class="text-end">Kho</th>
                            <th class="text-end">Đã bán</th>
                            <th class="text-end">Doanh thu</th>
                            <th class="text-end">Giá TB</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categoryReports as $row)
                            <tr>
                                <td class="report-rank">{{ $loop->iteration }}</td>
                                <td class="report-main-cell">
                                    <strong>{{ $row->category_name }}</strong>
                                    <div class="report-bar-track"><span style="width: {{ $percent($row->revenue, $maxRevenue) }}%"></span></div>
                                    <div class="report-meta">
                                        <span>{{ $money($row->min_price) }} - {{ $money($row->max_price) }}</span>
                                        <span>{{ $int($row->variant_count) }} biến thể</span>
                                    </div>
                                </td>
                                <td class="text-end">{{ $int($row->product_count) }}</td>
                                <td class="text-end">
                                    <strong>{{ $int($row->available_stock) }}</strong>
                                    @if ((int) $row->low_stock_count > 0)
                                        <div class="mt-1"><span class="report-pill danger">{{ $int($row->low_stock_count) }} sắp hết</span></div>
                                    @else
                                        <div class="mt-1"><span class="report-pill success">Ổn</span></div>
                                    @endif
                                </td>
                                <td class="text-end">{{ $int($row->sold_quantity) }}</td>
                                <td class="text-end"><strong>{{ $money($row->revenue) }}</strong></td>
                                <td class="text-end">{{ $money($row->avg_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="report-empty">Chưa có dữ liệu danh mục.</div>
        @endif
    </div>
</div>
</div>
@endsection
