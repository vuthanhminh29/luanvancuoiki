<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Atelier Optique - Thương hiệu gọng kính, tròng kính chính hãng và thử kính AI 3D trực tiếp hàng đầu.')">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', 'Atelier Optique - Thương hiệu gọng kính, tròng kính chính hãng và thử kính AI 3D trực tiếp hàng đầu.')">
    <meta property="og:image" content="@yield('og_image', asset('upload/logo/logo-1.png'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/elegant-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/slicknav.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">

    <style>
        body, html, h1, h2, h3, h4, h5, h6, p, input, button, select, textarea { font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important; }
        .breadcrumb-option { padding-top: 15px; padding-bottom: 15px; }
        .fa-search { color: black !important; }
        .watch-action-btn:hover i,
        .watch-action-btn:hover .arrow_expand { color: white !important; }
        .btn-primary { color: #fff; background-color: #1b4ea0; border-color: #1b4ea0; }
        .shop__cart__table tbody tr .cart__price,
        .shop__cart__table tbody tr .cart__total,
        .product__item.sale .product__item__text .product__price,
        .product__details__price { color: #c41e3a; }
        .header__right__widget li a .tip { background: #c41e3a; }
        .arrow_expand { color: black; }
        .arrow_expand:hover { color: white; }
        .header__logo { padding: 15px 0; }
        .search-dropdown {
            display: none; position: absolute; top: 100%; right: 15px; width: 100%; max-width: 450px;
            background: #ffffff; padding: 20px; box-shadow: 0 5px 20px rgba(0,0,0,.1);
            z-index: 1000; border: 1px solid #ebebeb; margin-top: 10px;
        }
        .search-dropdown.active { display: block; animation: fadeInDown .3s ease; }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .search-dropdown form { position: relative; display: flex; align-items: center; border: 1px solid #e1e1e1; background: #fff; }
        .search-dropdown input { width: 100%; height: 50px; padding: 0 60px 0 15px; border: none; outline: none; font-size: 14px; color: #444; }
        .search-dropdown button {
            position: absolute; right: 0; top: 0; width: 50px; height: 50px; background: transparent;
            border: none; cursor: pointer; font-size: 20px; color: #111; display: flex; align-items: center; justify-content: center;
        }
        .flash {
            max-width: 1140px; margin: 16px auto; padding: 12px 16px;
            border: 1px solid #e3d7c8; background: #fff8ef; color: #111; font-weight: 600;
        }
        .flash.error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
        .footer { background-color: #111827; color: #d1d5db; padding: 4rem 0 0; position: relative; z-index: 1; }
        .footer__about, .footer__widget, .footer__newsletter { margin-bottom: 2rem; }
        .footer__description { font-size: .9375rem; line-height: 1.6; color: #9ca3af; margin-bottom: 1.5rem; }
        .footer__social { display: flex; gap: .75rem; }
        .footer__social a {
            display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem;
            background-color: #1f2937; color: #d1d5db; border-radius: .375rem; transition: all .2s; font-size: 1rem;
        }
        .footer__social a:hover { background-color: #374151; color: #fff; transform: translateY(-2px); }
        .footer__widget h6, .footer__newsletter h6 {
            color: #fff; font-size: .875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 1.25rem;
        }
        .footer__widget ul { list-style: none; padding: 0; margin: 0; }
        .footer__widget ul li { margin-bottom: .75rem; }
        .footer__widget ul li a { color: #9ca3af; font-size: .9375rem; text-decoration: none; transition: color .2s; display: inline-block; }
        .footer__widget ul li a:hover { color: #fff; padding-left: .25rem; }
        .newsletter__description { font-size: .875rem; color: #9ca3af; margin-bottom: 1rem; }
        .newsletter__input-group { display: flex; gap: .5rem; }
        .newsletter__input-group input {
            flex: 1; padding: .75rem 1rem; background-color: #1f2937; border: 1px solid #374151;
            border-radius: .375rem; color: #fff; font-size: .9375rem; transition: all .2s;
        }
        .newsletter__button {
            padding: .75rem 1.25rem; background-color: #fff; color: #111827; border: none; border-radius: .375rem;
            font-size: 1rem; font-weight: 500; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s;
        }
        .footer__payment { margin-top: 1.5rem; }
        .payment__label { display: block; font-size: .875rem; color: #9ca3af; margin-bottom: .75rem; }
        .payment__icons { display: flex; flex-wrap: wrap; gap: .5rem; }
        .payment__icons img { height: 28px; width: auto; filter: grayscale(100%) brightness(1.2); opacity: .7; transition: all .2s; }
        .payment__icons img:hover { filter: grayscale(0); opacity: 1; }
        @media (max-width: 767px) {
            .footer { padding: 2.5rem 0 0; }
            .newsletter__input-group { flex-direction: column; }
            .newsletter__button { width: 100%; }
            .footer__social, .payment__icons { justify-content: center; }
        }
    </style>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ file_exists(public_path('css/theme.css')) ? filemtime(public_path('css/theme.css')) : time() }}">
</head>
<body>
    @php
        $cartTotalQuantity = array_sum(array_map('intval', session('cart', [])));
    @endphp
    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="offcanvas__close">+</div>
        <ul class="offcanvas__widget">
            <li><span class="icon_search search-switch"></span></li>
            <li>
                <a href="{{ route('cart.index') }}">
                    <span class="icon_bag_alt"></span>
                    <div class="tip">{{ $cartTotalQuantity }}</div>
                </a>
            </li>
        </ul>
        <div class="offcanvas__logo">
            <a href="{{ route('home') }}"><img src="{{ asset('upload/logo/logo-1.png') }}" alt="{{ config('app.name') }}"></a>
        </div>
        <div id="mobile-menu-wrap"></div>
        <div class="offcanvas__auth">
            @auth
                <a href="{{ route('account.index') }}">{{ auth()->user()->full_name }}</a>
            @else
                <a href="{{ route('login') }}">Đăng nhập</a>
                <a href="{{ route('register') }}">Đăng ký</a>
            @endauth
        </div>
    </div>

    <div class="optical-utility-bar" style="background: #0a3d42; color: #ffffff; padding: 8px 0; font-size: 13px;">
        <div class="container" style="max-width: 1280px;">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-4">
                    <span><i class="fas fa-user-md" style="color: #4ade80;"></i> Đo thị lực chuyên sâu</span>
                    <span><i class="fas fa-glasses" style="color: #4ade80;"></i> Trải nghiệm kính AI</span>
                    <span><i class="fas fa-shield-alt" style="color: #4ade80;"></i> Bảo hành 12 tháng</span>
                </div>
                <div class="d-flex align-items-center gap-4">
                    <span><i class="fas fa-headset"></i> Hotline: 1900 6789</span>
                    <a href="{{ route('pages.contact') }}" style="color: #ffffff;"><i class="fas fa-store"></i> Cửa hàng</a>
                </div>
            </div>
        </div>
    </div>

    <header class="header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 1000;">
        <div class="container" style="max-width: 1280px; position: relative;">
            <div class="row align-items-center py-2">
                <div class="col-xl-3 col-lg-3">
                    <div class="header__logo">
                        <a href="{{ route('home') }}" class="d-flex align-items-center gap-2" style="text-decoration: none;">
                            <img src="{{ asset('upload/logo/logo-1.png') }}" alt="{{ config('app.name') }}" style="max-height: 48px;">
                            <div style="line-height: 1.1;">
                                <strong style="font-size: 16px; letter-spacing: 0.08em; color: #0a3d42; display: block; font-family: 'Be Vietnam Pro', sans-serif;">ATELIER OPTIQUE</strong>
                                <span style="font-size: 9px; letter-spacing: 0.15em; color: #64748b; text-transform: uppercase;">PRECISION VISION</span>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6">
                    <nav class="header__menu">
                        <ul class="d-flex align-items-center justify-content-center gap-4 m-0 p-0" style="list-style: none;">
                            <li><a href="{{ route('home') }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Trang chủ</a></li>
                            <li><a href="{{ route('products.index', ['category' => 'gong-kinh']) }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Gọng kính</a></li>
                            <li><a href="{{ route('style.lens-selector') }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Tròng kính</a></li>
                            <li><a href="{{ route('style.face-shape') }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Tìm dáng kính</a></li>
                            <li><a href="{{ route('appointments.create') }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Đo thị lực</a></li>
                            <li><a href="{{ route('blog.index') }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Bài viết</a></li>
                            <li><a href="{{ route('pages.contact') }}" style="font-weight: 600; color: #1e293b; font-size: 14px;">Liên hệ</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-xl-3 col-lg-3">
                    <div class="header__right d-flex align-items-center justify-content-end gap-3" style="padding: 0;">
                        <span class="icon_search search-switch" aria-label="Tìm kiếm" style="font-size: 18px; cursor: pointer; color: #334155;"></span>
                        <a href="{{ route('account.index') }}" style="color: #334155; font-size: 18px;" title="Yêu thích"><i class="far fa-heart"></i></a>
                        @auth
                            <a href="{{ route('account.index') }}" class="header__account" style="color: #334155; font-size: 18px;" title="{{ auth()->user()->full_name }}" aria-label="Tài khoản của tôi">
                                <i class="far fa-user"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="header__account" style="color: #334155; font-size: 18px;" title="Đăng nhập" aria-label="Đăng nhập">
                                <i class="far fa-user"></i>
                            </a>
                        @endauth
                        <a id="cart-mini" href="{{ route('cart.index') }}" aria-label="Giỏ hàng" class="position-relative" style="color: #334155; font-size: 18px;">
                            <span class="icon_bag_alt"></span>
                            <span class="badge badge-danger position-absolute" style="top: -8px; right: -10px; background: #0e5c63; border-radius: 999px; font-size: 10px; padding: 2px 6px;">{{ $cartTotalQuantity }}</span>
                        </a>
                        <a href="{{ route('tryon') }}" class="btn text-white d-inline-flex align-items-center gap-2" style="background: #0a3d42; border-radius: 6px; font-weight: 600; font-size: 13px; padding: 8px 14px;">
                            <i class="fas fa-glasses" aria-hidden="true"></i> TRẢI NGHIỆM KÍNH AI
                        </a>
                    </div>
                </div>
            </div>
            <div class="canvas__open"><i class="fa fa-bars"></i></div>
            <div class="search-dropdown" id="header-search-dropdown">
                <form action="{{ route('products.index') }}" method="get">
                    <input type="text" name="q" id="header-search-input" placeholder="Nhập tên gọng kính, thương hiệu...">
                    <button type="submit" aria-label="Gửi tìm kiếm"><span class="icon_search"></span></button>
                </form>
            </div>
        </div>
    </header>

    <!-- Toast Notification Container -->
    <div id="toastContainer" style="position: fixed; top: 90px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; max-width: 380px; width: calc(100% - 48px); pointer-events: none;"></div>

    <main>
        @yield('content')
    </main>

    <div style="border: 1px solid #e5e7eb;"></div>

    <footer class="footer" style="background: #072b2d; color: #94a3b8; padding: 72px 0 0; font-size: 13px;">
        <div class="container" style="max-width: 1280px;">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="d-flex align-items-center gap-2 mt-4 mb-3">
                        {{-- Logo gốc là ảnh nền trắng đục; lọc brightness/invert biến nó thành
                             ô trắng đặc trên nền tối. Dùng ký hiệu gọng kính vẽ thẳng bằng SVG
                             để ăn theo màu chữ, không phụ thuộc file ảnh. --}}
                        <svg width="34" height="18" viewBox="0 0 34 18" fill="none" aria-hidden="true" style="flex: 0 0 auto;">
                            <path d="M1 4h32M4 4c-1 0-2 1-2 3.5S3.5 13 7 13s5.5-2 5.5-5c0-1.8-1-2.6-3-2.6S4 5.4 4 4Zm26 0c1 0 2 1 2 3.5S29.5 13 26 13s-5.5-2-5.5-5c0-1.8 1-2.6 3-2.6S30 5.4 30 4Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 8h8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        <strong style="font-size: 16px; letter-spacing: 0.08em; color: #ffffff;">ATELIER OPTIQUE</strong>
                    </div>
                    <p style="color: #cbd5e1; line-height: 1.6; margin-bottom: 16px;">
                        Atelier Optique &bull; Nhà chế tác gọng kính & Phòng khám đo thị lực cao cấp.
                    </p>
                    <ul class="list-unstyled d-flex flex-column gap-2" style="color: #cbd5e1;">
                        <li><i class="fas fa-map-marker-alt" style="color: #4ade80; width: 20px;"></i> 123 Nguyễn Trãi, P. Bến Thành, Q.1, TP.HCM</li>
                        <li><i class="fas fa-phone" style="color: #4ade80; width: 20px;"></i> 1900 6789</li>
                        <li><i class="fas fa-envelope" style="color: #4ade80; width: 20px;"></i> hello@atelieroptique.vn</li>
                    </ul>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" style="color: #ffffff; font-size: 16px;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" style="color: #ffffff; font-size: 16px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: #ffffff; font-size: 16px;"><i class="fab fa-tiktok"></i></a>
                        <a href="#" style="color: #ffffff; font-size: 16px;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-3 mt-4 mb-4">
                    <h6 style="color: #ffffff; font-weight: 700; font-size: 14px; letter-spacing: 0.05em; margin-bottom: 16px; text-transform: uppercase;">KHÁM PHÁ</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2" style="color: #cbd5e1;">
                        <li><a href="{{ route('products.index', ['category' => 'nam']) }}" style="color: inherit; text-decoration: none;">Gọng kính nam</a></li>
                        <li><a href="{{ route('products.index', ['category' => 'nu']) }}" style="color: inherit; text-decoration: none;">Gọng kính nữ</a></li>
                        <li><a href="{{ route('products.index', ['category' => 'kinh-ram']) }}" style="color: inherit; text-decoration: none;">Kính râm</a></li>
                        <li><a href="{{ route('products.index', ['category' => 'trong-kinh']) }}" style="color: inherit; text-decoration: none;">Tròng kính</a></li>
                        <li><a href="{{ route('products.index') }}" style="color: inherit; text-decoration: none;">Phụ kiện</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-3 mt-4 mb-4">
                    <h6 style="color: #ffffff; font-weight: 700; font-size: 14px; letter-spacing: 0.05em; margin-bottom: 16px; text-transform: uppercase;">HỖ TRỢ</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2" style="color: #cbd5e1;">
                        <li><a href="{{ route('appointments.create') }}" style="color: inherit; text-decoration: none;">Đặt lịch đo thị lực</a></li>
                        <li><a href="{{ route('style.face-shape') }}" style="color: inherit; text-decoration: none;">Tìm dáng kính phù hợp</a></li>
                        <li><a href="{{ route('style.lens-selector') }}" style="color: inherit; text-decoration: none;">Chọn tròng kính phù hợp</a></li>
                        <li><a href="{{ route('pages.support') }}" style="color: inherit; text-decoration: none;">Chính sách bảo hành</a></li>
                        <li><a href="{{ route('returns.index') }}" style="color: inherit; text-decoration: none;">Chính sách đổi trả</a></li>
                        <li><a href="{{ route('checkout.index') }}" style="color: inherit; text-decoration: none;">Thanh toán & giao hàng</a></li>
                        <li><a href="{{ route('pages.support') }}" style="color: inherit; text-decoration: none;">Câu hỏi thường gặp</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-12 mt-4 mb-4">
                    <h6 style="color: #ffffff; font-weight: 700; font-size: 14px; letter-spacing: 0.05em; margin-bottom: 16px; text-transform: uppercase;">ĐĂNG KÝ NHẬN TIN</h6>
                    <p style="color: #cbd5e1; margin-bottom: 12px;">Nhận ưu đãi đặc quyền và kiến thức chăm sóc mắt hữu ích.</p>
                    <form action="#" class="d-flex gap-2 mb-2">
                        <input type="email" placeholder="Nhập email của bạn..." required style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; color: #ffffff; padding: 10px 14px; flex: 1; font-size: 13px;">
                        <button type="submit" class="btn" style="background: #0e5c63; color: #ffffff; border-radius: 4px; padding: 0 16px;"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <small style="color: #94a3b8; font-size: 11px;">Chúng tôi cam kết bảo mật thông tin của bạn.</small>
                </div>
            </div>

            <hr style="border-color: rgba(255,255,255,0.1); margin: 40px 0 0;">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 text-muted" style="font-size: 12px; padding: 20px 0 28px;">
                <span>&copy; {{ date('Y') }} Atelier Optique. All rights reserved.</span>
                <div class="d-flex flex-wrap gap-4">
                    <a href="{{ route('pages.support') }}" style="color: inherit; text-decoration: none;">Điều khoản sử dụng</a>
                    <a href="{{ route('pages.support') }}" style="color: inherit; text-decoration: none;">Chính sách bảo mật</a>
                </div>
                <span>Thiết kế bởi Atelier Optique Studio</span>
            </div>
        </div>
    </footer>

    <div class="search-model">
        <div class="h-100 d-flex align-items-center justify-content-center">
            <div class="search-close-switch">+</div>
            <form action="{{ route('products.index') }}" method="get" class="search-model-form">
                <input type="search" name="q" id="search-input" placeholder="TÌM KIẾM.....">
            </form>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTopBtn" type="button" aria-label="Trở lại đầu trang" class="btn text-white position-fixed rounded-circle shadow-lg" style="bottom: 28px; right: 28px; width: 44px; height: 44px; background: #0a3d42; display: none; z-index: 1050; border: none; align-items: center; justify-content: center; transition: opacity 0.3s ease, transform 0.2s ease;">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/mixitup.min.js') }}"></script>
    <script src="{{ asset('js/jquery.countdown.min.js') }}"></script>
    <script src="{{ asset('js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script>
        window.showToast = function (message, type, title) {
            type = type || 'success';
            var container = document.getElementById('toastContainer');
            if (!container) return;

            var isSuccess = type === 'success';
            var bg = isSuccess ? '#ffffff' : '#ffffff';
            var border = isSuccess ? '#0e5c63' : '#ef4444';
            var iconColor = isSuccess ? '#0e5c63' : '#ef4444';
            var iconClass = isSuccess ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            var defaultTitle = isSuccess ? 'Thành công' : 'Thông báo';

            var toast = document.createElement('div');
            toast.style.cssText = 'background:' + bg + '; border-left: 4px solid ' + border + '; color: #1e293b; pointer-events: auto; transform: translateX(120%); transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease; opacity: 0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border-radius: 8px; padding: 14px 16px;';

            toast.innerHTML = `
                <div style="display: flex; align-items: start; gap: 12px;">
                    <i class="${iconClass}" style="color: ${iconColor}; font-size: 18px; margin-top: 2px; flex-shrink: 0;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">${title || defaultTitle}</strong>
                        <span style="font-size: 13px; line-height: 1.4; color: #334155;">${message}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 16px; color: #94a3b8; cursor: pointer; padding: 0; line-height: 1; margin-left: 4px;">&times;</button>
                </div>
            `;

            container.appendChild(toast);

            requestAnimationFrame(function () {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            });

            setTimeout(function () {
                toast.style.transform = 'translateX(120%)';
                toast.style.opacity = '0';
                setTimeout(function () {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 350);
            }, 4000);
        };

        document.addEventListener('DOMContentLoaded', function () {
            var backToTopBtn = document.getElementById('backToTopBtn');
            if (backToTopBtn) {
                window.addEventListener('scroll', function () {
                    if (window.scrollY > 300) {
                        backToTopBtn.style.display = 'flex';
                    } else {
                        backToTopBtn.style.display = 'none';
                    }
                });
                backToTopBtn.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            @if (session('success'))
                showToast(@json(session('success')), 'success', 'Giỏ hàng & Đơn hàng');
            @endif

            @if (session('error'))
                showToast(@json(session('error')), 'error', 'Thông báo');
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
