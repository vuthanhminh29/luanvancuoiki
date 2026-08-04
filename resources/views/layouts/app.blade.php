<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link href="https://fonts.googleapis.com/css2?family=Cookie&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/elegant-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/slicknav.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tryon-ai.css') }}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">

    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
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
    <link rel="stylesheet" href="{{ asset('css/ui-human.css') }}?v={{ file_exists(public_path('css/ui-human.css')) ? filemtime(public_path('css/ui-human.css')) : time() }}">
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

    <header class="header">
        <div class="container" style="max-width: 1280px; position: relative;">
            <div class="row">
                <div class="col-xl-3 col-lg-2">
                    <div class="header__logo">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('upload/logo/logo-1.png') }}" alt="{{ config('app.name') }}">
                        </a>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-7">
                    <nav class="header__menu">
                        <ul>
                            <li><a href="{{ route('home') }}">TRANG CHỦ</a></li>
                            <li><a href="{{ route('products.index') }}">Sản phẩm</a></li>
                            @foreach ($headerProductLinks ?? [] as $headerProductLink)
                                <li><a href="{{ $headerProductLink['url'] }}">{{ $headerProductLink['label'] }}</a></li>
                            @endforeach
                            <li><a href="{{ route('blog.index') }}">Bài viết</a></li>
                            <li><a href="{{ route('pages.contact') }}">LIÊN HỆ</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3">
                    <div class="header__right">
                        <div class="header__right__auth">
                            @auth
                                <a href="{{ route('account.index') }}">{{ auth()->user()->full_name }}</a>
                            @else
                                <a href="{{ route('login') }}">Đăng nhập</a>
                            @endauth
                        </div>
                        <ul class="header__right__widget">
                            <li><span class="icon_search search-switch"></span></li>
                            <li>
                                <a id="cart-mini" href="{{ route('cart.index') }}">
                                    <span class="icon_bag_alt"></span>
                                    <div class="tip">{{ $cartTotalQuantity }}</div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="canvas__open"><i class="fa fa-bars"></i></div>
            <div class="search-dropdown" id="header-search-dropdown">
                <form action="{{ route('products.index') }}" method="get">
                    <input type="text" name="q" id="header-search-input" placeholder="Nhập tên sản phẩm cần tìm">
                    <button type="submit"><span class="icon_search"></span></button>
                </form>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <div style="border: 1px solid #e5e7eb;"></div>

    <footer class="footer">
        <div class="container">
            <div class="footer__top">
                <a href="{{ route('home') }}" class="footer__brand" aria-label="{{ config('app.name') }}">
                    <img src="{{ asset('upload/logo/logo-1.png') }}" alt="{{ config('app.name') }}">
                    <span>WARFARER</span>
                </a>
                <div class="footer__badges" aria-label="Cam kết dịch vụ">
                    <span><i class="fa fa-shipping-fast"></i> Giao hàng nhanh</span>
                    <span><i class="fa fa-sync-alt"></i> Đổi trả rõ ràng</span>
                    <span><i class="fa fa-lock"></i> Thanh toán an toàn</span>
                </div>
            </div>

            <div class="row footer__grid">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="footer__about">
                        <h6>WARFARER</h6>
                        <p class="footer__description">Chuyên kính mát, gọng kính và phụ kiện mắt kính chính hãng. Tư vấn sản phẩm phù hợp nhu cầu sử dụng hằng ngày.</p>
                        <ul class="footer__contact">
                            <li><i class="fa fa-map-marker-alt"></i> 828 Sư Vạn Hạnh, Phường 13, Quận 10, TP.HCM</li>
                            <li><i class="fa fa-phone"></i> 0909 000 888</li>
                            <li><i class="fa fa-envelope"></i> support@warfarer.vn</li>
                        </ul>
                        <div class="footer__social">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <h6>Đường dẫn</h6>
                        <ul>
                            <li><a href="{{ route('products.index') }}">Cửa hàng</a></li>
                            <li><a href="{{ route('blog.index') }}">Blogs</a></li>
                            <li><a href="{{ route('pages.contact') }}">Liên hệ</a></li>
                            <li><a href="{{ route('pages.support') }}">Trung tâm hỗ trợ</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <div class="footer__widget">
                        <h6>Chính sách</h6>
                        <ul>
                            <li><a href="{{ route('pages.support') }}">Vận chuyển</a></li>
                            <li><a href="{{ route('checkout.index') }}">Thanh toán</a></li>
                            <li><a href="{{ route('returns.index') }}">Hoàn/đổi</a></li>
                            <li><a href="{{ route('account.orders.index') }}">Theo dõi đơn hàng</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 col-sm-12">
                    <div class="footer__newsletter">
                        <h6>Đăng ký nhận tin</h6>
                        <p class="newsletter__description">Nhận thông tin về sản phẩm mới và ưu đãi đặc biệt</p>
                        <form action="#" class="newsletter__form">
                            <div class="newsletter__input-group">
                                <input type="email" placeholder="Nhập email của bạn" required>
                                <button type="submit" class="newsletter__button"><i class="fa fa-paper-plane"></i></button>
                            </div>
                        </form>
                        <div class="footer__payment">
                            <span class="payment__label">Phương thức thanh toán</span>
                            <div class="payment__icons">
                                <img src="{{ asset('img/payment/payment-1.png') }}" alt="Visa">
                                <img src="{{ asset('img/payment/payment-2.png') }}" alt="Mastercard">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer__bottom">
                <span>© {{ date('Y') }} WARFARER. Dự án bán kính mắt.</span>
                <a href="{{ route('account.orders.index') }}">Theo dõi đơn hàng</a>
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
    @stack('scripts')
</body>
</html>
