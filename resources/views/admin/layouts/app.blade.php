<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('admin_assets/img/favicon.ico') }}" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/font-awesome-all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/lib/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/custom.css') }}" rel="stylesheet">
    @stack('styles')
    <style>
        body, html, h1, h2, h3, h4, h5, h6, input, button, select, textarea { font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important; }
        .container-xxl { max-width: none; }
        body { background: #f3f6fa; color: #111827; }
        .fa-bars { color: #2563eb; }
        .sidebar { height: 100vh; overflow-y: auto; scrollbar-width: none; width: 280px; background: #111827 !important; box-shadow: inset -1px 0 0 rgba(255,255,255,.06); }
        .sidebar::-webkit-scrollbar { display: none; }
        .sidebar.pe-4 { padding-right: 0 !important; }
        .sidebar.pb-3 { padding-bottom: 0 !important; }
        /* Sidebar là position: fixed (đặt trong admin_assets/css/style.css) nên nằm
           ngoài luồng, không chiếm chỗ. Vì vậy .content BẮT BUỘC có margin-left bằng
           đúng bề rộng sidebar. Theme gốc dùng cặp 250px/250px; ở đây sidebar được
           nới lên 280px nên margin cũng phải 280px, sai số bao nhiêu là chồng lấn bấy nhiêu. */
        .content { margin-left: 280px; background: #f3f6fa; }
        .sidebar, .sidebar .navbar { background: #111827 !important; }
        /* flex-wrap: nowrap là bắt buộc. Bootstrap đặt .navbar { flex-wrap: wrap },
           kết hợp với flex-direction: column + chiều cao cố định thì menu dài hơn
           màn hình sẽ cuốn sang CỘT THỨ HAI, đẩy toàn bộ menu sang phải đúng bằng
           bề rộng logo (203px) và cắt cụt chữ. Chiều cao để auto cho menu dài ra,
           .sidebar (height:100vh, overflow-y:auto) lo phần cuộn. */
        .sidebar .navbar { align-content: flex-start; align-items: flex-start; display: flex; flex-wrap: nowrap; flex-direction: column; height: auto; justify-content: flex-start !important; min-height: 100%; padding: 16px 0 0; }
        .sidebar .navbar-brand { align-items: center; display: flex; flex: 0 0 auto; margin: 0 0 12px 26px !important; min-height: 42px; }
        .sidebar .navbar-brand h3 { color: #fff !important; font-size: 25px; font-weight: 800; letter-spacing: 0; margin: 0; }
        .sidebar .navbar .navbar-nav { display: flex; flex: 1 1 auto; flex-direction: column; justify-content: space-between; margin-top: 0 !important; min-height: 0; padding: 0 14px 16px 0; width: 100%; }
        .sidebar .navbar .navbar-nav .nav-link {
            align-items: center;
            border-left: 0 !important;
            border-radius: 0 26px 26px 0;
            color: #cbd5e1 !important;
            display: flex;
            font-size: 16px;
            font-weight: 800;
            gap: 13px;
            margin: 0;
            min-height: 43px;
            padding: 0 18px 0 34px;
            position: relative;
            transition: background-color .15s ease, color .15s ease;
        }
        .sidebar .navbar .navbar-nav .nav-link i {
            background: transparent !important;
            border-radius: 0 !important;
            color: inherit;
            display: inline-block;
            flex: 0 0 24px;
            font-size: 17px;
            height: auto;
            line-height: 1;
            margin: 0 !important;
            text-align: center;
            width: 24px;
        }
        .sidebar .navbar .navbar-nav .dropdown-toggle { color: #cbd5e1 !important; }
        .sidebar .navbar .navbar-nav .nav-link:hover,
        .sidebar .navbar .navbar-nav .nav-link.active,
        .sidebar .dropdown-item.active { color: #fff !important; background: #2563eb !important; border-left: 0 !important; box-shadow: 0 10px 24px rgba(37,99,235,.24); }
        .sidebar .navbar .nav-link:hover i,
        .sidebar .navbar .nav-link.active i { background: transparent !important; }
        .sidebar .navbar .dropdown-toggle::after { top: calc(50% - 3px); right: 14px; }
        .sidebar .dropdown-menu { background: #0f172a !important; border-radius: 0 16px 16px 0; margin: 0 0 4px 0; padding: 4px 0; }
        .sidebar .dropdown-item { color:#cbd5e1 !important; font-size:13px; font-weight:700; min-height:27px; padding:5px 22px 5px 66px; }
        .sidebar .dropdown-item:hover { color: #fff !important; background: #1d4ed8 !important; }
        .content .navbar { min-height: 72px; background: #fff !important; border-bottom: 1px solid #e5e7eb; box-shadow: none; }
        .content .navbar .form-control { min-height: 42px; min-width: 230px; border-radius: 6px; }
        .content .navbar .sidebar-toggler { width: 46px; height: 46px; }
        .admin-user-box { align-items: center; display: flex; gap: 10px; }
        .admin-user-box img { width: 40px; height: 40px; }
        .admin-logout-btn {
            align-items: center;
            background: #fff;
            border: 1px solid #d0d5dd;
            border-radius: 7px;
            color: #111827;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 7px;
            min-height: 36px;
            padding: 0 12px;
            white-space: nowrap;
        }
        .admin-logout-btn:hover { background: #fee2e2; border-color: #fecaca; color: #991b1b; }
        .admin-title { padding: 0 0 18px; }
        .admin-title .eyebrow { margin: 0 0 8px; color: #2563eb; font-size: 13px; font-weight: 700; text-transform: uppercase; }
        .admin-title h1 { margin: 0; color: #111827; font-size: 30px; font-weight: 800; }
        .section { margin-bottom: 24px; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 0 0 1px rgba(0,0,0,.03); }
        .section-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 24px; margin-bottom: 24px; }
        .metric { display: grid; gap: 10px; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 0 0 1px rgba(0,0,0,.03); }
        .metric span { color: #757575; font-size: 14px; font-weight: 600; }
        .metric strong { color: #111827; font-size: 26px; line-height: 1.2; }
        .admin-table { overflow-x: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
        .admin-row { display: grid; grid-template-columns: repeat(5, minmax(140px, 1fr)); gap: 12px; min-width: 760px; padding: 14px 16px; align-items: center; }
        .admin-row + .admin-row { border-top: 1px solid #eef0f3; }
        .admin-row.head { color: #fff; background: #111827; font-weight: 700; }
        .inline-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 16px; }
        .inline-filter input, .inline-filter select { min-height: 42px; padding: 8px 12px; border: 1px solid #d9dee3; border-radius: 6px; background: #fff; }
        .btn.primary, .btn-primary { color: #fff; background: #2563eb; border-color: #2563eb; }
        .flash { margin-bottom: 18px; padding: 12px 16px; color: #0f5132; background: #d1e7dd; border: 1px solid #badbcc; border-radius: 8px; }
        .form-shell { max-width: 760px; margin: 0 auto 24px; }
        .panel-form { display: grid; gap: 14px; padding: 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
        .panel-form.compact { padding: 0; border: 0; }
        .panel-form label, .checkout-panel label { display: grid; gap: 7px; color: #111827; font-weight: 700; }
        .panel-form input, .panel-form select, .panel-form textarea, .checkout-panel input, .checkout-panel select, .checkout-panel textarea { min-height: 42px; padding: 9px 12px; border: 1px solid #d9dee3; border-radius: 6px; color: #111827; background: #fff; font-size: 14px; font-weight: 600; }
        .checkout-shell { display: grid; grid-template-columns: minmax(0, 1fr) 420px; gap: 24px; margin-bottom: 24px; }
        .checkout-panel { padding: 24px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
        .summary-line, .summary-total { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid #eef0f3; }
        .summary-total { border-bottom: 0; color: #111827; font-size: 18px; font-weight: 800; }
        .pagination { margin-top: 18px; }
        .ck-editor__editable[role="textbox"] { min-height: 280px; }
        .ck-content .image { max-width: 80%; margin: 20px auto; }
        @media (min-width: 992px) {
            .sidebar.open { margin-left: -280px; }
            .content.open { margin-left: 0; width: 100%; }
        }
        @media (max-width: 991px) {
            .sidebar { margin-left: -280px; }
            .sidebar.open { margin-left: 0; }
            .content { margin-left: 0; width: 100%; }
            .admin-row { grid-template-columns: 1fr; min-width: 0; }
            .checkout-shell { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@php
    $isRoute = fn (...$patterns) => request()->routeIs(...$patterns);
    $userId = auth()->id();
    $adminRoleCodes = $userId
        ? \Illuminate\Support\Facades\Cache::remember("users.{$userId}.role_codes", 300, function () use ($userId) {
            return \Illuminate\Support\Facades\DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.user_id', $userId)
                ->pluck('roles.code')
                ->all();
        })
        : [];
    $isAdminUser = in_array('ADMIN', $adminRoleCodes, true);
@endphp

<div class="container-xxl position-relative bg-white d-flex p-0">
    <div class="sidebar pe-4 pb-3">
        <nav class="navbar bg-light navbar-light">
            <a href="{{ route('admin.dashboard') }}" class="navbar-brand mx-4 mb-3"><h3>ADMIN PANEL</h3></a>
            <div class="navbar-nav w-100">
                <a href="{{ route('admin.dashboard') }}" class="nav-item nav-link {{ $isRoute('admin.dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer-alt me-2"></i>Tổng quan</a>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle {{ $isRoute('admin.categories.*') ? 'active' : '' }}" data-bs-toggle="dropdown"><i class="fa fa-th me-2"></i>Danh mục</a>
                    <div class="dropdown-menu bg-transparent border-0">
                        <a href="{{ route('admin.categories.create') }}" class="dropdown-item {{ $isRoute('admin.categories.create') ? 'active' : '' }}">Thêm mới</a>
                        <a href="{{ route('admin.categories.index') }}" class="dropdown-item {{ $isRoute('admin.categories.index', 'admin.categories.edit') ? 'active' : '' }}">Tất cả</a>
                    </div>
                </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle {{ $isRoute('admin.products.*') ? 'active' : '' }}" data-bs-toggle="dropdown"><i class="fas fa-box me-2"></i>Sản phẩm</a>
                    <div class="dropdown-menu bg-transparent border-0">
                        <a href="{{ route('admin.products.create') }}" class="dropdown-item {{ $isRoute('admin.products.create') ? 'active' : '' }}">Thêm mới</a>
                        <a href="{{ route('admin.products.index') }}" class="dropdown-item {{ $isRoute('admin.products.index', 'admin.products.edit') ? 'active' : '' }}">Tất cả</a>
                        <a href="{{ route('admin.products.recycle') }}" class="dropdown-item {{ $isRoute('admin.products.recycle') ? 'active' : '' }}">Thùng rác</a>
                    </div>
                </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle {{ $isRoute('admin.orders.*', 'admin.returns.*') ? 'active' : '' }}" data-bs-toggle="dropdown"><i class="fas fa-shopping-basket me-2"></i>Đơn hàng</a>
                    <div class="dropdown-menu bg-transparent border-0">
                        <a href="{{ route('admin.orders.index') }}" class="dropdown-item {{ $isRoute('admin.orders.index', 'admin.orders.show') ? 'active' : '' }}">Tất cả đơn hàng</a>
                        <a href="{{ route('admin.orders.unconfirmed') }}" class="dropdown-item {{ $isRoute('admin.orders.unconfirmed') ? 'active' : '' }}">Đơn chờ xác nhận</a>
                        <a href="{{ route('admin.returns.index') }}" class="dropdown-item {{ $isRoute('admin.returns.*') ? 'active' : '' }}">Yêu cầu hoàn/đổi</a>
                    </div>
                </div>

                @if ($isAdminUser)
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle {{ $isRoute('admin.reports.*') ? 'active' : '' }}" data-bs-toggle="dropdown"><i class="fas fa-chart-bar me-2"></i>Báo cáo</a>
                    <div class="dropdown-menu bg-transparent border-0">
                        <a href="{{ route('admin.reports.products') }}" class="dropdown-item {{ $isRoute('admin.reports.products') ? 'active' : '' }}">Sản phẩm - danh mục</a>
                        <a href="{{ route('admin.reports.orders') }}" class="dropdown-item {{ $isRoute('admin.reports.orders') ? 'active' : '' }}">Đơn hàng</a>
                        <a href="{{ route('admin.reports.sales-chart') }}" class="dropdown-item {{ $isRoute('admin.reports.sales-chart') ? 'active' : '' }}">Biểu đồ lượt bán</a>
                        <a href="{{ route('admin.reports.top-sales') }}" class="dropdown-item {{ $isRoute('admin.reports.top-sales') ? 'active' : '' }}">Top lượt bán</a>
                        <a href="{{ route('admin.reports.daily-sales') }}" class="dropdown-item {{ $isRoute('admin.reports.daily-sales') ? 'active' : '' }}">Lượt bán theo ngày</a>
                    </div>
                </div>
                @endif

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle {{ $isRoute('admin.lens-options.*') ? 'active' : '' }}" data-bs-toggle="dropdown"><i class="fas fa-layer-group me-2"></i>Tròng kính</a>
                    <div class="dropdown-menu bg-transparent border-0">
                        <a href="{{ route('admin.lens-options.create') }}" class="dropdown-item {{ $isRoute('admin.lens-options.create') ? 'active' : '' }}">Thêm mới</a>
                        <a href="{{ route('admin.lens-options.index') }}" class="dropdown-item {{ $isRoute('admin.lens-options.index', 'admin.lens-options.edit') ? 'active' : '' }}">Tất cả</a>
                    </div>
                </div>

                <a href="{{ route('admin.warehouses.index') }}" class="nav-item nav-link {{ $isRoute('admin.warehouses.*') ? 'active' : '' }}"><i class="fas fa-warehouse me-2"></i>Quản lý kho</a>
                
                @if ($isAdminUser)
                <a href="{{ route('admin.promotions.index') }}" class="nav-item nav-link {{ $isRoute('admin.promotions.*') ? 'active' : '' }}"><i class="fas fa-tags me-2"></i>Khuyến mãi</a>
                <a href="{{ route('admin.customers.index') }}" class="nav-item nav-link {{ $isRoute('admin.customers.*') ? 'active' : '' }}"><i class="fas fa-users me-2"></i>Thành viên</a>
                <a href="{{ route('admin.tryon-snapshots.index') }}" class="nav-item nav-link {{ $isRoute('admin.tryon-snapshots.*') ? 'active' : '' }}"><i class="fas fa-camera-retro me-2"></i>Thử kính</a>
                <a href="{{ route('admin.posts.index') }}" class="nav-item nav-link {{ $isRoute('admin.posts.*') ? 'active' : '' }}"><i class="fas fa-newspaper me-2"></i>Bài viết</a>
                <a href="{{ route('admin.banners.index') }}" class="nav-item nav-link {{ $isRoute('admin.banners.*') ? 'active' : '' }}"><i class="fas fa-images me-2"></i>Banners</a>
                <a href="{{ route('admin.home-layout.index') }}" class="nav-item nav-link {{ $isRoute('admin.home-layout.*') ? 'active' : '' }}"><i class="fas fa-layer-group me-2"></i>Bố cục trang chủ</a>
                <a href="{{ route('admin.business.index') }}" class="nav-item nav-link {{ $isRoute('admin.business.*') ? 'active' : '' }}"><i class="fas fa-briefcase me-2"></i>Thương hiệu & Cửa hàng</a>
                @endif
                
                <a href="{{ route('admin.reviews.index') }}" class="nav-item nav-link {{ $isRoute('admin.reviews.*') ? 'active' : '' }}"><i class="fas fa-comment me-2"></i>Bình luận</a>
            </div>
        </nav>
    </div>

    <div class="content">
        <nav class="navbar navbar-expand bg-light navbar-light sticky-top px-4 py-0">
            <a href="{{ route('admin.dashboard') }}" class="navbar-brand d-flex d-lg-none me-4"><h2 class="text-primary mb-0"><i class="fa fa-hashtag"></i></h2></a>
            <a href="#" class="sidebar-toggler flex-shrink-0"><i class="fa fa-bars"></i></a>
            <div class="navbar-nav align-items-center ms-auto">
                <div class="nav-item"><a href="{{ route('home') }}" class="nav-link"><i class="fa fa-globe me-lg-2"></i><span class="d-none d-lg-inline-flex">Website</span></a></div>
                <div class="nav-item admin-user-box">
                    <a href="#" class="nav-link d-flex align-items-center">
                        <img class="rounded-circle me-lg-2" src="{{ asset('admin_assets/img/user-default.png') }}" alt="">
                        <span class="d-none d-lg-inline-flex">{{ auth()->user()->full_name ?? 'ADMIN' }}</span>
                    </a>
                    <form method="post" action="{{ route('admin.logout') }}" class="m-0" onsubmit="return confirm('Đăng xuất khỏi admin?')">
                        @csrf
                        <button class="admin-logout-btn" type="submit"><i class="fa fa-sign-out-alt"></i><span class="d-none d-md-inline">Đăng xuất</span></button>
                    </form>
                </div>
            </div>
        </nav>

        <div class="container-fluid pt-4 px-4">
            @if (session('success'))
                <div class="flash">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @yield('content')
        </div>

        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row align-items-center">
                    <div class="col-12 text-center"><i class="fa fa-shield-alt text-primary me-2"></i><span class="text-muted">&copy; 2026 Admin Dashboard</span></div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top" style="position: fixed; right: 28px; bottom: 28px; z-index: 1050; display: none;" aria-label="Trở lại đầu trang"><i class="bi bi-arrow-up"></i></a>
</div>

<script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
<script src="{{ asset('admin_assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/chart/chart.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/easing/easing.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/waypoints/waypoints.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/owlcarousel/owl.carousel.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/tempusdominus/js/moment.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/tempusdominus/js/moment-timezone.min.js') }}"></script>
<script src="{{ asset('admin_assets/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<script src="{{ asset('admin_assets/js/main.js') }}"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<!-- Admin Page Dynamic Loading Overlay -->
<div id="adminPageOverlay" style="position: fixed; top: 64px; left: 280px; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(2px); z-index: 1040; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.18s ease;">
    <div style="background: #ffffff; padding: 16px 24px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 12px; border: 1px solid #e2e8f0;">
        <div class="spinner-border text-primary" role="status" style="width: 22px; height: 22px; border-width: 3px;">
            <span class="visually-hidden">Đang tải...</span>
        </div>
        <span style="font-size: 14px; font-weight: 700; color: #0f172a;">Đang tải trang...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var contentArea = document.querySelector('.content .container-fluid');
    var overlay = document.getElementById('adminPageOverlay');
    if (!contentArea) return;

    function showLoading() {
        if (overlay) {
            overlay.style.pointerEvents = 'auto';
            overlay.style.opacity = '1';
        }
    }

    function hideLoading() {
        if (overlay) {
            overlay.style.opacity = '0';
            overlay.style.pointerEvents = 'none';
        }
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('.sidebar a');
        if (!link) return;

        var url = link.getAttribute('href');
        if (!url || url === '#' || url.startsWith('javascript:') || link.target === '_blank' || e.ctrlKey || e.metaKey) return;

        e.preventDefault();

        document.querySelectorAll('.sidebar a').forEach(function (el) { el.classList.remove('active'); });
        link.classList.add('active');

        showLoading();

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.text(); })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');

            doc.querySelectorAll('head style, head link[rel="stylesheet"]').forEach(function (styleNode) {
                var href = styleNode.getAttribute('href');
                if (href && !document.head.querySelector('link[href="' + href + '"]')) {
                    document.head.appendChild(styleNode.cloneNode(true));
                } else if (styleNode.tagName === 'STYLE') {
                    document.head.appendChild(styleNode.cloneNode(true));
                }
            });

            var newContainer = doc.querySelector('.content .container-fluid');
            if (newContainer) {
                contentArea.innerHTML = newContainer.innerHTML;
                document.title = doc.title || document.title;
                history.pushState(null, '', url);
                window.scrollTo({ top: 0, behavior: 'instant' });
            } else {
                window.location.href = url;
            }
        })
        .catch(function () {
            window.location.href = url;
        })
        .finally(function () {
            setTimeout(hideLoading, 100);
        });
    });

    window.addEventListener('popstate', function () {
        window.location.reload();
    });
});
</script>

<script>
class LaravelUploadAdapter {
    constructor(loader, uploadUrl) {
        this.loader = loader;
        this.uploadUrl = uploadUrl;
    }

    upload() {
        return this.loader.file.then(file => new Promise((resolve, reject) => {
            const data = new FormData();
            data.append('upload', file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.uploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = () => {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('Upload failed');
                    return;
                }

                const response = JSON.parse(xhr.responseText);
                response.url ? resolve({ default: response.url }) : reject('Upload failed');
            };

            xhr.onerror = () => reject('Upload failed');
            xhr.send(data);
        }));
    }

    abort() {}
}

function editorUploadPlugin(uploadUrl) {
    return function(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = loader => new LaravelUploadAdapter(loader, uploadUrl);
    };
}

function bootClassicEditor(selector, uploadUrl) {
    const element = document.querySelector(selector);
    if (!element || typeof ClassicEditor === 'undefined') {
        return;
    }

    ClassicEditor
        .create(element, { extraPlugins: [editorUploadPlugin(uploadUrl)] })
        .catch(error => console.error(error));
}

bootClassicEditor('#short_description', @json(route('admin.posts.upload-editor')));
bootClassicEditor('#product_details', @json(route('admin.products.upload-editor')));
</script>
@stack('scripts')
</body>
</html>
