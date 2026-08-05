@extends('admin.layouts.app')

@section('title', $title)

@include('admin.products._styles')

@php
    $value = fn ($field, $fallback = '') => old($field, $product?->{$field} ?? $fallback);
    $variants = old('variant_color_id')
        ? collect(old('variant_color_id'))->map(fn ($colorId, $i) => [
            'color_id' => $colorId,
            'lens_size_id' => old('variant_lens_size_id.' . $i),
            'variant_price' => old('variant_price.' . $i),
            'status' => old('variant_status.' . $i, 'ACTIVE'),
        ])
        : ($product?->variants?->map(fn ($variant) => [
            'id' => $variant->id,
            'color_id' => $variant->color_id,
            'lens_size_id' => $variant->lens_size_id,
            'variant_price' => $variant->variant_price,
            'status' => $variant->status,
        ]) ?? collect([['id' => '', 'color_id' => '', 'lens_size_id' => '', 'variant_price' => '', 'status' => 'ACTIVE']]));
    if ($variants->isEmpty()) {
        $variants = collect([['id' => '', 'color_id' => '', 'lens_size_id' => '', 'variant_price' => '', 'status' => 'ACTIVE']]);
    }
@endphp

@section('content')
<div class="pa-page">
    <form method="post" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <div class="pa-head">
            <div>
                <div class="pa-kicker">Quản trị sản phẩm</div>
                <h1 class="pa-title">{{ $title }}</h1>
                <p class="pa-subtitle">{{ $subtitle }}</p>
            </div>
            <div class="pa-actions">
                <a class="pa-btn" href="{{ $backRoute }}"><i class="fas fa-arrow-left"></i> Quay lại</a>
                @if ($product)
                    <a class="pa-btn" href="{{ route('products.show', $product) }}" target="_blank"><i class="fas fa-eye"></i> Xem</a>
                @endif
                <button class="pa-btn primary" type="submit" name="save_product" value="1"><i class="fas fa-save"></i> {{ $submitLabel }}</button>
            </div>
        </div>

        <div class="pa-grid">
            <section>
                <div class="pa-card pa-section">
                    <h2 class="pa-section-title">Thông tin sản phẩm</h2>
                    <div class="pa-field">
                        <label class="pa-label">Tên sản phẩm</label>
                        <input class="pa-input" type="text" name="name" value="{{ $value('name') }}" placeholder="Ví dụ: Ray-Ban Aviator Classic">
                        @error('name')<span class="pa-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="pa-form-grid">
                        <div class="pa-field">
                            <label class="pa-label">Danh mục</label>
                            <select class="pa-select" name="category_id">
                                <option value="">Chọn danh mục</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $value('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<span class="pa-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Thương hiệu</label>
                            <select class="pa-select" name="brand_id">
                                <option value="">Không có thương hiệu</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected((string) $value('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Dáng gọng</label>
                            <select class="pa-select" name="frame_shape_id">
                                <option value="">Chọn dáng gọng</option>
                                @foreach ($frameShapes as $shape)
                                    <option value="{{ $shape->id }}" @selected((string) $value('frame_shape_id') === (string) $shape->id)>{{ $shape->name }}</option>
                                @endforeach
                            </select>
                            @error('frame_shape_id')<span class="pa-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Chất liệu gọng</label>
                            <select class="pa-select" name="frame_material_id">
                                <option value="">Không có chất liệu</option>
                                @foreach ($frameMaterials as $material)
                                    <option value="{{ $material->id }}" @selected((string) $value('frame_material_id') === (string) $material->id)>{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Chống UV</label>
                            <select class="pa-select" name="uv_protection">
                                @foreach (['NONE' => 'Không có', 'UV380' => 'UV380', 'UV400' => 'UV400'] as $key => $label)
                                    <option value="{{ $key }}" @selected($value('uv_protection', 'NONE') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Trạng thái</label>
                            <select class="pa-select" name="status">
                                @foreach (['DRAFT' => 'Bản nháp', 'ACTIVE' => 'Đang bán', 'INACTIVE' => 'Tạm ẩn', 'DISCONTINUED' => 'Ngừng bán'] as $key => $label)
                                    <option value="{{ $key }}" @selected($value('status', 'DRAFT') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="pa-hint">Sản phẩm mới thường để bản nháp hoặc tạm ẩn cho đến khi nhập kho.</div>
                        </div>
                    </div>

                    <div class="pa-form-grid">
                        <div class="pa-field">
                            <label class="pa-label">Giá nhập</label>
                            <input class="pa-input" type="number" min="0" step="1000" id="import_price" name="import_price" value="{{ $value('import_price', 0) }}">
                            <div class="pa-hint">Giá vốn mua vào từ nhà cung cấp.</div>
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Giá bán niêm yết</label>
                            <input class="pa-input" type="number" min="0" step="1000" id="base_price" name="base_price" value="{{ $value('base_price') }}" placeholder="Tự động tính = Giá nhập × {{ config('pricing.markup', 1.45) }}">
                            <div class="pa-hint" id="margin_hint">Tự động gợi ý nếu để trống.</div>
                            @error('base_price')<span class="pa-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="pa-field">
                            <label class="pa-label">Giá khuyến mãi</label>
                            <input class="pa-input" type="number" min="0" step="1000" id="sale_price" name="sale_price" value="{{ $value('sale_price') }}" placeholder="Để trống nếu không giảm">
                            <div class="pa-hint">Nhập khi sản phẩm đang khuyến mãi. Giá này sẽ hiển thị ngoài website.</div>
                            @error('sale_price')<span class="pa-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const markup = {{ config('pricing.markup', 1.45) }};
                        const roundTo = {{ config('pricing.round_to', 1000) }};
                        const importEl = document.getElementById('import_price');
                        const baseEl = document.getElementById('base_price');
                        const marginHint = document.getElementById('margin_hint');

                        function updateMarginHint() {
                            const imp = parseFloat(importEl.value) || 0;
                            const base = parseFloat(baseEl.value) || 0;
                            if (imp > 0 && base > 0) {
                                const profit = base - imp;
                                const marginPct = ((profit / base) * 100).toFixed(1);
                                marginHint.textContent = `Lợi nhuận gộp: ${profit.toLocaleString('vi-VN')}đ (${marginPct}%)`;
                            } else {
                                marginHint.textContent = 'Tự động gợi ý nếu để trống.';
                            }
                        }

                        importEl?.addEventListener('input', function () {
                            if (baseEl && baseEl.dataset.touched !== 'true') {
                                const imp = parseFloat(importEl.value) || 0;
                                if (imp > 0) {
                                    baseEl.value = Math.ceil((imp * markup) / roundTo) * roundTo;
                                }
                            }
                            updateMarginHint();
                        });

                        baseEl?.addEventListener('input', function () {
                            baseEl.dataset.touched = 'true';
                            updateMarginHint();
                        });

                        updateMarginHint();
                    });
                    </script>

                    <div class="pa-field">
                        <label class="pa-label">Mô tả sản phẩm</label>
                        <textarea class="pa-textarea" name="description" id="product_details">{{ $value('description') }}</textarea>
                    </div>
                </div>

                <div class="pa-card pa-section">
                    <h2 class="pa-section-title">Biến thể màu và size tròng</h2>
                    <div class="table-responsive">
                        <table class="table pa-table pa-variant-table" id="variantTable">
                            <thead>
                                <tr>
                                    <th>Màu kính</th>
                                    <th>Size tròng</th>
                                    <th>Giá riêng</th>
                                    <th>Trạng thái</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($variants as $row)
                                    <tr>
                                        <input type="hidden" name="variant_id[]" value="{{ $row['id'] ?? '' }}">
                                        <td>
                                            <select class="pa-select color-select" name="variant_color_id[]">
                                                <option value="">Chọn màu</option>
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color->id }}" data-color="{{ $color->hex_code }}" @selected((string) $row['color_id'] === (string) $color->id)>{{ $color->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="pa-color-dot"></span>
                                        </td>
                                        <td>
                                            <select class="pa-select" name="variant_lens_size_id[]">
                                                <option value="">Chọn size</option>
                                                @foreach ($lensSizes as $size)
                                                    <option value="{{ $size->id }}" @selected((string) $row['lens_size_id'] === (string) $size->id)>{{ $size->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input class="pa-input" type="number" min="0" step="1000" name="variant_price[]" value="{{ $row['variant_price'] }}" placeholder="Theo giá niêm yết"></td>
                                        <td>
                                            <select class="pa-select" name="variant_status[]">
                                                @foreach (['ACTIVE' => 'Đang bán', 'OUT_OF_STOCK' => 'Hết hàng', 'DISCONTINUED' => 'Ngừng bán'] as $key => $label)
                                                    <option value="{{ $key }}" @selected(($row['status'] ?? 'ACTIVE') === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><button class="pa-btn danger remove-variant" type="button"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pa-inline-actions">
                        <button class="pa-btn" type="button" id="addVariant"><i class="fas fa-plus"></i> Thêm biến thể</button>
                    </div>
                </div>
            </section>

            <aside>
                <div class="pa-card">
                    <h2 class="pa-section-title">Hình ảnh</h2>
                    <div class="pa-field">
                        <label class="pa-label">Ảnh chính</label>
                        <input class="pa-file" name="thumbnail_url" type="file" accept=".jpg,.jpeg,.png,.webp">
                        @if ($product?->image_url)
                            <img class="pa-preview" src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @endif
                    </div>
                    <div class="pa-field"><label class="pa-label">Ảnh phụ 1</label><input class="pa-file" name="image1" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                    <div class="pa-field"><label class="pa-label">Ảnh phụ 2</label><input class="pa-file" name="image2" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                    <div class="pa-field"><label class="pa-label">Ảnh phụ 3</label><input class="pa-file" name="image3" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                    <div class="pa-hint">Ảnh được lưu trong `upload/anh_san_pham`.</div>
                    @if ($product?->images?->isNotEmpty())
                        <div class="pa-inline-actions">
                            @foreach ($product->images as $image)
                                <img class="pa-thumb" src="{{ $image->url }}" alt="{{ $image->alt_text }}">
                            @endforeach
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('#variantTable tbody');
    const template = table.querySelector('tr').cloneNode(true);

    function refreshColorDot(select) {
        const option = select.options[select.selectedIndex];
        const dot = select.parentElement.querySelector('.pa-color-dot');
        dot.style.background = option ? option.dataset.color || '#fff' : '#fff';
    }

    document.querySelectorAll('.color-select').forEach(refreshColorDot);

    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('color-select')) {
            refreshColorDot(event.target);
        }
    });

    document.getElementById('addVariant').addEventListener('click', function () {
        const row = template.cloneNode(true);
        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        row.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
        row.querySelectorAll('.pa-error').forEach(function (error) { error.remove(); });
        table.appendChild(row);
        refreshColorDot(row.querySelector('.color-select'));
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.remove-variant')) return;
        if (table.querySelectorAll('tr').length > 1) {
            event.target.closest('tr').remove();
        }
    });
});
</script>
@endpush
