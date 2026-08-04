@extends('layouts.app')

@section('title', 'Tài khoản - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/user/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/views/user/user-infor.css') }}">
@endpush

@section('content')
@php
    $avatar = $user->avatar_url ?: 'user-default.png';
    $avatarUrl = str_starts_with($avatar, 'http')
        ? $avatar
        : asset(str_starts_with($avatar, 'upload/') ? $avatar : 'upload/' . $avatar);
    $defaultAddress = $addresses->first();
    $extraAddresses = $addresses->skip(1);
    $canAddAddress = $addresses->count() < 2;
    $genderMap = ['MALE' => 'Nam', 'FEMALE' => 'Nữ', 'OTHER' => 'Khác'];
@endphp

<section class="account-section">
    <div class="breadcrumb-option mb-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb__links">
                        <a href="{{ route('home') }}"><i class="fa fa-home"></i> Trang chủ</a>
                        <span>Thông tin tài khoản</span>
                    </div>
                </div>

                <div class="account-card tryon-history-card" id="lich-su-thu-kinh">
                    <div class="tryon-history-head">
                        <div>
                            <span class="tryon-history-eyebrow">áº¢nh Ä‘Ã£ lÆ°u</span>
                            <h5 class="card-title">Lá»‹ch sá»­ thá»­ kÃ­nh</h5>
                            <p class="card-subtitle">CÃ¡c áº£nh báº¡n Ä‘Ã£ chá»¥p sau khi thá»­ kÃ­nh trá»±c tuyáº¿n</p>
                        </div>
                        <div class="tryon-history-summary">
                            <strong>{{ $tryOnCount }}</strong>
                            <span>áº£nh Ä‘Ã£ lÆ°u</span>
                        </div>
                    </div>

                    {{-- Lá»‹ch sá»­ nÃ y láº¥y tá»« báº£ng try_on_snapshots theo user_id cá»§a tÃ i khoáº£n Ä‘ang Ä‘Äƒng nháº­p. --}}
                    @if ($tryOnSnapshots->isNotEmpty())
                        <div class="tryon-history-grid">
                            @foreach ($tryOnSnapshots as $snapshot)
                                {{-- Má»—i tháº» lÃ  má»™t láº§n khÃ¡ch báº¥m nÃºt Chá»¥p/LÆ°u káº¿t quáº£ á»Ÿ trang thá»­ kÃ­nh. --}}
                                <article class="tryon-history-item">
                                    <a href="{{ $snapshot->image_url }}" target="_blank" rel="noopener" class="tryon-history-photo">
                                        <img src="{{ $snapshot->image_url }}" alt="{{ $snapshot->product_name }}">
                                    </a>

                                    <div class="tryon-history-info">
                                        <h6>{{ $snapshot->product_name }}</h6>
                                        <span><i class="fa fa-clock-o"></i> {{ $snapshot->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="tryon-history-actions">
                            <a href="{{ route('tryon') }}" class="btn-orders">
                                <i class="fa fa-camera-retro"></i>
                                Thá»­ kÃ­nh tiáº¿p
                            </a>
                        </div>
                    @else
                        <div class="tryon-history-empty">
                            <i class="fa fa-camera-retro"></i>
                            <h6>ChÆ°a cÃ³ áº£nh thá»­ kÃ­nh</h6>
                            <p>Sau khi thá»­ kÃ­nh, báº¥m Chá»¥p/LÆ°u káº¿t quáº£ Ä‘á»ƒ áº£nh xuáº¥t hiá»‡n táº¡i Ä‘Ã¢y.</p>
                            <a href="{{ route('tryon') }}" class="btn-orders">Thá»­ kÃ­nh ngay</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <img src="{{ $avatarUrl }}" alt="avatar" class="profile-avatar">
                        <div class="profile-info">
                            <h6 class="profile-name">{{ $user->full_name }}</h6>
                            <a href="{{ route('account.profile.edit') }}" class="profile-edit">Sửa hồ sơ</a>
                        </div>
                    </div>

                    <div class="profile-menu">
                        <a href="{{ route('account.index') }}" class="menu-item active">
                            <i class="fa fa-user"></i>
                            <span>Hồ sơ</span>
                        </a>
                        <a href="{{ route('account.orders.index') }}" class="menu-item">
                            <i class="fa fa-shopping-bag"></i>
                            <span>Đơn mua</span>
                        </a>
                        <a href="{{ route('account.password.edit') }}" class="menu-item">
                            <i class="fa fa-lock"></i>
                            <span>Đổi mật khẩu</span>
                        </a>
                        <a href="{{ route('logout.get') }}" class="menu-item">
                            <i class="fa fa-sign-out-alt"></i>
                            <span>Đăng xuất</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="account-card">
                    <div class="card-header">
                        <h5 class="card-title">Thông tin tài khoản</h5>
                        <p class="card-subtitle">Quản lý thông tin cá nhân của bạn</p>
                    </div>

                    <div class="account-info">
                        <div class="info-row">
                            <div class="info-label">Họ tên</div>
                            <div class="info-value">{{ $user->full_name }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value">{{ $user->email }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Số điện thoại</div>
                            <div class="info-value">{{ $user->phone ?: 'Chưa cập nhật' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Email đăng nhập</div>
                            <div class="info-value">{{ $user->email }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Giới tính</div>
                            <div class="info-value">{{ $genderMap[$user->gender ?? 'OTHER'] ?? 'Khác' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Ngày sinh</div>
                            <div class="info-value">{{ $user->date_of_birth?->format('d/m/Y') ?? 'Chưa cập nhật' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Mật khẩu</div>
                            <div class="info-value">*********</div>
                            <div class="info-action">
                                <a href="{{ route('account.password.edit') }}" class="link-primary">Thay đổi</a>
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Địa chỉ 1</div>
                            <div class="info-value">
                                {{ $defaultAddress?->full_address ?: 'Chưa cập nhật' }}
                                @if ($defaultAddress?->is_default)
                                    <span class="address-hint">(mặc định)</span>
                                @endif
                            </div>
                            @if ($defaultAddress)
                                <div class="info-action">
                                    <a href="{{ route('account.addresses.edit', $defaultAddress) }}" class="link-primary me-3">
                                        <i class="fa fa-edit"></i> Sửa
                                    </a>
                                    <form action="{{ route('account.addresses.destroy', $defaultAddress) }}" method="post" class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-danger border-0 bg-transparent p-0">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        @foreach ($extraAddresses as $address)
                            <div class="info-row">
                                <div class="info-label">{{ $loop->first ? 'Địa chỉ 2' : 'Địa chỉ bổ sung ' . ($loop->iteration + 1) }}</div>
                                <div class="info-value">
                                    {{ $address->full_address }}
                                    @if ($loop->first)
                                        <span class="address-hint">(địa chỉ thêm lần đầu)</span>
                                    @endif
                                </div>
                                <div class="info-action">
                                    <a href="{{ route('account.addresses.edit', $address) }}" class="link-primary me-3">
                                        <i class="fa fa-edit"></i> Sửa
                                    </a>
                                    <form action="{{ route('account.addresses.destroy', $address) }}" method="post" class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-danger border-0 bg-transparent p-0">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach

                        <div class="info-row">
                            <div class="info-label">&nbsp;</div>
                            <div class="info-value">&nbsp;</div>
                            <div class="info-action">
                                @if ($canAddAddress)
                                    <a href="{{ route('account.addresses.create') }}" class="link-primary">
                                        <i class="fa fa-plus"></i> Thêm địa chỉ
                                    </a>
                                @else
                                    <span class="limit-notice">
                                        <i class="fa fa-info-circle"></i>
                                        Đã đủ 2 địa chỉ (hồ sơ + địa chỉ thêm). Muốn thêm mới, hãy xóa hoặc sửa địa chỉ thêm.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="{{ route('account.profile.edit') }}" class="btn-edit">
                            <i class="fa fa-edit"></i>
                            Sửa hồ sơ
                        </a>
                        <a href="{{ route('account.orders.index') }}" class="btn-orders">
                            <i class="fa fa-shopping-bag"></i>
                            Đơn mua
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
