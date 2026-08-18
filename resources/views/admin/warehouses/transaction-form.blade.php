@extends('admin.layouts.app')

@php
    $isAdminUser = $isAdminUser ?? false;
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $oldVariantIds = old('variant_id', [null]);
    $oldQuantities = old('quantity', [1]);
    $oldUnitCosts = old('unit_cost', [null]);
    $rowCount = max(count($oldVariantIds), 1);
@endphp

@section('title', $isAdminUser ? 'Tạo phiếu kho' : 'Tạo đề xuất kho')

@push('styles')
<style>
.wt-page{background:#f5f7fb;color:#172033;min-height:100vh;padding:20px 24px 70px}.wt-inner{max-width:1420px;margin:0 auto}
.wt-toolbar{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:14px}.wt-title h4{color:#111827;font-size:24px;font-weight:900;line-height:1.2;margin:0 0 6px}.wt-title p{color:#667085;font-size:14px;margin:0}.wt-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}
.wt-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;cursor:pointer;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}.wt-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}.wt-btn.dark{background:#111827;border-color:#111827;color:#fff}.wt-btn.danger{background:#fee2e2;border-color:#fecaca;color:#991b1b}.wt-btn:hover{filter:brightness(.98);color:inherit}.wt-btn.primary:hover,.wt-btn.dark:hover{color:#fff}
.wt-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:visible}.wt-card-head{align-items:center;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 15px}.wt-card-head h6{color:#111827;font-size:16px;font-weight:900;margin:0}.wt-card-head small{color:#667085;font-size:12px;font-weight:800}
.wt-form{padding:15px}.wt-form-grid{display:grid;gap:12px;grid-template-columns:repeat(3,minmax(0,1fr))}.wt-field label{color:#667085;display:block;font-size:11px;font-weight:900;margin-bottom:5px;text-transform:uppercase}.wt-input,.wt-select{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:700;min-height:38px;padding:0 10px;width:100%}.wt-input:focus,.wt-select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12);outline:none}.wt-help{color:#667085;font-size:12px;line-height:1.4;margin-top:6px}.wt-error{color:#dc2626;font-size:12px;font-weight:800;margin-top:5px}
.wt-table-wrap{overflow:visible}.wt-table{border-collapse:collapse;min-width:1080px;width:100%}.wt-table th{background:#fff;border-bottom:1px solid #e4e7ec;color:#667085;font-size:11px;font-weight:900;letter-spacing:0;padding:10px 11px;text-align:left;text-transform:uppercase;white-space:nowrap}.wt-table td{border-bottom:1px solid #f1f5f9;color:#344054;font-size:13px;padding:10px 11px;vertical-align:top}.wt-table th:first-child,.wt-table td:first-child{min-width:520px;width:52%}.wt-number{max-width:155px}.wt-picker{position:relative}.wt-picker-menu{background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 12px 30px rgba(15,23,42,.14);display:none;left:0;max-height:330px;overflow-y:auto;position:absolute;right:0;top:calc(100% + 6px);z-index:1000}.wt-picker-menu.is-open{display:block}.wt-option{align-items:center;background:#fff;border:0;border-bottom:1px solid #eef2f7;cursor:pointer;display:flex;gap:10px;padding:10px;text-align:left;width:100%}.wt-option:hover{background:#eff6ff}.wt-option img{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;flex:0 0 46px;height:46px;object-fit:cover;width:46px}.wt-option strong{color:#111827;display:block;font-size:13px;line-height:1.35}.wt-option span{color:#6b7280;display:block;font-size:12px;margin-top:3px}.wt-selected{color:#2563eb;font-size:12px;font-weight:900;line-height:1.35;margin-top:7px;min-height:18px}.wt-empty{color:#667085;font-size:13px;padding:12px}.wt-stock{color:#111827;font-weight:900;padding-top:10px;white-space:nowrap}
@media(max-width:900px){.wt-page{padding:14px}.wt-toolbar,.wt-card-head{align-items:flex-start;flex-direction:column}.wt-actions,.wt-btn{width:100%}.wt-form-grid{grid-template-columns:1fr}.wt-card{overflow:hidden}.wt-table-wrap{overflow-x:auto;overflow-y:visible}.wt-table{min-width:980px}}
</style>
@endpush

@section('content')
<div class="wt-page">
    <div class="wt-inner">
        <div class="wt-toolbar">
            <div class="wt-title">
                <h4>{{ $isAdminUser ? 'Tạo phiếu kho' : 'Tạo đề xuất kho' }}</h4>
                <p>{{ $isAdminUser ? 'Quản trị viên lập phiếu nhập hoặc xuất kho và tồn kho sẽ cập nhật ngay sau khi lưu.' : 'Nhân viên lập đề xuất nhập hoặc xuất kho, admin duyệt thì tồn kho mới thay đổi.' }}</p>
            </div>
            <div class="wt-actions">
                <a href="{{ route('admin.warehouses.index') }}" class="wt-btn"><i class="fa fa-arrow-left"></i> Quay lại kho</a>
            </div>
        </div>

        <form method="post" action="{{ route('admin.warehouses.store-transaction') }}" class="wt-card" id="stockForm">
            @csrf
            <div class="wt-card-head">
                <div>
                    <h6>Thông tin phiếu</h6>
                    <small>{{ $isAdminUser ? 'Phiếu sau khi lưu sẽ hoàn tất và cập nhật tồn kho ngay.' : 'Phiếu sau khi lưu sẽ ở trạng thái chờ admin duyệt.' }}</small>
                </div>
                <button type="submit" class="wt-btn primary"><i class="fa fa-save"></i> {{ $isAdminUser ? 'Lưu phiếu kho' : 'Gửi đề xuất kho' }}</button>
            </div>

            <div class="wt-form">
                @if ($viewErrors->any())
                    <div class="alert alert-danger">
                        {{ $viewErrors->first() }}
                    </div>
                @endif

                <div class="wt-form-grid">
                    <div class="wt-field">
                        <label>Loại phiếu</label>
                        <select name="type" id="stockType" class="wt-select">
                            <option value="IMPORT" @selected(old('type', 'IMPORT') === 'IMPORT')>Nhập kho</option>
                            <option value="EXPORT" @selected(old('type') === 'EXPORT')>Xuất kho</option>
                        </select>
                        <div class="wt-help">{{ $isAdminUser ? 'Tồn kho sẽ thay đổi ngay sau khi lưu phiếu.' : 'Tồn kho chỉ thay đổi sau khi admin duyệt đề xuất.' }}</div>
                    </div>
                    <div class="wt-field" id="sourceWarehouseGroup">
                        <label>Kho nguồn</label>
                        <select name="source_warehouse_id" class="wt-select">
                            <option value="">-- Chọn kho nguồn --</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('source_warehouse_id') == $warehouse->id)>
                                    {{ $warehouse->warehouse_code }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($viewErrors->has('source_warehouse_id'))<div class="wt-error">{{ $viewErrors->first('source_warehouse_id') }}</div>@endif
                    </div>
                    <div class="wt-field" id="targetWarehouseGroup">
                        <label>Kho đích</label>
                        <select name="target_warehouse_id" class="wt-select">
                            <option value="">-- Chọn kho đích --</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('target_warehouse_id') == $warehouse->id)>
                                    {{ $warehouse->warehouse_code }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($viewErrors->has('target_warehouse_id'))<div class="wt-error">{{ $viewErrors->first('target_warehouse_id') }}</div>@endif
                    </div>
                    <div class="wt-field">
                        <label>Mã phiếu</label>
                        <input type="text" name="transaction_code" value="{{ old('transaction_code') }}" class="wt-input" placeholder="Bỏ trống để tự tạo">
                    </div>
                    <div class="wt-field">
                        <label>Ngày dự kiến</label>
                        <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="wt-input">
                    </div>
                    <div class="wt-field">
                        <label>Ghi chú</label>
                        <input type="text" name="note" value="{{ old('note') }}" maxlength="1000" class="wt-input" placeholder="Ví dụ: nhập lô kính mới">
                    </div>
                </div>
            </div>

            <div class="wt-card-head">
                <div>
                    <h6>Sản phẩm trong phiếu</h6>
                    <small>Chọn biến thể sản phẩm rồi nhập số lượng cần cập nhật.</small>
                </div>
                <button type="button" class="wt-btn dark" id="addStockRow"><i class="fa fa-plus"></i> Thêm dòng</button>
            </div>

            <div class="wt-table-wrap">
                <table class="wt-table" id="stockItemsTable">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Tồn khả dụng</th>
                            <th>Số lượng</th>
                            <th>Đơn giá nhập</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $rowCount; $i++)
                            <tr>
                                <td>
                                    <div class="wt-picker">
                                        <input type="text" class="wt-input variant-search" placeholder="Gõ tên kính, mã sản phẩm, màu hoặc size..." autocomplete="off">
                                        <input type="hidden" name="variant_id[]" class="variant-id" value="{{ $oldVariantIds[$i] ?? '' }}">
                                        <div class="wt-picker-menu"></div>
                                        <div class="wt-selected">Chưa chọn sản phẩm</div>
                                    </div>
                                </td>
                                <td class="wt-stock available-cell">0</td>
                                <td><input type="number" name="quantity[]" min="1" value="{{ $oldQuantities[$i] ?? 1 }}" class="wt-input wt-number" required></td>
                                <td><input type="number" name="unit_cost[]" min="0" step="1000" value="{{ $oldUnitCosts[$i] ?? '' }}" class="wt-input wt-number" placeholder="Có thể bỏ trống"></td>
                                <td><button type="button" class="wt-btn danger remove-row">Xóa</button></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<template id="stockRowTemplate">
    <tr>
        <td>
            <div class="wt-picker">
                <input type="text" class="wt-input variant-search" placeholder="Gõ tên kính, mã sản phẩm, màu hoặc size..." autocomplete="off">
                <input type="hidden" name="variant_id[]" class="variant-id">
                <div class="wt-picker-menu"></div>
                <div class="wt-selected">Chưa chọn sản phẩm</div>
            </div>
        </td>
        <td class="wt-stock available-cell">0</td>
        <td><input type="number" name="quantity[]" min="1" value="1" class="wt-input wt-number" required></td>
        <td><input type="number" name="unit_cost[]" min="0" step="1000" class="wt-input wt-number" placeholder="Có thể bỏ trống"></td>
        <td><button type="button" class="wt-btn danger remove-row">Xóa</button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
const stockVariants = @json($variants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function syncWarehouseFields() {
    const type = document.getElementById('stockType').value;
    document.getElementById('sourceWarehouseGroup').style.display = type === 'EXPORT' ? '' : 'none';
    document.getElementById('targetWarehouseGroup').style.display = type === 'IMPORT' ? '' : 'none';
}

function normalizeSearch(value) {
    return String(value || '').toLocaleLowerCase('vi-VN').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (char) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
}

function closeVariantMenus() {
    document.querySelectorAll('.wt-picker-menu.is-open').forEach(function (menu) {
        menu.classList.remove('is-open');
    });
}

function renderVariantMenu(picker, keyword) {
    const menu = picker.querySelector('.wt-picker-menu');
    const query = normalizeSearch(keyword);
    const matched = stockVariants
        .filter(function (item) {
            return !query || normalizeSearch(item.label + ' ' + item.meta).includes(query);
        })
        .slice(0, 35);

    if (!matched.length) {
        menu.innerHTML = '<div class="wt-empty">Không tìm thấy sản phẩm phù hợp</div>';
        menu.classList.add('is-open');
        return;
    }

    menu.innerHTML = matched.map(function (item) {
        return '<button type="button" class="wt-option" data-id="' + item.id + '">' +
            '<img src="' + escapeHtml(item.image) + '" alt="">' +
            '<div><strong>' + escapeHtml(item.label) + '</strong>' +
            '<span>' + escapeHtml(item.meta) + ' | Tồn: ' + Number(item.stock).toLocaleString('vi-VN') + ' | Giá bán: ' + Number(item.salePrice).toLocaleString('vi-VN') + 'đ</span></div>' +
            '</button>';
    }).join('');
    menu.classList.add('is-open');
}

function selectVariant(picker, variant) {
    const row = picker.closest('tr');
    picker.querySelector('.variant-search').value = variant.label;
    picker.querySelector('.variant-id').value = variant.id;
    picker.querySelector('.wt-selected').textContent = variant.meta;
    row.querySelector('.available-cell').textContent = Number(variant.stock).toLocaleString('vi-VN');

    const unitCost = row.querySelector('input[name="unit_cost[]"]');
    if (unitCost && !unitCost.value && Number(variant.price) > 0) {
        unitCost.value = Math.round(Number(variant.price));
    }

    closeVariantMenus();
}

function hydrateOldRows() {
    document.querySelectorAll('#stockItemsTable tbody tr').forEach(function (row) {
        const id = Number(row.querySelector('.variant-id').value || 0);
        if (!id) return;
        const variant = stockVariants.find(function (item) { return Number(item.id) === id; });
        if (variant) {
            selectVariant(row.querySelector('.wt-picker'), variant);
        }
    });
}

document.getElementById('stockType').addEventListener('change', syncWarehouseFields);

document.getElementById('addStockRow').addEventListener('click', function () {
    const clone = document.getElementById('stockRowTemplate').content.cloneNode(true);
    const tbody = document.querySelector('#stockItemsTable tbody');
    tbody.appendChild(clone);
    syncWarehouseFields();
    const search = tbody.querySelector('tr:last-child .variant-search');
    search.focus();
    renderVariantMenu(search.closest('.wt-picker'), '');
});

document.getElementById('stockItemsTable').addEventListener('input', function (event) {
    if (!event.target.classList.contains('variant-search')) return;
    const picker = event.target.closest('.wt-picker');
    picker.querySelector('.variant-id').value = '';
    picker.querySelector('.wt-selected').textContent = 'Chưa chọn sản phẩm';
    picker.closest('tr').querySelector('.available-cell').textContent = '0';
    renderVariantMenu(picker, event.target.value);
});

document.getElementById('stockItemsTable').addEventListener('focusin', function (event) {
    if (event.target.classList.contains('variant-search')) {
        renderVariantMenu(event.target.closest('.wt-picker'), event.target.value);
    }
});

document.getElementById('stockItemsTable').addEventListener('click', function (event) {
    const option = event.target.closest('.wt-option');
    if (option) {
        const variant = stockVariants.find(function (item) {
            return Number(item.id) === Number(option.dataset.id);
        });
        if (variant) {
            selectVariant(option.closest('.wt-picker'), variant);
        }
        return;
    }

    if (event.target.classList.contains('remove-row')) {
        const rows = document.querySelectorAll('#stockItemsTable tbody tr');
        if (rows.length <= 1) {
            alert('Phiếu kho cần ít nhất 1 dòng sản phẩm');
            return;
        }
        event.target.closest('tr').remove();
    }
});

document.addEventListener('click', function (event) {
    if (!event.target.closest('.wt-picker')) {
        closeVariantMenus();
    }
});

document.getElementById('stockForm').addEventListener('submit', function (event) {
    const type = document.getElementById('stockType').value;
    const source = document.querySelector('select[name="source_warehouse_id"]').value;
    const target = document.querySelector('select[name="target_warehouse_id"]').value;
    if (type === 'EXPORT' && !source) {
        event.preventDefault();
        alert('Bạn cần chọn kho nguồn.');
        return;
    }
    if (type === 'IMPORT' && !target) {
        event.preventDefault();
        alert('Bạn cần chọn kho đích.');
        return;
    }
    const emptyRow = Array.from(document.querySelectorAll('.variant-id')).find(function (input) {
        return !input.value;
    });
    if (emptyRow) {
        event.preventDefault();
        alert('Bạn cần chọn sản phẩm từ danh sách gợi ý trước khi lưu phiếu kho.');
        emptyRow.closest('.wt-picker').querySelector('.variant-search').focus();
    }
});

syncWarehouseFields();
hydrateOldRows();
</script>
@endpush
