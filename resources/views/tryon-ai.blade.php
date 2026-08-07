@extends('layouts.app')

@section('title', 'Trải nghiệm kính AI - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/advisor-shared.css') }}?v={{ file_exists(public_path('css/views/advisor-shared.css')) ? filemtime(public_path('css/views/advisor-shared.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/views/tryon-ai.css') }}?v=tryon-fix-20260801-{{ file_exists(public_path('css/views/tryon-ai.css')) ? filemtime(public_path('css/views/tryon-ai.css')) : time() }}">
@endpush

@section('content')
    @php
        $firstTryOn = $firstTryOn ?? null;
        $firstVariantId = $firstTryOn['variantId'] ?? null;
    @endphp

    <nav class="advisor-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}"><i class="fa fa-home" aria-hidden="true"></i> Trang chủ</a>
        <span aria-hidden="true">/</span>
        <strong>Trải nghiệm kính AI</strong>
    </nav>

    <section class="advisor-page tryon-lead">
        <div class="advisor-container">
            <header class="advisor-header">
                <h1>Trải nghiệm kính AI</h1>
                <p>Bật camera hoặc tải ảnh lên để xem trước dáng kính, chọn nhanh mẫu ưng ý trước khi đặt mua.</p>
            </header>
        </div>
    </section>

    <section id="vtoApp" data-tryon-app
        data-jeeliz-base-path="{{ asset('vendor/jeelizGlassesVTOWidget') }}"
        data-jeeliz-script-url="{{ asset('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js') }}"
        data-jeeliz-model-check-url="{{ route('tryon.model-check') }}"
        data-snapshot-store-url="{{ route('tryon.snapshots.store') }}"
        data-login-url="{{ route('login') }}"
        data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
        <div class="vto-viewer">
            <div id="JeelizVTOWidget">
                <img id="tryonUploadedPreview" class="vto-uploaded-preview" src="" alt="">
                <canvas id="JeelizVTOWidgetCanvas"></canvas>

                <div class="JeelizVTOWidgetControls JeelizVTOWidgetControlsTop">
                    <button type="button" id="JeelizVTOWidgetAdjust" title="Căn chỉnh kính">
                        <i class="fas fa-arrows-alt"></i>
                    </button>
                </div>

                <div id="JeelizVTOWidgetAdjustNotice">
                    Di chuyển kính để căn chỉnh vị trí.
                    <button type="button" id="JeelizVTOWidgetAdjustExit">Xong</button>
                </div>

                <div id="JeelizVTOWidgetLoading">
                    <div class="JeelizVTOWidgetLoadingText">ĐANG TẢI...</div>
                </div>
            </div>

            {{-- Màn hình chọn cách thử: hiện mặc định, JS tự ẩn khi root có class
                 tryon-camera-active / tryon-image-mode (xem tryon-ai.css). Không đổi
                 id/class mà tryon-ai.js đang query — chỉ thêm 2 nút proxy gọi .click()
                 lên #tryonStartCamera / #tryonUploadImage (script riêng bên dưới). --}}
            <div class="vto-landing" id="vtoLanding">
                <ol class="vto-landing-steps">
                    <li><span class="vto-landing-step-num">01</span><span>Chọn kính</span></li>
                    <li><span class="vto-landing-step-num">02</span><span>Chụp ảnh</span></li>
                    <li><span class="vto-landing-step-num">03</span><span>Thử ngay</span></li>
                </ol>
                <h2 class="vto-landing-title">Chọn cách thử</h2>
                <div class="vto-landing-methods">
                    <button type="button" class="vto-method-card" data-proxy-target="tryonStartCamera">
                        <span class="vto-method-icon" aria-hidden="true"><i class="fas fa-camera"></i></span>
                        <strong>Dùng camera trực tiếp</strong>
                        <span>Chụp thẳng bằng webcam để thử ngay</span>
                    </button>
                    <button type="button" class="vto-method-card" id="tryonUploadImage">
                        <span class="vto-method-icon" aria-hidden="true"><i class="far fa-image"></i></span>
                        <strong>Tải ảnh lên</strong>
                        <span>Hỗ trợ JPG, PNG, dưới 5MB</span>
                    </button>
                </div>
                <p class="vto-landing-note"><i class="fas fa-lock" aria-hidden="true"></i> Ảnh chỉ dùng để thử kính, không chia sẻ cho bên thứ ba.</p>

                <div class="vto-upload-panel" id="tryonUploadPanel">
                    <div class="vto-upload-dropzone" id="tryonUploadDropzone">
                        <input type="file" id="tryonImageInput" accept="image/*" class="visually-hidden">
                        <i class="far fa-image" aria-hidden="true"></i>
                        <p><span>Chọn file</span> hoặc kéo ảnh vào đây</p>
                        <small>Giữ khuôn mặt nhìn thẳng, không đeo kính khi chụp. Tối đa 5MB.</small>
                        <div class="vto-upload-error" id="tryonUploadError"></div>
                    </div>
                    <button type="button" class="vto-upload-cancel" id="tryonUploadCancel">Hủy, quay lại</button>
                </div>
            </div>

            <div class="vto-no-model" id="tryonNoModel"></div>
        </div>

        <div class="vto-topbar">
            <a href="{{ $firstTryOn['detailUrl'] ?? route('products.index') }}"
                class="vto-icon-btn" id="tryonCloseLink" title="Đóng">
                <i class="fas fa-times"></i>
            </a>
            <button type="button" class="vto-icon-btn" title="Yêu thích">
                <i class="far fa-heart"></i>
            </button>
        </div>

        <button type="button" class="vto-status" id="tryonStartCamera" title="Bật / tắt camera" aria-pressed="false">
            <span class="vto-toggle-main">
                <i class="fas fa-camera"></i>
                <span id="tryonCameraToggleText">Bật camera</span>
            </span>
            <span class="vto-status-message" id="tryonStatus">Sẵn sàng thử kính.</span>
        </button>

        <button type="button" class="vto-save-btn" id="tryonSaveSnapshot">
            <i class="fas fa-camera-retro"></i> Chụp/Lưu kết quả
        </button>

        <div class="vto-products" id="tryonProductList"></div>

        <div class="vto-bottom">
            <button type="button" class="vto-camera-btn" id="tryonCameraMiniBtn" title="Bật / tắt camera">
                <i class="fas fa-camera"></i>
            </button>

            <div class="vto-info">
                <strong id="tryonSelectedName">{{ $firstTryOn['name'] ?? 'Chưa chọn kính' }}</strong>
                <span class="vto-price" id="tryonSelectedPrice">{{ $firstTryOn['priceText'] ?? '' }}</span>
            </div>

            <a class="vto-detail-link" id="tryonDetailLink" href="{{ $firstTryOn['detailUrl'] ?? route('products.index') }}">
                <strong><i class="fas fa-info"></i> Xem chi tiết kính</strong>
                <span>Tham khảo ảnh, giá và mô tả trước khi chọn mua</span>
            </a>

            <div class="vto-cart-wrap">
                @if ($firstVariantId)
                    <form action="{{ route('cart.store') }}" method="post" id="tryonCartForm">
                        @csrf
                        <input type="hidden" name="variant_id" id="tryonCartVariantId" value="{{ $firstVariantId }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="vto-cart-btn">
                            <i class="fas fa-shopping-bag"></i> Thêm vào giỏ
                        </button>
                    </form>
                @else
                    <button type="button" class="vto-cart-btn" disabled>
                        <i class="fas fa-shopping-bag"></i> Thêm vào giỏ
                    </button>
                @endif
            </div>
        </div>

        <script type="application/json" id="tryonProductData">@json($tryOnPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js') }}?v=tryon-fix-20260801-{{ file_exists(public_path('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js')) ? filemtime(public_path('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js')) : time() }}"></script>
    <script src="{{ asset('js/tryon-ai.js') }}?v=tryon-fix-20260801-{{ file_exists(public_path('js/tryon-ai.js')) ? filemtime(public_path('js/tryon-ai.js')) : time() }}"></script>
    <script>
        // Chỉ nối 2 nút chọn cách thử sang đúng nút điều khiển thật mà tryon-ai.js
        // đã lắng nghe (#tryonStartCamera, #tryonUploadImage). Không đụng vào
        // tryon-ai.js để không ảnh hưởng luồng camera/AR đang chạy ổn định.
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-proxy-target]').forEach(function (proxy) {
                proxy.addEventListener('click', function () {
                    const target = document.getElementById(this.dataset.proxyTarget);
                    target && target.click();
                });
            });
        });
    </script>
@endpush
