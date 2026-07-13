@extends('admin.layouts.app')

@section('title', 'Thùng lưu trữ sản phẩm')

@include('admin.products._styles')

@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    $int = fn ($value) => number_format((float) $value, 0, ',', '.');
    $statusMeta = function ($status) {
        return match ($status) {
            'ACTIVE' => ['Đang bán', 'success'],
            'DRAFT' => ['Bản nháp', 'secondary'],
            'INACTIVE' => ['Tạm ẩn', 'warning'],
            'DISCONTINUED' => ['Ngừng bán', 'danger'],
            default => [$status ?: 'Không rõ', 'dark'],
        };
    };
@endphp

@section('content')
<div class="pa-page">
    <div class="pa-head">
        <div>
            <div class="pa-kicker">Quản trị sản phẩm</div>
            <h1 class="pa-title">Thùng lưu trữ sản phẩm</h1>
            <p class="pa-subtitle">Các sản phẩm đang tạm ẩn, bản nháp hoặc đã ngừng kinh doanh.</p>
        </div>
        <div class="pa-actions">
            <a class="pa-btn" href="{{ route('admin.products.index') }}"><i class="fas fa-arrow-left"></i> Danh sách sản phẩm</a>
            <a class="pa-btn primary" href="{{ route('admin.products.create') }}"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
        </div>
    </div>

    <div class="pa-card">
        <div class="table-responsive">
            <table class="table pa-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sản phẩm</th>
                        <th>Phân loại</th>
                        <th class="text-end">Biến thể</th>
                        <th class="text-end">Tồn kho</th>
                        <th class="text-end">Giá bán</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            [$statusText, $statusClass] = $statusMeta($product->status);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img class="pa-thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                    <div>
                                        <strong>{{ $product->name }}</strong><br>
                                        <code>{{ $product->product_code ?? 'SP' . $product->id }}</code>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $product->category->name ?? 'Chưa phân loại' }}</strong><br>
                                <small class="text-muted">{{ $product->brand->name ?? 'Chưa có thương hiệu' }}</small>
                            </td>
                            <td class="text-end">{{ $int($product->variants_count) }}</td>
                            <td class="text-end">{{ $int($product->quantity) }}</td>
                            <td class="text-end"><strong>{{ $money($product->display_price) }}</strong></td>
                            <td><span class="pa-pill {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.products.edit', $product) }}"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="pa-empty">Thùng lưu trữ đang trống.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pa-pagination">{{ $products->links() }}</div>
    </div>
</div>
@endsection
