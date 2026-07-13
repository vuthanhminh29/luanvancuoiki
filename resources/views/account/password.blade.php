@extends('layouts.app')

@section('title', 'Đổi mật khẩu - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/user/change-password.css') }}">
@endpush

@section('content')
<div class="breadcrumb-option">
    <div class="container">
        <div class="breadcrumb__links">
            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ route('account.index') }}">Tài khoản</a>
            <span>Đổi mật khẩu</span>
        </div>
    </div>
</div>

<section class="password-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <div class="profile-info">
                            <h6 class="profile-name">{{ $user->full_name }}</h6>
                            <a href="{{ route('account.index') }}" class="profile-edit">Tài khoản</a>
                        </div>
                    </div>
                    <div class="profile-menu">
                        <a href="{{ route('account.index') }}" class="menu-item"><i class="fa fa-user"></i><span>Hồ sơ</span></a>
                        <a href="{{ route('account.orders.index') }}" class="menu-item"><i class="fa fa-shopping-bag"></i><span>Đơn mua</span></a>
                        <a href="{{ route('account.password.edit') }}" class="menu-item active"><i class="fa fa-lock"></i><span>Đổi mật khẩu</span></a>
                        <a href="{{ route('logout.get') }}" class="menu-item"><i class="fa fa-sign-out-alt"></i><span>Đăng xuất</span></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="password-card">
                    <div class="card-header">
                        <h5 class="card-title">Đổi mật khẩu</h5>
                        <p class="card-subtitle">Cập nhật mật khẩu của bạn để bảo mật tài khoản</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('account.password.update') }}" method="post" class="password-form">
                        @csrf
                        @method('PUT')

                        <div class="form-notice">
                            <i class="fa fa-info-circle"></i>
                            <span>Mật khẩu mới tối thiểu 8 ký tự.</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mật khẩu hiện tại <span class="required">*</span></label>
                            <input type="password" class="form-input" name="current_password" placeholder="Nhập mật khẩu hiện tại">
                            @error('current_password')<span class="error-text">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mật khẩu mới <span class="required">*</span></label>
                            <input type="password" class="form-input" name="password" placeholder="Nhập mật khẩu mới">
                            @error('password')<span class="error-text">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Xác nhận mật khẩu mới <span class="required">*</span></label>
                            <input type="password" class="form-input" name="password_confirmation" placeholder="Nhập lại mật khẩu mới">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit"><i class="fa fa-check"></i>Đổi mật khẩu</button>
                            <a href="{{ route('account.index') }}" class="btn-cancel">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
