@extends('layouts.app')

@section('title', 'Quên mật khẩu - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/login.css') }}">
@endpush

@section('content')
    <section class="auth-section">
        <div class="container">
            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3>Quên Mật Khẩu</h3>
                        <p>Nhập email để khôi phục mật khẩu của bạn</p>
                    </div>

                    <form action="{{ route('password.email') }}" method="post" class="auth-form" autocomplete="off">
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
                            <label for="email">Email đăng ký</label>
                            <div class="input-with-icon">
                                <i class="fa fa-envelope"></i>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="Nhập email của bạn" value="{{ old('email') }}" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Lấy Lại Mật Khẩu</button>

                        <div class="divider">
                            <span>hoặc</span>
                        </div>

                        <div class="auth-footer">
                            <p>Đã nhớ mật khẩu?</p>
                            <a href="{{ route('login') }}" class="btn-register">Đăng Nhập</a>
                        </div>

                        <div class="auth-footer auth-footer-spaced">
                            <p>Chưa có tài khoản?</p>
                            <a href="{{ route('register') }}" class="btn-register">Tạo Tài Khoản Mới</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
