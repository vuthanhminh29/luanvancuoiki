@extends('admin.layouts.app')

@section('title', 'Sản phẩm')

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
            <h1 class="pa-title">Danh sách kính đang bán</h1>
            <p class="pa-subtitle">Quản lý sản phẩm, biến thể màu/size, giá bán và tồn kho khả dụng.</p>
        </div>
        <div class="pa-actions">
            <a class="pa-btn" href="{{ route('admin.products.recycle') }}"><i class="fas fa-archive"></i> Thùng lưu trữ ({{ $int($totalRecycle) }})</a>
            <a class="pa-btn primary" href="{{ route('admin.products.create') }}"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
        </div>
    </div>

    <div class="pa-card pa-section">
        <form action="{{ route('admin.products.index') }}" method="get" class="pa-form-grid">
            <div class="pa-field">
                <label class="pa-label">Tìm sản phẩm</label>
                <input class="pa-input" type="search" name="q" value="{{ request('q') }}" placeholder="Tên kính hoặc mã sản phẩm">
            </div>
            <div class="pa-field">
                <label class="pa-label">Danh mục</label>
                <select class="pa-select" name="search_cate">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('search_cate') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pa-inline-actions" style="align-items:flex-end;">
                <button class="pa-btn primary" type="submit"><i class="fas fa-filter"></i> Lọc</button>
                <a class="pa-btn" href="{{ route('admin.products.index') }}">Xóa lọc</a>
            </div>
        </form>
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
                        <th class="text-end">Đã bán</th>
                        <th class="text-end">Giá bán</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        @php
                            [$statusText, $statusClass] = $statusMeta($product->status);
                            $categoryNames = $product->categories->pluck('name')->filter()->implode(', ')
                                ?: ($product->category->name ?? 'Chưa phân loại');
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                            <td style="min-width:260px;">
                                <div class="d-flex align-items-center gap-3">
                                    <img class="pa-thumb" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                    <div>
                                        <strong>{{ $product->name }}</strong><br>
                                        <code>{{ $product->product_code ?? 'SP' . $product->id }}</code>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong>{{ $categoryNames }}</strong><br>
                                <small class="text-muted">{{ $product->brand->name ?? 'Chưa có thương hiệu' }} · {{ $product->frameShape->name ?? 'Chưa có dáng gọng' }}</small>
                            </td>
                            <td class="text-end">{{ $int($product->variants_count) }}</td>
                            <td class="text-end">
                                @if ((int) $product->quantity <= 0)
                                    <span class="pa-pill danger">Hết</span>
                                @elseif ((int) $product->quantity < 10)
                                    <strong>{{ $int($product->quantity) }}</strong>
                                    <span class="pa-pill warning ms-1">Ít</span>
                                @else
                                    <strong>{{ $int($product->quantity) }}</strong>
                                @endif
                            </td>
                            <td class="text-end">{{ $int($product->sold_quantity) }}</td>
                            <td class="text-end">
                                <strong>{{ $money($product->display_price) }}</strong>
                                @if ((float) $product->max_price > (float) $product->display_price)
                                    <br><small class="text-muted">đến {{ $money($product->max_price) }}</small>
                                @endif
                            </td>
                            <td><span class="pa-pill {{ $statusClass }}">{{ $statusText }}</span></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('products.show', $product) }}" target="_blank" title="Xem"><i class="fas fa-eye"></i></a>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.products.edit', $product) }}" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <form method="post" action="{{ route('admin.products.hidden', $product) }}" onsubmit="return confirm('Ẩn sản phẩm này?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Tạm ẩn"><i class="fas fa-archive"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="pa-empty">Không tìm thấy sản phẩm phù hợp.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pa-pagination">{{ $products->links() }}</div>
    </div>
</div>
@endsection
