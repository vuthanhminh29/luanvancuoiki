@extends('layouts.app')

@section('title', 'Thanh toán - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/checkout.css') }}?v={{ filemtime(public_path('css/views/checkout.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/views/checkout/checkout.css') }}?v={{ filemtime(public_path('css/views/checkout/checkout.css')) }}">
@endpush

@section('content')
    @php
        $subtotalPayment = (float) $items->sum('line_total');
        $discountAmount = (float) ($discountAmount ?? 0);
        $totalPayment = max(0, $subtotalPayment - $discountAmount);
        $totalQuantity = $items->sum('quantity');
        $appliedPromotion = $appliedPromotion ?? null;
        $promotionExplanation = null;
        if ($appliedPromotion) {
            $formatPromotionNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
            $discountText = $appliedPromotion->discount_type === 'PERCENT'
                ? $formatPromotionNumber($appliedPromotion->discount_value) . '%'
                : number_format((float) $appliedPromotion->discount_value, 0, ',', '.') . 'đ';
            $conditionText = (float) $appliedPromotion->min_order_amount > 0
                ? ' cho đơn hàng từ ' . number_format((float) $appliedPromotion->min_order_amount, 0, ',', '.') . 'đ'
                : ' cho đơn hàng này';
            $maxDiscountText = (float) ($appliedPromotion->max_discount_amount ?? 0) > 0
                ? ' Tối đa ' . number_format((float) $appliedPromotion->max_discount_amount, 0, ',', '.') . 'đ.'
                : '';
            $promotionExplanation = 'Giảm ' . $discountText . $conditionText . '.' . $maxDiscountText;
        }
        $user = auth()->user();
        $defaultAddress = $defaultAddress ?? null;
        $fullName = old('recipient_name', $defaultAddress->recipient_name ?? $user->full_name ?? '');
        $phone = old('recipient_phone', $defaultAddress->phone ?? $user->phone ?? '');
        $addressDetail = old('address_detail', collect([
            $defaultAddress->address_detail ?? null,
            $defaultAddress->ward_name ?? null,
            $defaultAddress->district_name ?? null,
        ])->filter()->implode(', '));
        $city = old('city', $defaultAddress->province_name ?? '');
        $shippingAddress = trim((string) old('shipping_address', collect([$addressDetail, $city])->filter()->implode(', ')));
        $paymentMethod = in_array(old('payment_method', 'COD'), ['COD', 'VNPAY'], true) ? old('payment_method', 'COD') : 'COD';
        $cities = [
            'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
            'An Giang', 'Bà Rịa Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
            'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước',
            'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông',
            'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang',
            'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 'Hậu Giang', 'Hòa Bình',
            'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu',
            'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định',
            'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên',
            'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị',
            'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên',
            'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang',
            'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
        ];
    @endphp

    <div class="breadcrumb-option">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                        <span>Thanh toán</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="checkout-section">
        <div class="container">
            <div class="checkout-header">
                <div class="checkout-header-content">
                    <h3>Thanh Toán Đơn Hàng</h3>
                    <a href="{{ route('cart.index') }}" class="back-to-cart">
                        <i class="fa fa-angle-left"></i> Quay lại giỏ hàng
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="address-notice">
                    <i class="fa fa-info-circle"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('checkout.store') }}" method="post" class="checkout-form" id="checkout-form" novalidate>
                @csrf
                <input type="hidden" name="recipient_name" value="{{ $fullName }}">
                <input type="hidden" name="shipping_address" id="shipping-address" value="{{ $shippingAddress }}">

                <div class="row">
                    <div class="col-lg-7">
                        <div class="checkout-card">
                            <div class="checkout-card-header">
                                <h5>Thông Tin Giao Hàng</h5>
                            </div>
                            <div class="checkout-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Họ và tên <span class="required">*</span></label>
                                            <input type="text" class="form-control" disabled name="full_name"
                                                value="{{ $fullName }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email <span class="required">*</span></label>
                                            <input type="text" class="form-control" disabled
                                                value="{{ $user->email ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Địa chỉ chi tiết <span class="required">*</span></label>
                                            <input type="text"
                                                class="form-control @error('address_detail') is-invalid @enderror"
                                                name="address_detail" id="address-detail"
                                                value="{{ old('address_detail', $addressDetail) }}"
                                                placeholder="Số nhà, đường, phường/xã, quận/huyện...">
                                            @error('address_detail')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Tỉnh/Thành phố <span class="required">*</span></label>
                                            <select class="form-control @error('city') is-invalid @enderror" name="city" id="city">
                                                <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                                @foreach ($cities as $cityItem)
                                                    <option value="{{ $cityItem }}" @selected($city === $cityItem)>{{ $cityItem }}</option>
                                                @endforeach
                                            </select>
                                            @error('city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Số điện thoại <span class="required">*</span></label>
                                            <input type="text"
                                                class="form-control @error('recipient_phone') is-invalid @enderror"
                                                name="recipient_phone" value="{{ $phone }}"
                                                placeholder="Nhập số điện thoại">
                                            @error('recipient_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Ghi chú đơn hàng</label>
                                            <textarea class="form-control" name="note" rows="3"
                                                placeholder="Ghi chú về đơn hàng (tùy chọn)">{{ old('note') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="address-options">
                                    <div class="address-notice">
                                        <i class="fa fa-info-circle"></i>
                                        <span>Bạn có thể sử dụng địa chỉ mặc định hoặc chọn địa chỉ khác</span>
                                    </div>
                                    <div class="address-buttons">
                                        <a href="{{ route('checkout.index') }}" class="btn-address">
                                            <i class="fa fa-map-marker"></i> Địa chỉ mới
                                        </a>
                                        <a href="{{ route('account.index') }}" class="btn-address">
                                            <i class="fa fa-home"></i> Sử dụng địa chỉ 2
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($items->isNotEmpty())
                            <div class="checkout-choice-section">
                                <h5>Phương thức vận chuyển</h5>
                                <div class="checkout-choice-card active">
                                    <div>
                                        <strong>Giao hàng tiêu chuẩn (3 đến 7 ngày)</strong>
                                        <span>Nhập địa chỉ để cửa hàng sắp xếp giao hàng.</span>
                                    </div>
                                    <b>MIỄN PHÍ</b>
                                </div>
                            </div>

                            <div class="checkout-choice-section">
                                <h5>Thanh toán</h5>
                                <p>Toàn bộ các giao dịch được bảo mật và mã hóa.</p>
                                <label class="checkout-choice-card payment-choice-card payment-option-card {{ $paymentMethod === 'VNPAY' ? 'active' : '' }}" data-payment-card>
                                    <input type="radio" name="payment_method" value="VNPAY" @checked($paymentMethod === 'VNPAY')>
                                    <div>
                                        <strong>Thanh toán online qua VNPay</strong>
                                        <span>Chuyển sang VNPay sandbox để thanh toán bằng thẻ test hoặc QR ngân hàng.</span>
                                    </div>
                                    <i class="fa fa-credit-card"></i>
                                </label>
                                <label class="checkout-choice-card payment-choice-card payment-option-card {{ $paymentMethod === 'COD' ? 'active' : '' }}" data-payment-card>
                                    <input type="radio" name="payment_method" value="COD" @checked($paymentMethod === 'COD')>
                                    <div>
                                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                                        <span>Bạn chỉ thanh toán khi đã nhận được đơn hàng.</span>
                                    </div>
                                    <i class="fa fa-money"></i>
                                </label>
                            </div>

                            <button type="button" class="btn-place-order checkout-action-button" data-toggle="modal" data-target="#thanh-toan-1">
                                Thanh toán ngay
                            </button>
                        @endif
                    </div>

                    <div class="col-lg-5">
                        <div class="order-summary">
                            <div class="order-summary-header">
                                <h5>Đơn Hàng Của Bạn</h5>
                                <span class="item-count">{{ $totalQuantity }} sản phẩm</span>
                            </div>

                            <div class="order-items">
                                @forelse ($items as $item)
                                    @php
                                        $variant = $item['variant'];
                                        $product = $variant->product;
                                        $lensOption = $item['lens_option'] ?? null;
                                    @endphp
                                    <div class="order-item">
                                        <div class="order-item-media">
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                            <span>{{ $item['quantity'] }}</span>
                                        </div>
                                        <div class="order-item-info">
                                            <div class="order-item-name">{{ $product->name }}</div>
                                            <div class="order-item-meta" style="font-size: 11px; font-family: var(--font-mono); color: var(--ink-soft); margin-top: 2px;">
                                                @if (!empty($product->frame_size))
                                                    <span>{{ str_replace([' ', '□', '-'], [' ', '▭', '-'], $product->frame_size) }}</span>
                                                @else
                                                    <span>52▭18-145</span>
                                                @endif
                                                @if (!empty($variant->color->name))
                                                    <span> &bull; {{ $variant->color->name }}</span>
                                                @endif
                                            </div>
                                            @if ($lensOption)
                                                <div class="order-item-lens">
                                                    Tròng kính: {{ $lensOption['name'] }} (+{{ number_format((float) $lensOption['price'], 0, ',', '.') }}đ)
                                                </div>
                                            @endif
                                            <div class="order-item-qty">Số lượng: {{ $item['quantity'] }}</div>
                                        </div>
                                        <div class="order-item-price">
                                            {{ number_format($item['line_total'], 0, ',', '.') }}đ
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-cart-notice">
                                        <i class="fa fa-shopping-cart"></i>
                                        <p>Chưa có sản phẩm trong giỏ hàng</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="coupon-helper">
                                <strong>Gợi ý mã giảm giá:</strong>
                                Nhập mã <span>HELLO</span> để giảm 20% cho đơn từ 500.000đ, tối đa 1.000.000đ.
                            </div>
                            <div class="discount-box">
                                <input type="text" name="promotion_code" value="{{ old('promotion_code', $appliedPromotion->promotion_code ?? '') }}" placeholder="Mã giảm giá" maxlength="20" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                                @if ($appliedPromotion)
                                    <button type="submit" formaction="{{ route('checkout.promotion.remove') }}" formmethod="post" formnovalidate>Xóa</button>
                                @else
                                    <button type="submit" formaction="{{ route('checkout.promotion.apply') }}" formmethod="post" formnovalidate>Áp dụng</button>
                                @endif
                            </div>
                            @if ($appliedPromotion)
                                <div class="applied-coupon">
                                    <span>Đã áp dụng {{ $appliedPromotion->promotion_code }}</span>
                                    <strong>-{{ number_format($discountAmount, 0, ',', '.') }}đ</strong>
                                </div>
                            @endif
                            @error('promotion_code')
                                <div class="coupon-message error">{{ $message }}</div>
                            @enderror

                            <div class="order-calculation">
                                <div class="calc-row">
                                    <span>Tạm tính:</span>
                                    <span>{{ number_format($subtotalPayment, 0, ',', '.') }}đ</span>
                                </div>
                                @if ($discountAmount > 0)
                                    <div class="calc-row discount">
                                        <span>Giảm giá:</span>
                                        <span>-{{ number_format($discountAmount, 0, ',', '.') }}đ</span>
                                    </div>
                                @endif
                                <div class="calc-row">
                                    <span>Phí vận chuyển:</span>
                                    <span class="text-success">Miễn phí</span>
                                </div>
                                <div class="calc-divider"></div>
                                <div class="calc-row total">
                                    <span>Tổng cộng:</span>
                                    <span>{{ number_format($totalPayment, 0, ',', '.') }}đ</span>
                                </div>
                            </div>

                            @if ($items->isNotEmpty())
                                <div class="payment-method">
                                    <div class="payment-badge payment-badge-vnpay" id="checkout-payment-summary">
                                        <i class="fa fa-credit-card"></i>
                                        <span>Thanh toán online qua VNPay</span>
                                    </div>
                                </div>

                                <button type="button" class="btn-place-order btn-place-order-cod" data-toggle="modal" data-target="#thanh-toan-1">
                                    Thanh Toán Ngay
                                </button>

                                <div class="order-security">
                                    <i class="fa fa-shield"></i>
                                    <span id="checkout-security-text">Thanh toán online an toàn qua VNPay</span>
                                </div>

                                <div class="modal fade view-checkout-inline-1" id="thanh-toan-1" tabindex="-1" role="dialog"
                                    aria-labelledby="thanh-toan-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content checkout-modal">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Xác Nhận Đặt Hàng</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="confirm-icon confirm-icon-vnpay" id="checkout-confirm-icon">
                                                    <i class="fa fa-credit-card"></i>
                                                </div>
                                                <p id="checkout-confirm-message">Bạn sẽ được chuyển sang VNPay sandbox.</p>
                                                <div class="confirm-total">
                                                    Tổng thanh toán: <strong>{{ number_format($totalPayment, 0, ',', '.') }}đ</strong>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn-modal-cancel" data-dismiss="modal">
                                                    Hủy bỏ
                                                </button>
                                                <button type="submit" name="checkout" class="btn-modal-confirm btn-modal-confirm-vnpay" id="checkout-confirm-submit">
                                                    Xác nhận thanh toán
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('products.index') }}" class="btn-place-order">Xem Sản Phẩm</a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('checkout-form');
            const addressDetail = document.getElementById('address-detail');
            const city = document.getElementById('city');
            const recipientPhone = document.getElementById('recipient-phone');
            const shippingAddress = document.getElementById('shipping-address');

            function syncShippingAddress() {
                const parts = [addressDetail.value.trim(), city.value.trim()].filter(Boolean);
                shippingAddress.value = parts.join(', ');
            }

            function fieldError(name) {
                return document.querySelector('[data-checkout-error="' + name + '"]');
            }

            function setFieldError(input, name, message) {
                const error = fieldError(name);

                if (!input || !error) {
                    return;
                }

                input.classList.toggle('is-invalid', Boolean(message));
                error.textContent = message || '';
            }

            function validateCheckoutForm() {
                let firstInvalid = null;
                const addressValue = addressDetail ? addressDetail.value.trim() : '';
                const cityValue = city ? city.value.trim() : '';
                const phoneValue = recipientPhone ? recipientPhone.value.trim() : '';

                setFieldError(addressDetail, 'address_detail', '');
                setFieldError(city, 'city', '');
                setFieldError(recipientPhone, 'recipient_phone', '');

                if (!addressValue) {
                    setFieldError(addressDetail, 'address_detail', 'Vui lòng nhập địa chỉ chi tiết.');
                    firstInvalid = firstInvalid || addressDetail;
                }

                if (!cityValue) {
                    setFieldError(city, 'city', 'Vui lòng chọn Tỉnh/Thành phố.');
                    firstInvalid = firstInvalid || city;
                }

                if (!phoneValue) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại không được để trống.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (!/^[0-9]+$/.test(phoneValue)) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại chỉ được nhập số.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (phoneValue.length !== 10) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại bắt buộc 10 số.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (!phoneValue.startsWith('0')) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại phải bắt đầu bằng số 0.');
                    firstInvalid = firstInvalid || recipientPhone;
                }

                if (firstInvalid) {
                    hideCheckoutModal();
                    firstInvalid.focus();
                    return false;
                }

                return true;
            }

            function fieldError(name) {
                return document.querySelector('[data-checkout-error="' + name + '"]');
            }

            function setFieldError(input, name, message) {
                const error = fieldError(name);

                if (!input || !error) {
                    return;
                }

                input.classList.toggle('is-invalid', Boolean(message));
                error.textContent = message || '';
            }

            function validateCheckoutForm() {
                let firstInvalid = null;
                const addressValue = addressDetail ? addressDetail.value.trim() : '';
                const cityValue = city ? city.value.trim() : '';
                const phoneValue = recipientPhone ? recipientPhone.value.trim() : '';

                setFieldError(addressDetail, 'address_detail', '');
                setFieldError(city, 'city', '');
                setFieldError(recipientPhone, 'recipient_phone', '');

                if (!addressValue) {
                    setFieldError(addressDetail, 'address_detail', 'Vui lòng nhập địa chỉ chi tiết.');
                    firstInvalid = firstInvalid || addressDetail;
                }

                if (!cityValue) {
                    setFieldError(city, 'city', 'Vui lòng chọn Tỉnh/Thành phố.');
                    firstInvalid = firstInvalid || city;
                }

                if (!phoneValue) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại không được để trống.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (!/^[0-9]+$/.test(phoneValue)) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại chỉ được nhập số.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (phoneValue.length !== 10) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại bắt buộc 10 số.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (!phoneValue.startsWith('0')) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại phải bắt đầu bằng số 0.');
                    firstInvalid = firstInvalid || recipientPhone;
                }

                if (firstInvalid) {
                    hideCheckoutModal();
                    firstInvalid.focus();
                    return false;
                }

                return true;
            }

            function fieldError(name) {
                return document.querySelector('[data-checkout-error="' + name + '"]');
            }

            function setFieldError(input, name, message) {
                const error = fieldError(name);

                if (!input || !error) {
                    return;
                }

                input.classList.toggle('is-invalid', Boolean(message));
                error.textContent = message || '';
            }

            function validateCheckoutForm() {
                let firstInvalid = null;
                const addressValue = addressDetail ? addressDetail.value.trim() : '';
                const cityValue = city ? city.value.trim() : '';
                const phoneValue = recipientPhone ? recipientPhone.value.trim() : '';

                setFieldError(addressDetail, 'address_detail', '');
                setFieldError(city, 'city', '');
                setFieldError(recipientPhone, 'recipient_phone', '');

                if (!addressValue) {
                    setFieldError(addressDetail, 'address_detail', 'Vui lòng nhập địa chỉ chi tiết.');
                    firstInvalid = firstInvalid || addressDetail;
                }

                if (!cityValue) {
                    setFieldError(city, 'city', 'Vui lòng chọn Tỉnh/Thành phố.');
                    firstInvalid = firstInvalid || city;
                }

                if (!phoneValue) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại không được để trống.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (!/^[0-9]+$/.test(phoneValue)) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại chỉ được nhập số.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (phoneValue.length !== 10) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại bắt buộc 10 số.');
                    firstInvalid = firstInvalid || recipientPhone;
                } else if (!phoneValue.startsWith('0')) {
                    setFieldError(recipientPhone, 'recipient_phone', 'Số điện thoại phải bắt đầu bằng số 0.');
                    firstInvalid = firstInvalid || recipientPhone;
                }

                if (firstInvalid) {
                    hideCheckoutModal();
                    firstInvalid.focus();
                    return false;
                }

                return true;
            }

            if (form && addressDetail && city && shippingAddress) {
                addressDetail.addEventListener('input', syncShippingAddress);
                city.addEventListener('change', syncShippingAddress);
                form.addEventListener('submit', syncShippingAddress);
                syncShippingAddress();
            }

            [
                [addressDetail, 'address_detail'],
                [city, 'city'],
                [recipientPhone, 'recipient_phone']
            ].forEach(function(pair) {
                const input = pair[0];
                const name = pair[1];

                if (!input) {
                    return;
                }

                input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', function() {
                    setFieldError(input, name, '');
                });
            });

            const paymentCards = document.querySelectorAll('[data-payment-card]');
            const paymentSummary = document.getElementById('checkout-payment-summary');
            const confirmMessage = document.getElementById('checkout-confirm-message');
            const confirmIcon = document.getElementById('checkout-confirm-icon');
            const securityText = document.getElementById('checkout-security-text');
            const checkoutModal = document.getElementById('thanh-toan-1');
            const confirmSubmit = document.getElementById('checkout-confirm-submit');

            if (checkoutModal && checkoutModal.parentElement !== document.body) {
                if (form && confirmSubmit) {
                    confirmSubmit.setAttribute('form', form.id);
                }

                document.body.appendChild(checkoutModal);
            }

            function cleanupModalState() {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                    backdrop.remove();
                });
            }

            function hideCheckoutModal() {
                if (!checkoutModal) {
                    return;
                }

                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                    window.jQuery(checkoutModal).modal('hide');
                    window.setTimeout(cleanupModalState, 250);
                    return;
                }

                checkoutModal.classList.remove('show');
                checkoutModal.style.display = 'none';
                checkoutModal.setAttribute('aria-hidden', 'true');
                cleanupModalState();
            }

            function showCheckoutModal() {
                if (!checkoutModal || !form) {
                    return;
                }

                syncShippingAddress();

                if (!validateCheckoutForm()) {
                    return;
                }

                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                    window.jQuery(checkoutModal).modal({
                        backdrop: true,
                        keyboard: true,
                        show: true
                    });
                } else {
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show checkout-modal-backdrop';
                    document.body.appendChild(backdrop);
                    document.body.classList.add('modal-open');
                    checkoutModal.classList.add('show');
                    checkoutModal.style.display = 'flex';
                    checkoutModal.removeAttribute('aria-hidden');
                }

                window.setTimeout(function() {
                    if (confirmSubmit) {
                        confirmSubmit.focus();
                    }
                }, 150);
            }

            function syncPaymentUi() {
                const selected = document.querySelector('input[name="payment_method"]:checked');
                const isVnPay = !selected || selected.value === 'VNPAY';

                paymentCards.forEach(function(card) {
                    const input = card.querySelector('input[name="payment_method"]');
                    card.classList.toggle('active', input && input.checked);
                });

                if (paymentSummary) {
                    paymentSummary.innerHTML = isVnPay
                        ? '<i class="fa fa-credit-card"></i><span>Thanh toán online qua VNPay</span>'
                        : '<i class="fa fa-money"></i><span>Thanh toán khi nhận hàng (COD)</span>';
                    paymentSummary.classList.toggle('payment-badge-vnpay', isVnPay);
                    paymentSummary.classList.toggle('payment-badge-cod', !isVnPay);
                }

                if (confirmMessage) {
                    confirmMessage.textContent = isVnPay
                        ? 'Bạn sẽ được chuyển sang VNPay sandbox.'
                        : 'Thanh toán khi nhận hàng - bạn chỉ trả tiền khi nhận được đơn.';
                }

                if (confirmIcon) {
                    confirmIcon.innerHTML = isVnPay
                        ? '<i class="fa fa-credit-card"></i>'
                        : '<i class="fa fa-money"></i>';
                    confirmIcon.classList.toggle('confirm-icon-vnpay', isVnPay);
                    confirmIcon.classList.toggle('confirm-icon-cod', !isVnPay);
                }

                if (confirmSubmit) {
                    confirmSubmit.textContent = isVnPay ? 'Xác nhận thanh toán' : 'Xác nhận đặt hàng';
                    confirmSubmit.classList.toggle('btn-modal-confirm-vnpay', isVnPay);
                    confirmSubmit.classList.toggle('btn-modal-confirm-cod', !isVnPay);
                }

                if (securityText) {
                    securityText.textContent = isVnPay
                        ? 'Thanh toán online an toàn qua VNPay'
                        : 'Đặt hàng an toàn - thanh toán khi nhận hàng';
                }
            }

            paymentCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    const input = card.querySelector('input[name="payment_method"]');
                    if (input) {
                        input.checked = true;
                        syncPaymentUi();
                    }
                });
            });

            syncPaymentUi();
        });
    </script>
@endpush
