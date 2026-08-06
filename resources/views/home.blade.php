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
    <!-- SECTION 1: Top Optical Experience Flow Bar -->
    <section class="atelier-experience-bar" style="background: var(--paper); border-bottom: 1px solid var(--line); padding: 10px 0; font-size: 13px;">
        <div class="container" style="max-width: 1280px;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span style="font-weight: 700; color: var(--accent-dark); text-transform: uppercase; font-size: 11px; letter-spacing: 0.08em;">TRẢI NGHIỆM THỊ LỰC</span>
                <div class="d-flex align-items-center gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" style="background: var(--line); color: var(--ink-soft); border-radius: 999px; font-size: 10px;">01</span>
                        <div>
                            <strong style="color: var(--ink);">Chọn dáng mặt</strong>
                            <small class="text-muted d-none d-md-inline">&bull; Xác định tỷ lệ khuôn mặt</small>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 10px;"></i>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" style="background: var(--line); color: var(--ink-soft); border-radius: 999px; font-size: 10px;">02</span>
                        <div>
                            <strong style="color: var(--ink);">Tìm gọng phù hợp</strong>
                            <small class="text-muted d-none d-md-inline">&bull; Gợi ý theo phong cách</small>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 10px;"></i>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" style="background: var(--accent); color: var(--paper-card); border-radius: 999px; font-size: 10px;">03</span>
                        <div>
                            <strong style="color: var(--accent);">Thử kính AI</strong>
                            <small class="text-muted d-none d-md-inline">&bull; Xem trực tiếp trên khuôn mặt</small>
                        </div>
                    </div>
                </div>
                <a href="{{ route('tryon') }}" style="color: var(--accent); font-weight: 700; font-size: 12px; text-decoration: none;">BẮT ĐẦU NGAY &rarr;</a>
            </div>
        </div>
    </section>

    <!-- SECTION 2: 12-Column Atelier Hero Grid -->
    {{-- Hero bỏ border-bottom và padding dưới: banner ngay bên dưới cũng nền trắng,
         có đường kẻ ở giữa sẽ thành hai khối trắng rời nhau. Để hai phần liền một mảng,
         phần phân cách thật là dải xám của thanh cam kết phía sau. --}}
    <section class="atelier-hero" style="{{ $homeSectionStyle('banner') }}; background: var(--paper-card); padding: 48px 0 {{ empty($midBanner) ? '48px' : '0' }};">
       <div class="container" style="max-width: 1280px;">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <span style="font-size: 12px; font-weight: 700; color: var(--accent); letter-spacing: 0.1em; text-transform: uppercase;">ATELIER OPTIQUE</span>
                    <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--ink); line-height: 1.2; margin: 12px 0 16px;">
                        Thị lực chính xác.<br>
                        <em style="color: var(--accent); font-style: italic; font-weight: 600;">Diện mạo được định hình.</em>
                    </h1>
                    <p style="color: var(--ink-soft); font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
                        Kết hợp giữa nghệ thuật chế tác gọng kính di sản và công nghệ đo thị lực chuẩn y khoa.
                    </p>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <a href="{{ route('tryon') }}" class="btn text-white" style="background: var(--accent-dark); padding: 12px 24px; font-weight: 700; font-size: 14px; border-radius: 4px;">
                            <i class="fas fa-camera mr-2"></i> THỬ KÍNH AI NGAY
                        </a>
                        <a href="{{ route('products.index') }}" class="btn" style="border: 1px solid var(--line); color: var(--ink); padding: 12px 24px; font-weight: 600; font-size: 14px; border-radius: 4px;">
                            KHÁM PHÁ BỘ SƯ TẬP
                        </a>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="position-relative p-2" style="background: var(--paper); border: 1px solid var(--line); border-radius: 8px;">
                        @php
                            $heroImage = isset($banners) && $banners->count() > 0 ? $banners->first()->image_src : asset('upload/banner/banner-kinh-1.jpg');
                        @endphp
                        <img src="{{ $heroImage }}" alt="Gọng kính Atelier Optique" style="width: 100%; height: 380px; object-fit: cover; border-radius: 6px;" fetchpriority="high" loading="eager">
                        <div class="position-absolute d-flex align-items-center gap-3 px-3 py-2" style="bottom: 20px; right: 20px; background: rgba(255,255,255,0.92); backdrop-filter: blur(8px); border: 1px solid var(--line); border-radius: 6px; font-size: 12px;">
                            <span style="font-family: var(--font-mono); font-weight: 700; color: var(--accent);">52 &square; 18 &mdash; 145</span>
                            <span class="text-muted" style="font-size: 10px;">Eye &bull; Bridge &bull; Temple</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2B: Banner ngang giữa hero và thanh cam kết -->
    @if (!empty($midBanner))
        <section class="atelier-mid-banner" style="background: var(--paper-card); padding: 32px 0 48px;">
            <div class="container" style="max-width: 1280px;">
                <a href="{{ $midBanner->link_url ?: route('products.index') }}"
                   style="display: block; border-radius: 6px; overflow: hidden; border: 1px solid var(--line);">
                    <img src="{{ $midBanner->image_src }}"
                         alt="{{ $midBanner->title ?: 'Ưu đãi Atelier Optique' }}"
                         style="width: 100%; height: 220px; object-fit: cover; display: block;"
                         loading="lazy">
                </a>
            </div>
        </section>
    @endif

    <!-- SECTION 3: Trust Strip Section -->
    <section class="atelier-trust-strip" style="background: var(--paper); border-bottom: 1px solid var(--line); padding: 20px 0;">
        <div class="container" style="max-width: 1280px;">
            <div class="row align-items-center text-center text-md-left">
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3">
                        <i class="fas fa-user-md fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 13px; color: var(--ink); display: block;">Đo thị lực chuẩn y khoa</strong>
                            <small style="color: var(--ink-soft);">Thiết bị ZEISS chính hãng</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3">
                        <i class="fas fa-glasses fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 13px; color: var(--ink); display: block;">Tròng kính chính hãng</strong>
                            <small style="color: var(--ink-soft);">Essilor, Zeiss, Hoya...</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3">
                        <i class="fas fa-shield-alt fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 13px; color: var(--ink); display: block;">Bảo hành 12 tháng</strong>
                            <small style="color: var(--ink-soft);">Áp dụng toàn quốc</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3">
                        <i class="fas fa-truck fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 13px; color: var(--ink); display: block;">Miễn phí vận chuyển</strong>
                            <small style="color: var(--ink-soft);">Đơn từ 400.000đ</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- SECTION 4: Featured Collections Grid -->
    <section class="atelier-collections-section py-5" style="background: var(--paper-card); border-bottom: 1px solid var(--line);">
        <div class="container" style="max-width: 1280px;">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--ink); letter-spacing: 0.05em; margin: 0; text-transform: uppercase;">BỘ SƯ TẬP NỔI BẬT</h2>
                <a href="{{ route('products.index') }}" style="color: var(--accent); font-weight: 600; font-size: 13px; text-decoration: none;">Xem tất cả bộ sưu tập &rarr;</a>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <a href="{{ route('products.index', ['material' => 'acetate']) }}" class="card border-0 h-100 text-decoration-none" style="background: var(--paper); border-radius: 6px; overflow: hidden; border: 1px solid var(--line) !important;">
                        <img src="https://images.unsplash.com/photo-1572635196237-14b3f281503f?q=80&w=600&auto=format&fit=crop" alt="Acetate Thủ Công" style="width: 100%; height: 180px; object-fit: cover; display: block;">
                        <div class="card-body p-3">
                            <strong style="color: var(--ink); font-size: 13px; letter-spacing: 0.05em; display: block; font-weight: 700; text-transform: uppercase;">ACETATE THỦ CÔNG</strong>
                            <small class="text-muted" style="font-size: 11px;">Tinh tế &amp; bền bỉ</small>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <a href="{{ route('products.index', ['material' => 'titanium']) }}" class="card border-0 h-100 text-decoration-none" style="background: var(--paper); border-radius: 6px; overflow: hidden; border: 1px solid var(--line) !important;">
                        <img src="https://images.unsplash.com/photo-1511499767150-a48a237f0083?q=80&w=600&auto=format&fit=crop" alt="Titanium Cao Cấp" style="width: 100%; height: 180px; object-fit: cover; display: block;">
                        <div class="card-body p-3">
                            <strong style="color: var(--ink); font-size: 13px; letter-spacing: 0.05em; display: block; font-weight: 700; text-transform: uppercase;">TITANIUM CAO CẤP</strong>
                            <small class="text-muted" style="font-size: 11px;">Nhẹ - Bền - Sang trọng</small>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <a href="{{ route('products.index', ['category' => 'kinh-ram']) }}" class="card border-0 h-100 text-decoration-none" style="background: var(--paper); border-radius: 6px; overflow: hidden; border: 1px solid var(--line) !important;">
                        <img src="https://images.unsplash.com/photo-1508296695146-257a814070b4?q=80&w=600&auto=format&fit=crop" alt="Kính Râm Chống UV" style="width: 100%; height: 180px; object-fit: cover; display: block;">
                        <div class="card-body p-3">
                            <strong style="color: var(--ink); font-size: 13px; letter-spacing: 0.05em; display: block; font-weight: 700; text-transform: uppercase;">KÍNH RÂM CHỐNG UV</strong>
                            <small class="text-muted" style="font-size: 11px;">Bảo vệ mắt tối ưu</small>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <a href="{{ route('products.index', ['category' => 'trong-kinh']) }}" class="card border-0 h-100 text-decoration-none" style="background: var(--paper); border-radius: 6px; overflow: hidden; border: 1px solid var(--line) !important;">
                        <img src="https://images.unsplash.com/photo-1577803645773-f96470509666?q=80&w=600&auto=format&fit=crop" alt="Tròng Kính Cao Cấp" style="width: 100%; height: 180px; object-fit: cover; display: block;">
                        <div class="card-body p-3">
                            <strong style="color: var(--ink); font-size: 13px; letter-spacing: 0.05em; display: block; font-weight: 700; text-transform: uppercase;">TRÒNG KÍNH CAO CẤP</strong>
                            <small class="text-muted" style="font-size: 11px;">Đổi màu - Chống ánh sáng xanh</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="watch-products-section" style="{{ $homeSectionStyle('new_products') }}; padding: 48px 0; background: var(--paper-card);">
        <div class="container" style="max-width: 1280px;">
            <div class="mb-4">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--ink); letter-spacing: 0.05em; margin: 0; text-transform: uppercase;">SẢN PHẨM MỚI</h2>
            </div>
            <div class="watch-product-slider-container position-relative" style="padding: 0 10px;">
                <button class="watch-product-slider-arrow prev position-absolute" onclick="scrollProductSlider('new-products-track', 'prev')" style="left: -20px; top: 50%; transform: translateY(-50%); z-index: 20; background: transparent !important; border: none !important; outline: none !important; box-shadow: none !important; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ink-soft); font-size: 18px; transition: all 0.2s;" aria-label="Sản phẩm trước">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="watch-product-slider-arrow next position-absolute" onclick="scrollProductSlider('new-products-track', 'next')" style="right: -20px; top: 50%; transform: translateY(-50%); z-index: 20; background: transparent !important; border: none !important; outline: none !important; box-shadow: none !important; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ink-soft); font-size: 18px; transition: all 0.2s;" aria-label="Sản phẩm tiếp theo">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <div class="watch-product-slider-window" style="overflow: hidden;">
                    <div id="new-products-track" class="watch-products-grid">
                        @foreach ($newProducts as $product)
                            @include('partials.eyewear-product-card', ['product' => $product, 'showTryOn' => true])
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center align-items-center gap-2 mt-4">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
            </div>
        </div>
    </div>

    <div class="watch-products-section" style="{{ $homeSectionStyle('best_sellers') }}; padding: 48px 0; background: var(--paper);">
        <div class="container" style="max-width: 1280px;">
            <div class="mb-4">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--ink); letter-spacing: 0.05em; margin: 0; text-transform: uppercase;">SẢN PHẨM BÁN CHẠY</h2>
            </div>
            <div class="watch-product-slider-container position-relative" style="padding: 0 10px;">
                <button class="watch-product-slider-arrow prev position-absolute" onclick="scrollProductSlider('best-seller-track', 'prev')" style="left: -20px; top: 50%; transform: translateY(-50%); z-index: 20; background: transparent !important; border: none !important; outline: none !important; box-shadow: none !important; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ink-soft); font-size: 18px; transition: all 0.2s;" aria-label="Sản phẩm trước">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="watch-product-slider-arrow next position-absolute" onclick="scrollProductSlider('best-seller-track', 'next')" style="right: -20px; top: 50%; transform: translateY(-50%); z-index: 20; background: transparent !important; border: none !important; outline: none !important; box-shadow: none !important; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ink-soft); font-size: 18px; transition: all 0.2s;" aria-label="Sản phẩm tiếp theo">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <div class="watch-product-slider-window" style="overflow: hidden;">
                    <div id="best-seller-track" class="watch-products-grid">
                        @foreach ($featuredProducts as $product)
                            @include('partials.eyewear-product-card', ['product' => $product, 'showTryOn' => true])
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center align-items-center gap-2 mt-4">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
                <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--line); display: inline-block;"></span>
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
    <!-- SECTION 6: Why Choose Atelier Optique Badges -->
    <section class="atelier-why-us py-4" style="background: var(--paper); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);">
        <div class="container" style="max-width: 1280px;">
            <div class="row align-items-center text-center text-md-left">
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                        <i class="fas fa-users fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 14px; color: var(--ink); display: block;">10.000+</strong>
                            <small style="color: var(--ink-soft);">Khách hàng tin tưởng</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                        <i class="fas fa-award fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 14px; color: var(--ink); display: block;">15+ năm</strong>
                            <small style="color: var(--ink-soft);">Kinh nghiệm trong ngành</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                        <i class="fas fa-microscope fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 14px; color: var(--ink); display: block;">ZEISS Certified</strong>
                            <small style="color: var(--ink-soft);">Thiết bị đo chính hãng</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3">
                        <i class="fas fa-heart fa-2x" style="color: var(--accent);"></i>
                        <div>
                            <strong style="font-size: 14px; color: var(--ink); display: block;">Bảo hành 12 tháng</strong>
                            <small style="color: var(--ink-soft);">Hỗ trợ tận tâm</small>
                        </div>
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

    {{-- Dải cam kết cuối trang lấy nền trắng: ba section trước nó đều xám
         (why-us, posts, best-sellers) nên nếu để xám nữa sẽ thành một mảng phẳng
         đâm thẳng vào footer tối. Nền trắng + viền trên tạo nhịp nghỉ trước footer. --}}
    <section class="watch-services-section" style="{{ $homeSectionStyle('services') }}; background: var(--paper-card); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);">
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
