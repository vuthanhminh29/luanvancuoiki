@extends('layouts.app')

@section('title', 'Giỏ hàng - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/cart-main.css') }}?v={{ filemtime(public_path('css/views/cart-main.css')) }}">
@endpush

@section('content')
    @php
        $totalPayment = (float) $items->sum('line_total');
        $totalQuantity = (int) $items->sum('quantity');
        $orderMaxQuantity = 10;
        $hasCartErrors = isset($errors) && $errors->any();
        $cartMessage = session('error') ?: session('success') ?: ($hasCartErrors ? $errors->first() : null);
        $formatMoney = fn ($value) => number_format((float) $value, 0, ',', '.') . 'đ';
    @endphp

    <section class="cart-page">
        <div class="cart-container">
            <nav class="cart-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                <span><i class="fa fa-angle-right"></i></span>
                <a href="{{ route('products.index') }}">Cửa hàng</a>
                <span><i class="fa fa-angle-right"></i></span>
                <strong>Giỏ hàng</strong>
            </nav>

            @if ($items->isNotEmpty())
                <header class="cart-heading">
                    <div>
                        <p class="cart-kicker">Đơn hàng của bạn</p>
                        <h1>Giỏ hàng</h1>
                    </div>
                    <div class="cart-count">{{ $totalQuantity }}/{{ $orderMaxQuantity }} sản phẩm</div>
                </header>

                @if ($cartMessage)
                    <div class="cart-message {{ session('error') || $hasCartErrors ? 'is-error' : 'is-success' }}">
                        <i class="fa {{ session('error') || $hasCartErrors ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
                        <span>{{ $cartMessage }}</span>
                    </div>
                @endif

                <div class="cart-limit-note">
                    <i class="fa fa-info-circle"></i>
                    <span>
                        Mỗi đơn tối đa {{ $orderMaxQuantity }} sản phẩm. Nếu cần đặt số lượng lớn hơn, vui lòng
                        <a href="{{ route('pages.contact') }}">liên hệ cửa hàng</a>.
                    </span>
                </div>

                <div class="cart-layout">
                    <div class="cart-main-panel">
                        <form action="{{ route('cart.update') }}" method="post" id="cart-update-form" data-order-max-quantity="{{ $orderMaxQuantity }}" novalidate>
                            @csrf
                            @method('PUT')

                            <div class="cart-table-head">
                                <span>Sản phẩm</span>
                                <span>Đơn giá</span>
                                <span>Số lượng</span>
                                <span>Tạm tính</span>
                                <span></span>
                            </div>

                            <div class="cart-items">
                                @foreach ($items as $item)
                                    @php
                                        $variant = $item['variant'];
                                        $product = $variant->product;
                                    @endphp

                                    <article class="cart-item">
                                        <div class="cart-product">
                                            <a class="cart-product-image" href="{{ route('products.show', $product) }}">
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                            </a>
                                            <div class="cart-product-copy">
                                                <a class="cart-product-name" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                            </div>
                                        </div>

                                        <div class="cart-price" data-label="Đơn giá">{{ $formatMoney($variant->display_price) }}</div>

                                        <div class="cart-quantity" data-label="Số lượng">
                                            <div class="input-next-cart">
                                                <button type="button" class="button-minus" data-field="quantity" aria-label="Giảm số lượng">-</button>
                                                <input type="number"
                                                    step="1"
                                                    min="1"
                                                    max="{{ $orderMaxQuantity }}"
                                                    value="{{ $item['quantity'] }}"
                                                    name="quantities[{{ $variant->id }}]"
                                                    data-product="{{ $product->name }}"
                                                    data-original-value="{{ $item['quantity'] }}"
                                                    class="quantity-field-cart">
                                                <button type="button" class="button-plus" data-field="quantity" aria-label="Tăng số lượng">+</button>
                                            </div>
                                        </div>

                                        <div class="cart-line-total" data-label="Tạm tính">{{ $formatMoney($item['line_total']) }}</div>

                                        <div class="cart-remove">
                                            <button form="remove-{{ $variant->id }}" type="submit" title="Xóa sản phẩm"
                                                onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="cart-actions">
                                <a class="cart-btn light" href="{{ route('products.index') }}">
                                    <i class="fa fa-angle-left"></i> Tiếp tục mua sắm
                                </a>
                                <button class="cart-btn dark" name="update_cart" type="submit">
                                    <i class="fa fa-sync-alt"></i> Cập nhật giỏ hàng
                                </button>
                            </div>
                        </form>
                    </div>

                    <aside class="cart-summary" aria-label="Tóm tắt giỏ hàng">
                        <div class="cart-summary-head">
                            <h2>Tổng tiền</h2>
                            <span>{{ $totalQuantity }} sản phẩm</span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Số lượng</span>
                            <strong>{{ $totalQuantity }}/{{ $orderMaxQuantity }}</strong>
                        </div>
                        <div class="cart-summary-row">
                            <span>Tạm tính</span>
                            <strong>{{ $formatMoney($totalPayment) }}</strong>
                        </div>
                        <div class="cart-summary-row muted">
                            <span>Phí vận chuyển</span>
                            <strong>Tính ở bước thanh toán</strong>
                        </div>
                        <div class="cart-summary-divider"></div>
                        <div class="cart-summary-total">
                            <span>Tổng</span>
                            <strong>{{ $formatMoney($totalPayment) }}</strong>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="cart-checkout-btn" id="cart-checkout-link">
                            Thanh toán
                        </a>
                        <p class="cart-safe-note">
                            <i class="fa fa-lock"></i> Kiểm tra giỏ hàng và thông tin giao hàng trước khi tạo đơn.
                        </p>
                    </aside>
                </div>

                @foreach ($items as $item)
                    <form id="remove-{{ $item['variant']->id }}" method="post" action="{{ route('cart.destroy', $item['variant']->id) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @else
                <div class="cart-empty">
                    <div class="cart-empty-icon"><i class="fa fa-shopping-bag"></i></div>
                    <h1>Giỏ hàng đang trống</h1>
                    <p>Chọn một mẫu kính bạn thích, thử kính nếu cần, rồi quay lại đây để hoàn tất đơn hàng.</p>
                    <div class="cart-empty-actions">
                        <a class="cart-btn dark" href="{{ route('products.index') }}">Xem sản phẩm</a>
                        <a class="cart-btn light" href="{{ route('home') }}">Trang chủ</a>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cartUpdateForm = document.getElementById('cart-update-form');
            const checkoutLink = document.getElementById('cart-checkout-link');
            let cartAlertOpen = false;

            if (!cartUpdateForm) {
                return;
            }

            function showCartAlert(message, focusElement = null) {
                if (cartAlertOpen) {
                    return;
                }

                cartAlertOpen = true;
                alert(message);
                cartAlertOpen = false;

                if (focusElement) {
                    focusElement.focus();
                }
            }

            function validateCartQuantities(event) {
                const maxQuantity = parseInt(cartUpdateForm.dataset.orderMaxQuantity || '10', 10);
                let totalQuantity = 0;
                const quantityInputs = cartUpdateForm.querySelectorAll('.quantity-field-cart');

                for (const input of quantityInputs) {
                    const quantity = Math.max(0, parseInt(input.value || '0', 10) || 0);

                    totalQuantity += quantity;
                }

                if (totalQuantity > maxQuantity) {
                    event.preventDefault();
                    showCartAlert('Mỗi đơn chỉ đặt tối đa ' + maxQuantity + ' sản phẩm. Vui lòng giảm số lượng trong giỏ.');
                    return false;
                }

                return true;
            }

            cartUpdateForm.addEventListener('submit', function(event) {
                validateCartQuantities(event);
            });

            if (checkoutLink) {
                checkoutLink.addEventListener('click', function(event) {
                    if (!validateCartQuantities(event)) {
                        return;
                    }

                    const changedInput = cartUpdateForm.querySelector('.quantity-field-cart');
                    const hasUnsavedChanges = Array.from(cartUpdateForm.querySelectorAll('.quantity-field-cart')).some(function(input) {
                        return String(parseInt(input.value || '0', 10) || 0) !== String(parseInt(input.dataset.originalValue || '0', 10) || 0);
                    });

                    if (hasUnsavedChanges) {
                        event.preventDefault();
                        showCartAlert('Bạn vừa thay đổi số lượng. Vui lòng bấm Cập nhật giỏ hàng trước khi thanh toán.', changedInput);
                    }
                });
            }
        });
    </script>
@endpush
