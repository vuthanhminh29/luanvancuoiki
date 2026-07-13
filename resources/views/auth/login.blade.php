@extends('layouts.app')

@section('title', 'Đăng nhập - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/login.css') }}">
@endpush

@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3>Đăng Nhập</h3>
                        <p>Chào mừng bạn quay trở lại</p>
                    </div>

                    <form action="{{ route('login.store') }}" method="post" class="auth-form" autocomplete="off">
                        @csrf

                        @if (session('status'))
                            <div class="alert-success">
                                <i class="fa fa-check-circle"></i>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert-error">
                                <i class="fa fa-exclamation-circle"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="input-with-icon">
                                <i class="fa fa-envelope"></i>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="Nhập email" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <div class="input-with-icon">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Nhập mật khẩu" required>
                                <button type="button" class="toggle-password" onclick="togglePassword()">
                                    <i class="fa fa-eye" id="show_eye"></i>
                                    <i class="fa fa-eye-slash view-user-login-inline-1" id="hide_eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="{{ route('password.request') }}" class="forgot-link">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" class="btn-submit">Đăng Nhập</button>

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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const showEye = document.getElementById('show_eye');
            const hideEye = document.getElementById('hide_eye');

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
