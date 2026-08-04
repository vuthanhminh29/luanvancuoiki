@extends('layouts.app')

@section('title', 'Tạo yêu cầu hoàn đổi - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/return-request.css') }}?v={{ filemtime(public_path('css/views/return-request.css')) }}">
@endpush

@section('content')
<div class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__links">
                    <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                    <a href="{{ route('account.orders.show', $order) }}">Chi tiết đơn hàng</a>
                    <span>Yêu cầu hoàn/đổi</span>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $items = collect($returnableItems ?? $order->items);
    $remaining = collect($remainingQuantities ?? []);
    $firstItem = $items->first();
    $selectedItemId = (int) old('order_item_id', $selectedOrderItemId ?? ($firstItem?->id ?? 0));
    $selectedItem = $items->firstWhere('id', $selectedItemId) ?? $firstItem;
    $firstMax = $selectedItem ? max(1, (int) ($remaining[$selectedItem->id] ?? $selectedItem->quantity)) : 1;
@endphp

<section class="view-return-request-inline-2">
    <div class="container view-return-request-inline-3">
        @if ($selectedItem)
            <div class="view-return-request-inline-4" data-return-item-card>
                <div class="view-return-request-inline-5">
                    <img src="{{ $selectedItem->product->image_url ?? asset('upload/no-image.jpg') }}" alt="" class="view-return-request-inline-13" data-return-item-image>
                    <div>
                        <h4 class="view-return-request-inline-6" data-return-item-name>{{ $selectedItem->product_name }}</h4>
                        <div class="view-return-request-inline-7">Số lượng đã mua: <span data-return-item-purchased>{{ $selectedItem->quantity }}</span></div>
                        <div class="view-return-request-inline-7">Còn có thể yêu cầu: <span data-return-item-remaining>{{ $firstMax }}</span></div>
                        <div class="view-return-request-inline-8" data-return-item-price>{{ number_format($selectedItem->unit_price, 0, ',', '.') }}đ</div>
                    </div>
                </div>
            </div>
        @endif

        <form method="post" action="{{ route('returns.store', $order) }}" class="view-return-request-inline-9">
            @csrf
            <div class="form-group">
                <label>Loại yêu cầu</label>
                <select name="type" class="form-control" required>
                    <option value="RETURN" @selected(old('type') === 'RETURN')>Hoàn trả</option>
                    <option value="EXCHANGE" @selected(old('type') === 'EXCHANGE')>Đổi hàng</option>
                </select>
            </div>

            <div class="form-group">
                <label>Sản phẩm</label>
                <select name="order_item_id" class="form-control" data-return-item-select required>
                    @foreach ($items as $item)
                        @php
                            $maxQuantity = max(1, (int) ($remaining[$item->id] ?? $item->quantity));
                        @endphp
                        <option
                            value="{{ $item->id }}"
                            data-max="{{ $maxQuantity }}"
                            data-name="{{ $item->product_name }}"
                            data-purchased="{{ $item->quantity }}"
                            data-price="{{ number_format($item->unit_price, 0, ',', '.') }}đ"
                            data-image="{{ $item->product->image_url ?? asset('upload/no-image.jpg') }}"
                            @selected($selectedItemId === (int) $item->id)
                        >
                            {{ $item->product_name }} - còn có thể yêu cầu {{ $maxQuantity }}/{{ $item->quantity }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Lý do</label>
                <select name="reason_id" class="form-control" required>
                    <option value="">-- Chọn lý do --</option>
                    @foreach ($reasons as $reason)
                        <option value="{{ $reason->id }}" @selected(old('reason_id') == $reason->id)>{{ $reason->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Số lượng</label>
                <input type="number" name="quantity" min="1" max="{{ $firstMax }}" value="{{ old('quantity', 1) }}" class="form-control" data-return-quantity required>
            </div>

            <div class="form-group">
                <label>Mô tả thêm</label>
                <textarea name="reason_detail" rows="4" maxlength="1000" class="form-control" placeholder="Mô tả tình trạng sản phẩm hoặc mong muốn đổi hàng...">{{ old('reason_detail') }}</textarea>
            </div>

            <div class="form-group">
                <label>Tình trạng sản phẩm</label>
                <textarea name="condition_note" rows="3" maxlength="500" class="form-control" placeholder="Ví dụ: còn tem, còn hộp, bị trầy xước...">{{ old('condition_note') }}</textarea>
            </div>

            <div class="view-return-request-inline-12">
                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-light view-return-request-inline-14">Quay lại</a>
                <button type="submit" class="btn btn-dark">Gửi yêu cầu</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var itemSelect = document.querySelector('[data-return-item-select]');
    var quantityInput = document.querySelector('[data-return-quantity]');
    var itemImage = document.querySelector('[data-return-item-image]');
    var itemName = document.querySelector('[data-return-item-name]');
    var itemPurchased = document.querySelector('[data-return-item-purchased]');
    var itemRemaining = document.querySelector('[data-return-item-remaining]');
    var itemPrice = document.querySelector('[data-return-item-price]');

    function syncReturnItem() {
        if (!itemSelect || !quantityInput) {
            return;
        }

        var selected = itemSelect.options[itemSelect.selectedIndex];
        var max = parseInt(selected ? selected.dataset.max : quantityInput.max, 10) || 1;
        quantityInput.max = String(max);

        if (parseInt(quantityInput.value, 10) > max) {
            quantityInput.value = String(max);
        }

        if (selected) {
            if (itemImage) {
                itemImage.src = selected.dataset.image || itemImage.src;
            }
            if (itemName) {
                itemName.textContent = selected.dataset.name || '';
            }
            if (itemPurchased) {
                itemPurchased.textContent = selected.dataset.purchased || '';
            }
            if (itemRemaining) {
                itemRemaining.textContent = String(max);
            }
            if (itemPrice) {
                itemPrice.textContent = selected.dataset.price || '';
            }
        }
    }

    if (itemSelect) {
        itemSelect.addEventListener('change', syncReturnItem);
        syncReturnItem();
    }
});
</script>
@endpush
