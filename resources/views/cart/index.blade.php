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
        @endphp

        <section class="shop-cart spad">
            <div class="container">
                <form action="{{ route('cart.update') }}" method="post" data-max-total-quantity="20">
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
                                                            <input type="number" readonly step="1" min="0" max="20"
                                                                value="{{ $item['quantity'] }}"
                                                                name="quantities[{{ $variant->id }}]"
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
