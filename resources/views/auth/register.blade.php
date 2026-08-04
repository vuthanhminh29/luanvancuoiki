@extends('layouts.app')

@section('title', 'Đăng ký - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/register.css') }}">
@endpush

@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3>Đăng Ký Tài Khoản</h3>
                        <p>Tạo tài khoản mới để mua sắm</p>
                    </div>

                    <form action="{{ route('register.store') }}" method="post" class="auth-form" autocomplete="off">
                        @csrf

                        @if ($errors->any())
                            <div class="alert-error">
                                <i class="fa fa-exclamation-circle"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-envelope"></i>
                                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                            placeholder="Nhập địa chỉ email" value="{{ old('email') }}" required>
                                    </div>
                                    @error('email')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Họ và tên <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-user"></i>
                                        <input type="text" id="full_name" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                            placeholder="Nhập họ và tên" value="{{ old('full_name') }}" required>
                                    </div>
                                    @error('full_name')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Số điện thoại</label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-phone"></i>
                                        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                            placeholder="Nhập số điện thoại" value="{{ old('phone') }}">
                                    </div>
                                    @error('phone')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="province_name">Tỉnh/Thành phố <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-map"></i>
                                        <select id="province_name" name="province_name" class="form-control @error('province_name') is-invalid @enderror" required>
                                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                            @foreach ($cities as $city)
                                                <option value="{{ $city }}" @selected(old('province_name') === $city)>{{ $city }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('province_name')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address_detail">Địa chỉ chi tiết <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-map-marker"></i>
                                        <input type="text" id="address_detail" name="address_detail" class="form-control @error('address_detail') is-invalid @enderror"
                                            placeholder="Số nhà, đường, phường/xã..." value="{{ old('address_detail') }}" required>
                                    </div>
                                    @error('address_detail')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Mật khẩu <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-lock"></i>
                                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Nhập mật khẩu" required>
                                        <button type="button" class="toggle-password" onclick="togglePassword()">
                                            <i class="fa fa-eye" id="show_eye"></i>
                                            <i class="fa fa-eye-slash view-user-login-inline-1" id="hide_eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirmation">Xác nhận mật khẩu <span class="required">*</span></label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-check"></i>
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            class="form-control @error('password_confirmation') is-invalid @enderror" placeholder="Nhập lại mật khẩu" required>
                                    </div>
                                    @error('password_confirmation')
                                        <span class="invalid-feedback-custom">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Đăng Ký</button>

                        <div class="divider">
                            <span>hoặc</span>
                        </div>

                        <div class="auth-footer">
                            <p>Đã có tài khoản?</p>
                            <a href="{{ route('login') }}" class="btn-login-link btn-register">Đăng Nhập Ngay</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');
            const showEye = document.getElementById('show_eye');
            const hideEye = document.getElementById('hide_eye');
            const show = password.type === 'password';

            password.type = show ? 'text' : 'password';
            passwordConfirm.type = show ? 'text' : 'password';
            showEye.style.display = show ? 'none' : 'block';
            hideEye.style.display = show ? 'block' : 'none';
        }
    </script>
@endpush
