@extends('layouts.app')

@section('title', 'Khôi phục mật khẩu - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/login.css') }}">
@endpush

@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3>Khôi Phục Mật Khẩu</h3>
                        <p>Tạo mật khẩu mới cho tài khoản của bạn</p>
                    </div>

                    <form action="{{ route('password.update') }}" method="post" class="auth-form" autocomplete="off">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email', $email) }}">
                        <input type="hidden" name="token" value="{{ $token }}">

                        @if ($errors->any())
                            <div class="alert-error">
                                <i class="fa fa-exclamation-circle"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="password">Mật khẩu mới</label>
                            <div class="input-with-icon">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Nhập mật khẩu mới" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password', 'show_eye_1', 'hide_eye_1')">
                                    <i class="fa fa-eye" id="show_eye_1"></i>
                                    <i class="fa fa-eye-slash view-user-login-inline-1" id="hide_eye_1"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Xác nhận mật khẩu</label>
                            <div class="input-with-icon">
                                <i class="fa fa-check"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                                <button type="button" class="toggle-password"
                                    onclick="togglePassword('password_confirmation', 'show_eye_2', 'hide_eye_2')">
                                    <i class="fa fa-eye" id="show_eye_2"></i>
                                    <i class="fa fa-eye-slash view-user-login-inline-1" id="hide_eye_2"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Đổi Mật Khẩu</button>

                        <div class="divider">
                            <span>hoặc</span>
                        </div>

                        <div class="auth-footer">
                            <p>Chưa có tài khoản?</p>
                            <a href="{{ route('register') }}" class="btn-register">Tạo Tài Khoản Mới</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        function togglePassword(inputId, showEyeId, hideEyeId) {
            const passwordInput = document.getElementById(inputId);
            const showEye = document.getElementById(showEyeId);
            const hideEye = document.getElementById(hideEyeId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                showEye.style.display = 'none';
                hideEye.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                showEye.style.display = 'block';
                hideEye.style.display = 'none';
            }
        }
    </script>
@endpush
