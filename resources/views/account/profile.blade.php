@extends('layouts.app')

@section('title', 'Chỉnh sửa hồ sơ - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/user/edit-profile.css') }}">
@endpush

@section('content')
<div class="breadcrumb-option">
    <div class="container">
        <div class="breadcrumb__links">
            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ route('account.index') }}">Tài khoản</a>
            <span>Chỉnh sửa hồ sơ</span>
        </div>
    </div>
</div>

<section class="profile-edit-section">
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
                        <a href="{{ route('account.profile.edit') }}" class="menu-item active"><i class="fa fa-user"></i><span>Hồ sơ</span></a>
                        <a href="{{ route('account.orders.index') }}" class="menu-item"><i class="fa fa-shopping-bag"></i><span>Đơn mua</span></a>
                        <a href="{{ route('account.password.edit') }}" class="menu-item"><i class="fa fa-lock"></i><span>Đổi mật khẩu</span></a>
                        <form method="post" action="{{ route('logout') }}" class="logout-menu-form">
                            @csrf
                            <button type="submit" class="menu-item"><i class="fa fa-sign-out-alt"></i><span>Đăng xuất</span></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-edit-card">
                    <div class="card-header">
                        <h5 class="card-title">Chỉnh sửa hồ sơ</h5>
                        <p class="card-subtitle">Cập nhật thông tin cá nhân và ảnh đại diện của bạn</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('account.profile.update') }}" method="post" class="profile-form" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label class="form-label" for="full_name">Họ và tên <span class="required">*</span></label>
                            <input type="text" class="form-input" id="full_name" name="full_name"
                                   value="{{ old('full_name', $user->full_name) }}" maxlength="100" required>
                            @error('full_name')<span class="error-text">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-row">
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label" for="phone">Số điện thoại</label>
                                    <input type="tel" class="form-input" id="phone" name="phone"
                                           value="{{ old('phone', $user->phone) }}" placeholder="0901234567">
                                    @error('phone')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label" for="date_of_birth">Ngày sinh</label>
                                    {{-- Chặn ngay ở input cho khớp rule before_or_equal:today và after_or_equal:1900-01-01. --}}
                                    <input type="date" class="form-input" id="date_of_birth" name="date_of_birth"
                                           value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                                           min="1900-01-01" max="{{ now()->toDateString() }}">
                                    @error('date_of_birth')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="gender">Giới tính</label>
                            <select class="form-input" id="gender" name="gender">
                                <option value="MALE" @selected(old('gender', $user->gender) === 'MALE')>Nam</option>
                                <option value="FEMALE" @selected(old('gender', $user->gender) === 'FEMALE')>Nữ</option>
                                <option value="OTHER" @selected(old('gender', $user->gender) === 'OTHER')>Khác</option>
                            </select>
                            @error('gender')<span class="error-text">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label class="image-label" for="avatar">Ảnh đại diện</label>
                            <div class="image-upload-area">
                                @if ($user->avatar_url)
                                    <img src="{{ asset('upload/' . $user->avatar_url) }}"
                                         alt="Ảnh đại diện hiện tại của {{ $user->full_name }}" class="current-image">
                                @endif
                                <input type="file" class="form-input-file" id="avatar" name="avatar"
                                       accept="image/jpeg,image/png,image/webp">
                                <span class="file-hint">JPG, PNG hoặc WEBP, tối đa 5MB. Để trống nếu giữ ảnh cũ.</span>
                                @error('avatar')<span class="error-text">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit"><i class="fa fa-check"></i>Lưu thay đổi</button>
                            <a href="{{ route('account.index') }}" class="btn-cancel">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
