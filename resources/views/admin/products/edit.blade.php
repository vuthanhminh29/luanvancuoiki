@extends('admin.layouts.app')

@section('title', 'Sửa sản phẩm')

@push('styles')
<style>
.pa-page { background:#f4f7fb; min-height:calc(100vh - 72px); padding:24px; }
.pa-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
.pa-kicker { color:#0f766e; font-size:13px; font-weight:800; letter-spacing:.04em; margin-bottom:6px; text-transform:uppercase; }
.pa-title { color:#101828; font-size:26px; font-weight:900; line-height:1.2; margin:0; }
.pa-subtitle { color:#667085; font-size:14px; margin:8px 0 0; }
.pa-btn { align-items:center; background:#fff; border:1px solid #d0d5dd; border-radius:8px; color:#344054; display:inline-flex; font-size:14px; font-weight:800; gap:8px; min-height:40px; padding:0 14px; text-decoration:none; }
.pa-btn.primary, .pa-submit { background:#0f766e; border-color:#0f766e; color:#fff; }
.pa-card { background:#fff; border:1px solid #e4e7ec; border-radius:8px; box-shadow:0 8px 24px rgba(16,24,40,.04); padding:18px; }
.pa-form-grid { display:grid; gap:14px; grid-template-columns:repeat(2,minmax(0,1fr)); }
.pa-field { margin-bottom:14px; }
.pa-label { color:#344054; display:block; font-size:13px; font-weight:800; margin-bottom:7px; }
.pa-input, .pa-select, .pa-textarea { background:#fff; border:1px solid #d0d5dd; border-radius:8px; color:#101828; font-size:14px; min-height:42px; padding:9px 12px; width:100%; }
.pa-textarea { min-height:130px; resize:vertical; }
.pa-inline-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:16px; }
@media (max-width:768px) { .pa-page { padding:16px; } .pa-head { display:block; } .pa-form-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="pa-page">
    <div class="pa-head">
        <div>
            <div class="pa-kicker">Quản trị sản phẩm</div>
            <h1 class="pa-title">Cập nhật sản phẩm</h1>
            <p class="pa-subtitle">{{ $product->name }}</p>
        </div>
        <a class="pa-btn" href="{{ route('admin.products.index') }}"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>

    <div class="pa-card">
        <form method="post" action="{{ route('admin.products.update', $product) }}">
            @csrf
            @method('PUT')
            <div class="pa-form-grid">
                <div class="pa-field">
                    <label class="pa-label">Tên sản phẩm</label>
                    <input class="pa-input" name="name" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="pa-field">
                    <label class="pa-label">Danh mục</label>
                    <select class="pa-select" name="category_id" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pa-field">
                    <label class="pa-label">Thương hiệu</label>
                    <select class="pa-select" name="brand_id">
                        <option value="">Không có</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected($product->brand_id === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pa-field">
                    <label class="pa-label">Giá gốc</label>
                    <input class="pa-input" type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" required>
                </div>
                <div class="pa-field">
                    <label class="pa-label">Giá sale</label>
                    <input class="pa-input" type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}">
                </div>
                <div class="pa-field">
                    <label class="pa-label">Trạng thái</label>
                    <select class="pa-select" name="status">
                        @foreach (['DRAFT','ACTIVE','INACTIVE','DISCONTINUED'] as $status)
                            <option value="{{ $status }}" @selected($product->status === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pa-field">
                <label class="pa-label">Mô tả</label>
                <textarea class="pa-textarea" name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="pa-inline-actions">
                <button class="pa-btn primary" type="submit"><i class="fas fa-save"></i> Lưu sản phẩm</button>
                <a class="pa-btn" href="{{ route('admin.products.index') }}">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection
