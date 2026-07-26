@extends('layouts.app')

@section('title', 'Thử kính AI - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/tryon-ai.css') }}?v=tryon-fix-20260726-{{ filemtime(public_path('css/views/tryon-ai.css')) }}">
@endpush

@section('content')
    @php
        $firstTryOn = $firstTryOn ?? null;
        $firstVariantId = $firstTryOn['variantId'] ?? null;
    @endphp

    <section id="vtoApp" data-tryon-app
        data-jeeliz-base-path="{{ asset('vendor/jeelizGlassesVTOWidget') }}"
        data-jeeliz-script-url="{{ asset('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js') }}">
        <div class="vto-viewer">
            <div id="JeelizVTOWidget">
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

            <div class="vto-placeholder" id="tryonNoModel">
                <i class="fas fa-camera"></i>
                <span>Nhấn nút camera để bắt đầu thử kính</span>
            </div>
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
    <script src="{{ asset('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js') }}?v=tryon-fix-20260726-{{ filemtime(public_path('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js')) }}"></script>
    <script src="{{ asset('js/tryon-ai.js') }}?v=tryon-fix-20260726-{{ filemtime(public_path('js/tryon-ai.js')) }}"></script>
@endpush
