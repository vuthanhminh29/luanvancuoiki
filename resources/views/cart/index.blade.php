@extends('layouts.app')

@section('title', 'Giỏ hàng - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/cart-main.css') }}?v={{ filemtime(public_path('css/views/cart-main.css')) }}">
@endpush

@section('content')
    <div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                        <a href="{{ route('products.index') }}"> Cửa hàng</a>
                        <span>Giỏ hàng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($items->isNotEmpty())
        @php
            $totalPayment = $items->sum('line_total');
            $totalQuantity = $items->sum('quantity');
            $orderMaxQuantity = 10;
        @endphp

        <section class="shop-cart spad">
            <div class="container">
                <div class="cart-page-head">
                    <h1>Giỏ hàng</h1>
                    <div class="cart-count">{{ $totalQuantity }}/{{ $orderMaxQuantity }} sản phẩm</div>
                </div>

                <div class="cart-bulk-note">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <span>
                        Nếu khách hàng muốn đặt trên {{ $orderMaxQuantity }} cái, vui lòng bấm sang
                        <a href="{{ route('pages.contact') }}">Liên hệ</a> để liên hệ với shop.
                    </span>
                </div>

                <form action="{{ route('cart.update') }}" method="post" id="cart-update-form" data-order-max-quantity="{{ $orderMaxQuantity }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="shop__cart__table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Giá</th>
                                            <th>Số lượng</th>
                                            <th>Tổng</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            @php
                                                $variant = $item['variant'];
                                                $product = $variant->product;
                                            @endphp
                                            <tr>
                                                <td class="cart__product__item">
                                                    <a href="{{ route('products.show', $product) }}">
                                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                                    </a>
                                                    <div class="cart__product__item__title">
                                                        <h6 class="text-truncate-1">
                                                            <a href="{{ route('products.show', $product) }}" class="text-dark">
                                                                {{ $product->name }}
                                                            </a>
                                                        </h6>
                                                        <div class="rating">
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                            <i class="fa fa-star"></i>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="cart__price">{{ number_format($variant->display_price, 0, ',', '.') }}₫</td>
                                                <td class="cart__quantity">
                                                    <div class="input-group float-left">
                                                        <div class="input-next-cart d-flex">
                                                            <input type="button" value="-" class="button-minus" data-field="quantity">
                                                            <input type="number" step="1" min="1" max="{{ max(1, min((int) $item['available_stock'], $orderMaxQuantity)) }}"
                                                                value="{{ $item['quantity'] }}"
                                                                name="quantities[{{ $variant->id }}]"
                                                                data-stock="{{ $item['available_stock'] }}"
                                                                data-product="{{ $product->name }}"
                                                                data-original-value="{{ $item['quantity'] }}"
                                                                class="quantity-field-cart">
                                                            <input type="button" value="+" class="button-plus" data-field="quantity">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="cart__total">{{ number_format($item['line_total'], 0, ',', '.') }}₫</td>
                                                <td class="cart__close">
                                                    <button form="remove-{{ $variant->id }}" type="submit"
                                                        onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                                        <span class="icon_close"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="cart__btn">
                                <a href="{{ route('products.index') }}">Tiếp tục mua sắm</a>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="cart__btn update__btn">
                                <button name="update_cart" type="submit">
                                    <span class="icon_loading"></span> Cập nhật giỏ hàng
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-lg-6"></div>
                    <div class="col-lg-4 offset-lg-2">
                        <div class="cart__total__procced">
                            <h6>Tổng tiền</h6>
                            <ul>
                                <li>Số lượng <span>{{ $totalQuantity }}/20 sản phẩm</span></li>
                                <li>Tổng <span>{{ number_format($totalPayment, 0, ',', '.') }}₫</span></li>
                            </ul>
                            <a href="{{ route('checkout.index') }}" class="primary-btn">Thanh toán</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @foreach ($items as $item)
            <form id="remove-{{ $item['variant']->id }}" method="post" action="{{ route('cart.destroy', $item['variant']->id) }}">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
@else
        <div class="empty-cart-container">
            <div class="container">
                <div class="row rounded justify-content-center mx-0">
                    <div class="col-md-6 text-center">
                        <h4>Chưa có sản phẩm nào trong giỏ hàng</h4>
                        <a class="btn btn-primary" href="{{ route('products.index') }}">Xem sản phẩm</a>
                        <a class="btn btn-secondary" href="{{ route('home') }}">Trang chủ</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
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
                    const stock = Math.max(0, parseInt(input.dataset.stock || '0', 10) || 0);

                    totalQuantity += quantity;

                    if (quantity > stock) {
                        event.preventDefault();
                        input.value = Math.max(1, stock);
                        showCartAlert('Sản phẩm này chỉ còn ' + stock + ' sản phẩm trong kho. Vui lòng giảm số lượng.', input);
                        return false;
                    }
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
