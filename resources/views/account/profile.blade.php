@extends('layouts.app')

@section('title', 'Sửa hồ sơ - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/user/edit-profile.css') }}">
@endpush

@section('content')
@php
    $avatar = $user->avatar_url ?: 'user-default.png';
    $avatarUrl = str_starts_with($avatar, 'http')
        ? $avatar
        : asset(str_starts_with($avatar, 'upload/') ? $avatar : 'upload/' . $avatar);
@endphp

<div class="breadcrumb-option">
    <div class="container">
        <div class="breadcrumb__links">
            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ route('account.index') }}">Tài khoản</a>
            <span>Sửa hồ sơ</span>
        </div>
    </div>
</div>

<section class="profile-edit-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <img src="{{ $avatarUrl }}" alt="avatar" class="profile-avatar">
                        <div class="profile-info">
                            <h6 class="profile-name">{{ $user->full_name }}</h6>
                            <a href="{{ route('account.index') }}" class="profile-edit">Tài khoản</a>
                        </div>
                    </div>
                    <div class="profile-menu">
                        <a href="{{ route('account.index') }}" class="menu-item"><i class="fa fa-user"></i><span>Hồ sơ</span></a>
                        <a href="{{ route('account.orders.index') }}" class="menu-item"><i class="fa fa-shopping-bag"></i><span>Đơn mua</span></a>
                        <a href="{{ route('account.password.edit') }}" class="menu-item"><i class="fa fa-lock"></i><span>Đổi mật khẩu</span></a>
                        <a href="{{ route('logout.get') }}" class="menu-item"><i class="fa fa-sign-out-alt"></i><span>Đăng xuất</span></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-edit-card">
                    <div class="card-header">
                        <h5 class="card-title">Sửa hồ sơ</h5>
                        <p class="card-subtitle">Quản lý thông tin cá nhân của bạn</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('account.profile.update') }}" method="post" class="profile-form" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-row">
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label">Họ tên <span class="required">*</span></label>
                                    <input type="text" class="form-input" name="full_name"
                                        value="{{ old('full_name', $user->full_name) }}" placeholder="Nhập họ tên">
                                    @error('full_name')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-input" value="{{ $user->email }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="text" class="form-input" name="phone"
                                        value="{{ old('phone', $user->phone) }}" placeholder="Nhập số điện thoại">
                                    @error('phone')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label">Giới tính</label>
                                    <select class="form-input" name="gender">
                                        <option value="MALE" @selected(old('gender', $user->gender ?? 'OTHER') === 'MALE')>Nam</option>
                                        <option value="FEMALE" @selected(old('gender', $user->gender ?? 'OTHER') === 'FEMALE')>Nữ</option>
                                        <option value="OTHER" @selected(old('gender', $user->gender ?? 'OTHER') === 'OTHER')>Khác</option>
                                    </select>
                                    @error('gender')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" class="form-input" name="date_of_birth"
                                value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}">
                            @error('date_of_birth')<span class="error-text">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Hình ảnh đại diện</label>
                            <div class="image-upload-area">
                                <div class="current-image">
                                    <img src="{{ $avatarUrl }}" alt="Avatar hiện tại" class="preview-image">
                                    <span class="image-label">Ảnh hiện tại</span>
                                </div>
                                <input type="file" class="form-input-file" name="avatar" accept="image/*">
                                <p class="file-hint">Chọn ảnh JPG, PNG hoặc WEBP, tối đa 5MB</p>
                            </div>
                            @error('avatar')<span class="error-text">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit"><i class="fa fa-check"></i>Cập nhật hồ sơ</button>
                            <a href="{{ route('account.index') }}" class="btn-cancel">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
