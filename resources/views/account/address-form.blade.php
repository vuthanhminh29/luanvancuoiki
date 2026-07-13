@extends('layouts.app')

@php
    $isEdit = $mode === 'edit';
    $title = $isEdit ? 'Chỉnh sửa địa chỉ' : 'Thêm địa chỉ';
@endphp

@section('title', $title . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset($isEdit ? 'css/views/user/edit-address.css' : 'css/views/user/add-address.css') }}">
@endpush

@section('content')
<div class="breadcrumb-option">
    <div class="container">
        <div class="breadcrumb__links">
            <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
            <a href="{{ route('account.index') }}">Tài khoản</a>
            <span>{{ $title }}</span>
        </div>
    </div>
</div>

<section class="address-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-sidebar">
                    <div class="profile-menu">
                        <a href="{{ route('account.index') }}" class="menu-item"><i class="fa fa-user"></i><span>Hồ sơ</span></a>
                        <a href="{{ route('account.orders.index') }}" class="menu-item"><i class="fa fa-shopping-bag"></i><span>Đơn mua</span></a>
                        <a href="{{ route('account.password.edit') }}" class="menu-item"><i class="fa fa-lock"></i><span>Đổi mật khẩu</span></a>
                        <a href="{{ route('logout.get') }}" class="menu-item"><i class="fa fa-sign-out-alt"></i><span>Đăng xuất</span></a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="address-card">
                    <div class="card-header">
                        <h5 class="card-title">{{ $title }}</h5>
                        <p class="card-subtitle">Cập nhật thông tin địa chỉ giao hàng</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ $isEdit ? route('account.addresses.update', $address) : route('account.addresses.store') }}" method="post" class="address-form">
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Họ tên người nhận <span class="required">*</span></label>
                                    <input type="text" class="form-input" name="recipient_name"
                                        value="{{ old('recipient_name', $address->recipient_name) }}" placeholder="Nhập họ tên">
                                    @error('recipient_name')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại <span class="required">*</span></label>
                                    <input type="text" class="form-input" name="phone"
                                        value="{{ old('phone', $address->phone) }}" placeholder="Nhập số điện thoại">
                                    @error('phone')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Tỉnh/Thành phố <span class="required">*</span></label>
                                    <select class="form-select" name="province_name">
                                        <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city }}" @selected(old('province_name', $address->province_name) === $city)>{{ $city }}</option>
                                        @endforeach
                                    </select>
                                    @error('province_name')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Địa chỉ chi tiết <span class="required">*</span></label>
                                    <input type="text" class="form-input" name="address_detail"
                                        value="{{ old('address_detail', $address->address_detail) }}" placeholder="Số nhà, đường, phường/xã, quận/huyện...">
                                    @error('address_detail')<span class="error-text">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))>
                                Đặt làm địa chỉ mặc định
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fa {{ $isEdit ? 'fa-save' : 'fa-plus' }}"></i>
                                {{ $isEdit ? 'Lưu thay đổi' : 'Thêm địa chỉ' }}
                            </button>
                            <a href="{{ route('account.index') }}" class="btn-cancel">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
