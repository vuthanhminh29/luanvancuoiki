@extends('layouts.app')

@section('title', 'Trang chủ - ' . config('app.name'))

@php
    $homeDesignVersion = '20260630-100fix-3';
    $homeLayout = $homeLayout ?? collect();
    $homeSectionStyle = function (string $key) use ($homeLayout) {
        $section = $homeLayout[$key] ?? null;

        if (! $section) {
            return '';
        }

        $styles = ['order:' . (int) $section->sort_order];

        if ((int) $section->status !== 1) {
            $styles[] = 'display:none';
        }

        return implode(';', $styles);
    };
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/home/home.css') }}?v={{ $homeDesignVersion }}">
    <link rel="stylesheet" href="{{ asset('css/views/home/banner.css') }}?v={{ $homeDesignVersion }}">
    <link rel="stylesheet" href="{{ asset('css/views/home/categories.css') }}?v={{ $homeDesignVersion }}">
    <link rel="stylesheet" href="{{ asset('css/views/home/brands.css') }}?v={{ $homeDesignVersion }}">
@endpush

@section('content')
    <div class="home-layout-stack" style="display:flex;flex-direction:column;">
    <section class="watch-promo-banner my-3" style="{{ $homeSectionStyle('banner') }}">
        <div class="row">
            <div class="col-lg-12 col-sm-12">
                <div id="header-carousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner view-home-banner-inline-1">
                        @forelse ($banners as $index => $banner)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <a href="{{ $banner->link_url ?: '#' }}">
                                    <img class="img-fluid" src="{{ $banner->image_src }}" alt="{{ $banner->title }}">
                                </a>
                            </div>
                        @empty
                            <div class="carousel-item active">
                                <img class="img-fluid" src="{{ asset('upload/banner/banner-kinh-1.jpg') }}" alt="Banner">
                            </div>
                        @endforelse
                    </div>
                    <a class="carousel-control-prev" href="#header-carousel" data-slide="prev">
                        <div class="btn btn-dark view-home-banner-inline-2">
                            <span class="carousel-control-prev-icon mb-n2"></span>
                        </div>
                    </a>
                    <a class="carousel-control-next" href="#header-carousel" data-slide="next">
                        <div class="btn btn-dark view-home-banner-inline-2">
                            <span class="carousel-control-next-icon mb-n2"></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="watch-featured-cats-section" style="{{ $homeSectionStyle('categories') }}">
        <div class="watch-container">
            <div class="watch-section-header">
                <h2 class="watch-section-title">DANH MỤC NỔI BẬT</h2>
            </div>
            <div class="watch-featured-cats-container">
                <button class="watch-cat-arrow prev" onclick="scrollFeaturedCats('prev')"><i class="fas fa-chevron-left"></i></button>
                <button class="watch-cat-arrow next" onclick="scrollFeaturedCats('next')"><i class="fas fa-chevron-right"></i></button>
                <div id="featured-cats-slider" class="watch-featured-cats-grid">
                    @foreach ($featuredCategories as $index => $category)
                        @php
                            $image = str_starts_with((string) $category->slug, 'kinh-mat')
                                ? 'https://matkinhsaigon.com.vn/img/featured-category/1669976518-Kinh_Mat.png'
                                : 'https://matkinhsaigon.com.vn/img/featured-category/1669976593-Gong_Kinh_1.png';
                        @endphp
                        <a href="{{ route('products.index', ['category' => $category->id]) }}" class="watch-cat-card view-home-categories-inline-{{ $index + 1 }}">
                            <div class="watch-cat-img-box">
                                <img src="{{ $image }}" alt="{{ $category->name }}">
                            </div>
                            <h3 class="watch-cat-title">{{ mb_strtoupper($category->name, 'UTF-8') }}</h3>
                            <span class="watch-cat-btn">Xem ngay</span>
                        </a>
                    @endforeach
                    <a href="{{ route('products.index') }}" class="watch-cat-card view-home-categories-inline-6">
                        <div class="watch-cat-img-box">
                            <img src="https://matkinhsaigon.com.vn/img/featured-category/1669985298-Khuyen_Mai_2.png" alt="Khuyến mãi">
                        </div>
                        <h3 class="watch-cat-title">KHUYẾN MÃI</h3>
                        <span class="watch-cat-btn">Xem ngay</span>
                    </a>
                    <a href="{{ route('pages.contact') }}" class="watch-cat-card view-home-categories-inline-7">
                        <div class="watch-cat-img-box">
                            <img src="https://matkinhsaigon.com.vn/img/featured-category/1669978174-He_Thong.png" alt="Hệ thống">
                        </div>
                        <h3 class="watch-cat-title">HỆ THỐNG</h3>
                        <span class="watch-cat-btn">Xem ngay</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="watch-products-section" style="{{ $homeSectionStyle('new_products') }}">
        <div class="watch-container">
            <div class="watch-section-header">
                <h2 class="watch-section-title">SẢN PHẨM MỚI</h2>
            </div>
            <div class="watch-product-slider-container">
                <button class="watch-product-slider-arrow prev" onclick="scrollProductSlider('new-products-track', 'prev')"><i class="fas fa-chevron-left"></i></button>
                <button class="watch-product-slider-arrow next" onclick="scrollProductSlider('new-products-track', 'next')"><i class="fas fa-chevron-right"></i></button>
                <div class="watch-product-slider-window">
                    <div id="new-products-track" class="watch-products-grid">
                        @foreach ($newProducts as $product)
                            @include('partials.watch-product-card', ['product' => $product])
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="watch-view-all">
                <a href="{{ route('products.index') }}" class="watch-view-all-btn">Xem tất cả</a>
            </div>
        </div>
    </div>

    <div class="watch-products-section" style="{{ $homeSectionStyle('best_sellers') }}">
        <div class="watch-container">
            <div class="watch-section-header">
                <h2 class="watch-section-title">SẢN PHẨM BÁN CHẠY</h2>
            </div>
            <div class="watch-product-slider-container">
                <button class="watch-product-slider-arrow prev" onclick="scrollProductSlider('best-seller-track', 'prev')"><i class="fas fa-chevron-left"></i></button>
                <button class="watch-product-slider-arrow next" onclick="scrollProductSlider('best-seller-track', 'next')"><i class="fas fa-chevron-right"></i></button>
                <div class="watch-product-slider-window">
                    <div id="best-seller-track" class="watch-products-grid">
                        @foreach ($featuredProducts as $product)
                            @include('partials.watch-product-card', ['product' => $product])
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="watch-view-all">
                <a href="{{ route('products.index') }}" class="watch-view-all-btn">Xem tất cả</a>
            </div>
        </div>
    </div>

    <section class="watch-brand-section" style="{{ $homeSectionStyle('brands') }}">
        <div class="watch-container">
            <div class="wrapper-brand">
                <div class="section-heading">
                    <h4 class="watch-posts-main-title view-home-brands-inline-1">Thương hiệu</h4>
                </div>
                <div class="watch-brand-slider-container">
                    <button class="watch-brand-arrow prev" onclick="scrollBrands('prev')"><i class="fas fa-chevron-left"></i></button>
                    <button class="watch-brand-arrow next" onclick="scrollBrands('next')"><i class="fas fa-chevron-right"></i></button>
                    <div id="brand-slider-track" class="section-content brand-grid">
                        @foreach ([
                            ['CARTIER', 'https://file.hstatic.net/200000689681/file/cartier_logo-55_e88603918eb14d158164557bcc346560.png'],
                            ['BOLON', 'https://file.hstatic.net/200000689681/file/bolon_logo-64-64-65.png'],
                            ['BURBERRY', 'https://file.hstatic.net/200000689681/file/burberry_logo_black.png'],
                            ['PUMA', 'https://file.hstatic.net/200000689681/file/puma_logo-46.png'],
                            ['COACH', 'https://file.hstatic.net/200000689681/file/coach_logo-19.png'],
                            ['DOLCE & GABBANA', 'https://file.hstatic.net/200000689681/file/d_g_logo_black.png'],
                            ['ARMANI EXCHANGE', 'https://file.hstatic.net/200000689681/file/ax_logo-04.png'],
                            ['GUCCI', 'https://file.hstatic.net/200000689681/file/gucci_logo-57.png'],
                        ] as [$brandName, $brandLogo])
                            <div class="item-brand">
                                <img alt="{{ $brandName }}" src="{{ $brandLogo }}" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($posts->count())
        @php($featuredPost = $posts->first())
        <section class="watch-posts-section" style="{{ $homeSectionStyle('news') }}">
            <div class="watch-container">
                <div class="watch-posts-grid">
                    <div class="watch-posts-col">
                        <h4 class="watch-posts-main-title">BÀI VIẾT NỔI BẬT</h4>
                        <div id="featured-post-container" class="watch-featured-card">
                            <div class="watch-featured-img-group">
                                <div class="watch-featured-img-wrapper">
                                    <img id="featured-img" src="{{ $featuredPost->image_url }}" alt="{{ $featuredPost->title }}" class="watch-featured-img">
                                </div>
                            </div>
                            <div class="watch-featured-content">
                                <h3 id="featured-title" class="watch-featured-title">
                                    <a href="{{ route('blog.show', $featuredPost) }}">{{ $featuredPost->title }}</a>
                                </h3>
                                <p id="featured-excerpt" class="watch-featured-excerpt">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($featuredPost->summary ?: $featuredPost->content), 150) }}
                                </p>
                                <div id="featured-date" class="watch-featured-date">
                                    Ngày đăng {{ $featuredPost->published_at?->format('d/m/Y') }}
                                </div>
                            </div>
                            <div class="watch-featured-nav">
                                <button onclick="prevFeatured()" class="watch-nav-btn"><i class="fa fa-chevron-left"></i></button>
                                <span id="featured-counter" class="watch-nav-counter">1/{{ $posts->count() }}</span>
                                <button onclick="nextFeatured()" class="watch-nav-btn"><i class="fa fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="watch-posts-col">
                        <h4 class="watch-posts-main-title">XEM NHIỀU NHẤT</h4>
                        <div class="watch-most-viewed-list">
                            @foreach ($posts->skip(1) as $post)
                                <div class="watch-most-viewed-item">
                                    <div class="watch-most-viewed-img-wrapper">
                                        <a href="{{ route('blog.show', $post) }}">
                                            <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="watch-most-viewed-img">
                                        </a>
                                    </div>
                                    <div class="watch-most-viewed-info">
                                        <h5 class="watch-most-viewed-title">
                                            <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                                        </h5>
                                        <p class="watch-most-viewed-excerpt">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($post->summary ?: $post->content), 80) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="watch-services-section" style="{{ $homeSectionStyle('services') }}">
        <div class="watch-container">
            <div class="watch-services-grid">
                <div class="watch-service-item">
                    <i class="fa fa-car watch-service-icon"></i>
                    <h6 class="watch-service-title">Miễn phí vận chuyển</h6>
                    <p class="watch-service-desc">Đơn hàng trên 400.000&#8363;</p>
                </div>
                <div class="watch-service-item">
                    <i class="fa fa-money watch-service-icon"></i>
                    <h6 class="watch-service-title">Đảm bảo hoàn tiền</h6>
                    <p class="watch-service-desc">Nếu sản phẩm có vấn đề</p>
                </div>
                <div class="watch-service-item">
                    <i class="fa fa-support watch-service-icon"></i>
                    <h6 class="watch-service-title">Hỗ trợ trực tuyến 24/7</h6>
                    <p class="watch-service-desc">Hỗ trợ chuyên dụng</p>
                </div>
                <div class="watch-service-item">
                    <i class="fa fa-headphones watch-service-icon"></i>
                    <h6 class="watch-service-title">Thanh toán an toàn</h6>
                    <p class="watch-service-desc">Thanh toán an toàn 100%</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-support" style="{{ $homeSectionStyle('support') }}">
        <div class="watch-container watch-support-slider-container">
            <button class="watch-support-arrow prev" onclick="scrollProductSlider('support-track', 'prev')"><i class="fas fa-chevron-left"></i></button>
            <button class="watch-support-arrow next" onclick="scrollProductSlider('support-track', 'next')"><i class="fas fa-chevron-right"></i></button>
            <div class="watch-support-slider-window">
                <div id="support-track" class="flex-container">
                    @foreach ([
                        ['sp-1.png', 'GIAO HÀNG TOÀN QUỐC', 'Hồ Chí Minh: Từ 1-2 ngày. Tỉnh khác: Từ 2-3 ngày'],
                        ['sp-2.png', 'ĐO KHÁM MẮT MIỄN PHÍ', 'Đội ngũ chuyên khoa khúc xạ và thiết bị hiện đại'],
                        ['sp-3.png', 'THỜI GIAN BẢO HÀNH', 'Thị lực 30 ngày. Sản phẩm đến 24 tháng theo hãng'],
                        ['sp-4.png', 'MIỄN PHÍ TRỌN ĐỜI', 'Vệ sinh gọng, thay ve mũi, nắn kính miễn phí'],
                    ] as [$icon, $title, $desc])
                        <div class="column-wrap active-ani">
                            <div class="support-inner">
                                <div class="inner-icon">
                                    <img class="dt-width-auto" fetchpriority="low" decoding="async" src="{{ asset('upload/support/' . $icon) }}" alt="{{ $title }}">
                                </div>
                                <div class="inner-content">
                                    <h3>{{ $title }}</h3>
                                    <p>{{ $desc }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    </div>
@endsection

@push('scripts')
    <script>
        window.scrollFeaturedCats = function(direction) {
            const slider = document.getElementById('featured-cats-slider');
            if (!slider) return;
            const amount = slider.offsetWidth;
            slider.scrollBy({ left: direction === 'next' ? amount : -amount, behavior: 'smooth' });
        };

        window.scrollProductSlider = function(trackId, direction) {
            const slider = document.getElementById(trackId);
            if (!slider) return;
            const amount = slider.offsetWidth;
            slider.scrollBy({ left: direction === 'next' ? amount : -amount, behavior: 'smooth' });
        };

        window.scrollBrands = function(direction) {
            const slider = document.getElementById('brand-slider-track');
            if (!slider) return;
            const amount = slider.offsetWidth;
            slider.scrollBy({ left: direction === 'next' ? amount : -amount, behavior: 'smooth' });
        };

        window.prevFeatured = window.prevFeatured || function() {};
        window.nextFeatured = window.nextFeatured || function() {};
    </script>
@endpush
