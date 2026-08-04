@extends('admin.layouts.app')

@section('title', 'Báo cáo đơn hàng')

@include('admin.reports._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
    $percent = fn ($value, $total) => $total > 0 ? round(((float) $value / (float) $total) * 100, 1) : 0;
    $statusLabel = function ($status) {
        return match ($status) {
            'PENDING' => ['Chờ xác nhận', 'warning'],
            'AWAITING_PAYMENT' => ['Chờ thanh toán', 'warning'],
            'CONFIRMED' => ['Đã xác nhận', 'primary'],
            'PACKING' => ['Đang đóng gói', 'info'],
            'DELIVERING' => ['Đang giao', 'primary'],
            'DELIVERED' => ['Đã giao', 'success'],
            'CANCELLED' => ['Đã hủy', 'danger'],
            'LOST_IN_TRANSIT' => ['Không hoàn tất', 'danger'],
            default => [$status, 'secondary'],
        };
    };
@endphp

@section('content')
<div class="report-page">
    <div class="report-head">
        <div>
            <div class="report-kicker">Báo cáo bán hàng</div>
            <h1 class="report-title">Hiệu quả sản phẩm kính</h1>
            <p class="report-subtitle">Đối chiếu doanh thu, số lượng bán, tồn kho và yêu cầu hoàn đổi theo từng sản phẩm.</p>
        </div>
        <div class="report-actions">
            <a class="report-btn" href="{{ route('admin.reports.daily-sales') }}"><i class="fas fa-calendar-alt"></i> Theo ngày</a>
            <a class="report-btn primary" href="{{ route('admin.reports.top-sales', ['top' => 10]) }}"><i class="fas fa-trophy"></i> Top bán chạy</a>
        </div>
    </div>

    <div class="report-grid">
        <div class="report-card">
            <div class="report-metric-label">Tổng đơn hàng</div>
            <div class="report-metric-value">{{ $int($summary->total_orders ?? 0) }}</div>
            <p class="report-metric-note">{{ $int($summary->pending_orders ?? 0) }} đơn đang chờ xử lý</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Doanh thu đã giao</div>
            <div class="report-metric-value">{{ $money($summary->delivered_revenue ?? 0) }}</div>
            <p class="report-metric-note">Chỉ tính đơn DELIVERED</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Số lượng đã bán</div>
            <div class="report-metric-value">{{ $int($summary->sold_quantity ?? 0) }}</div>
            <p class="report-metric-note">Không tính đơn hủy</p>
        </div>
        <div class="report-card">
            <div class="report-metric-label">Sản phẩm có giao dịch</div>
            <div class="report-metric-value">{{ $int($tradedProducts) }}</div>
            <p class="report-metric-note">Có phát sinh bán hàng</p>
        </div>
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Cơ cấu trạng thái đơn</h2>
        </div>
        @if ($statusReports->isNotEmpty())
            <div class="report-bars">
                @foreach ($statusReports as $row)
                    @php($status = $statusLabel($row->status))
                    <div class="report-bar-row">
                        <div>
                            <strong>{{ $status[0] }}</strong>
                            <div class="report-bar-track"><span style="width: {{ $percent($row->order_count, $maxStatus) }}%"></span></div>
                            <small class="text-muted">{{ $money($row->total_amount) }}</small>
                        </div>
                        <div class="text-end">
                            <span class="report-pill {{ $status[1] }}">{{ $int($row->order_count) }} đơn</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="report-empty">Chưa có dữ liệu đơn hàng.</div>
        @endif
    </div>

    <div class="report-card report-section">
        <div class="report-section-head">
            <h2 class="report-section-title">Chi tiết theo sản phẩm</h2>
        </div>
        @if ($productReports->isNotEmpty())
            <div class="table-responsive">
                <table class="table report-table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Thương hiệu</th>
                            <th class="text-end">Biến thể</th>
                            <th class="text-end">Tồn kho</th>
                            <th class="text-end">Đơn</th>
                            <th class="text-end">Đã bán</th>
                            <th class="text-end">Hoàn/đổi</th>
                            <th class="text-end">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productReports as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $row->product_name }}</strong></td>
                                <td>{{ $row->category_name }}</td>
                                <td>{{ $row->brand_name }}</td>
                                <td class="text-end">{{ $int($row->total_variants) }}</td>
                                <td class="text-end">
                                    {{ $int($row->available_stock) }}
                                    @if ((int) $row->low_variant_count > 0)
                                        <span class="report-pill danger ms-1">{{ $int($row->low_variant_count) }} thấp</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $int($row->order_count) }}</td>
                                <td class="text-end">{{ $int($row->sold_quantity) }}</td>
                                <td class="text-end">{{ $int($row->return_quantity) }}</td>
                                <td class="text-end"><strong>{{ $money($row->revenue) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="report-empty">Chưa có dữ liệu sản phẩm.</div>
        @endif
    </div>
</div>
@endsection
