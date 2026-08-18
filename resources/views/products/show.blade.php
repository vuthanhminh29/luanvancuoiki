@extends('layouts.app')

@section('title', $product->name . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/productdetail-base.css') }}?v={{ filemtime(public_path('css/views/productdetail-base.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/views/productdetail.css') }}?v={{ filemtime(public_path('css/views/productdetail.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/views/comments.css') }}?v={{ filemtime(public_path('css/views/comments.css')) }}">
@endpush

@section('content')
    @php
        $firstVariant = $product->variants->firstWhere('status', 'ACTIVE') ?? $product->variants->first();
        $variantStock = collect($variantStock ?? []);
        $selectedVariantId = $firstVariant ? (int) $firstVariant->id : 0;
        $selectedVariantStock = $selectedVariantId > 0 ? max(0, (int) $variantStock->get($selectedVariantId, 0)) : 0;
        $discount = $product->sale_price ? max(0, round((($product->base_price - $product->sale_price) / max(1, $product->base_price)) * 100)) : 0;
        $firstTryOn = $tryOnPayload->first();
        $canAddToCart = $firstVariant && $selectedVariantStock > 0;
        $firstTryOnVariantId = (int) ($firstTryOn['variantId'] ?? 0);
        $firstTryOnStock = $firstTryOnVariantId > 0 ? max(0, (int) $variantStock->get($firstTryOnVariantId, 0)) : 0;
        $reviewCount = $reviewStats['count'] ?? $visibleReviews->count();
        $reviewAverage = (float) ($reviewStats['average'] ?? 0);
        $reviewStars = max(0, min(5, (int) round($reviewAverage ?: 5)));
        $thumbnailImages = collect([$product->image_url])
            ->merge($product->images->map(fn ($image) => trim((string) $image->image_url) === '' ? null : $image->url))
            ->filter()
            ->values();
        $displayCategories = $product->categories->isNotEmpty()
            ? $product->categories
            : collect([$product->category])->filter();
        $primaryCategory = $displayCategories->first();


        $rawDescription = trim((string) $product->description);

        if ($rawDescription === '') {
            $descriptionHtml = 'Thông tin sản phẩm đang được cập nhật.';
        } elseif (str_contains($rawDescription, '<') && str_contains($rawDescription, '>')) {
            $descriptionHtml = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $rawDescription);
            $descriptionHtml = strip_tags($descriptionHtml, '<p><br><strong><b><em><i><ul><ol><li><h2><h3><h4>');
            $descriptionHtml = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $descriptionHtml);
        } else {
            $descriptionHtml = nl2br(e($rawDescription));
        }

        $plainDescription = preg_replace('/<\/(h2|h3|h4|p|li)>/i', '$0 ', $rawDescription);
        $plainDescription = preg_replace('/<(br|br\/)>/i', ' ', (string) $plainDescription);
        $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags((string) $plainDescription)))
            ?: 'Kiểu dáng dễ đeo, phù hợp sử dụng hằng ngày.';
    @endphp

    <div class="watch-breadcrumb">
        <div class="watch-container">
            <div class="watch-breadcrumb-list">
                <a href="{{ route('home') }}" class="watch-breadcrumb-item">
                    <i class="fa fa-home"></i> Trang chủ
                </a>
                <span class="watch-breadcrumb-separator">/</span>
                <a href="{{ route('products.index') }}" class="watch-breadcrumb-item">Sản phẩm</a>
                <span class="watch-breadcrumb-separator">/</span>
                <a href="{{ route('products.index', ['category' => $primaryCategory?->id ?? $product->category_id]) }}" class="watch-breadcrumb-item">
                    {{ $primaryCategory?->name ?? 'Danh mục' }}
                </a>
                <span class="watch-breadcrumb-separator">/</span>
                <span class="watch-breadcrumb-current">{{ $product->name }}</span>
            </div>
        </div>
    </div>

    <section class="watch-detail-section">
        <div class="watch-container">
            <div class="watch-detail-grid">
                <div class="watch-images-col">
                    <div class="watch-main-image">
                        @if ($discount > 0)
                            <div class="watch-discount-badge">-{{ $discount }}%</div>
                        @endif
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" id="mainProductImage" class="watch-main-img">
                    </div>
<div class="watch-thumb-slider">
    <button type="button" class="watch-thumb-arrow" onclick="scrollThumbs(-1)" aria-label="Ảnh trước">
        <i class="fa fa-chevron-left"></i>
    </button>

    <div class="watch-thumbnails" id="watchThumbnails">
        @foreach ($thumbnailImages as $thumbnailImage)
            @php
                $fallbackImage = $loop->first ? $product->image_url : $thumbnailImages->get($loop->index - 1, $product->image_url);
            @endphp
            <div class="watch-thumb {{ $loop->first ? 'active' : '' }}">
                <img src="{{ $thumbnailImage }}" alt="{{ $product->name }}" data-fallback="{{ $fallbackImage }}" onerror="this.onerror=null;this.src=this.dataset.fallback;" onclick="changeMainImage(this)">
            </div>
        @endforeach
    </div>

    <button type="button" class="watch-thumb-arrow" onclick="scrollThumbs(1)" aria-label="Ảnh sau">
        <i class="fa fa-chevron-right"></i>
    </button>
</div>
                </div>

                <div class="watch-info-col">
                    <h1 class="watch-product-title">{{ $product->name }}</h1>

                    <div class="watch-category-line">
                        Danh mục:
                        @forelse ($displayCategories as $category)
                            <a href="{{ route('products.index', ['category' => $category->id]) }}" class="watch-category-link">
                                {{ $category->name }}
                            </a>@if (! $loop->last), @endif
                        @empty
                            <span>Sản phẩm</span>
                        @endforelse
                    </div>

                    <div class="watch-rating-line">
                        <div class="watch-stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <i class="{{ $star <= $reviewStars ? 'fa' : 'far' }} fa-star"></i>
                            @endfor
                        </div>
                        <span class="watch-review-text">({{ $reviewCount }} đánh giá)</span>
                    </div>

                    <div class="watch-price-line">
                        <span class="watch-current-price">{{ number_format($product->display_price, 0, ',', '.') }}d</span>
                        @if ($product->sale_price)
                            <span class="watch-original-price">{{ number_format($product->base_price, 0, ',', '.') }}d</span>
                        @endif
                    </div>

                    <div class="watch-short-desc watch-description-text watch-description-text--summary">
                        {!! $descriptionHtml !!}
                    </div>

                    @if ($canAddToCart)
                        <form action="{{ route('cart.store') }}" method="post">
                            @csrf
                            <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
                            <div class="watch-quantity-box">
                                <label class="watch-qty-label">Số lượng:</label>
                                <div class="watch-qty-controls">
                                    <button type="button" class="watch-qty-btn" onclick="decreaseQty()">-</button>
                                    <input type="number" name="quantity" id="productQty" value="1" min="1" max="{{ $selectedVariantStock }}" class="watch-qty-input">
                                    <button type="button" class="watch-qty-btn" onclick="increaseQty({{ $selectedVariantStock }})">+</button>
                                </div>
                                <span class="watch-stock-text">Còn {{ $selectedVariantStock }} sản phẩm</span>
                            </div>

                            <div class="watch-button-group">
                                <button type="submit" class="watch-btn watch-btn-outline">
                                    <i class="fas fa-shopping-cart"></i>
                                    Thêm vào giỏ
                                </button>
                                <button type="submit" class="watch-btn watch-btn-solid">
                                    <i class="fas fa-bolt"></i>
                                    Mua ngay
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="watch-out-of-stock">
                            <button class="watch-stock-btn" disabled>Hết hàng</button>
                        </div>
                    @endif

                    <div class="watch-extra-actions">
                        <button class="watch-extra-btn"><i class="far fa-heart"></i> Yêu thích</button>
                        <button type="button" class="watch-extra-btn" id="openVtoModal"><i class="fas fa-glasses"></i> Trải nghiệm AI</button>
                        <button class="watch-extra-btn"><i class="fas fa-share-alt"></i> Chia sẻ</button>
                    </div>

                    <div class="atelier-product-guarantee my-3 p-3" style="background: var(--paper); border: 1px solid var(--line); border-radius: var(--radius); font-size: 13px; color: var(--ink);">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <i class="fas fa-undo" style="color: var(--accent);" aria-hidden="true"></i>
                            <span><strong>Đổi trả 7 ngày:</strong> Miễn phí đổi mẫu nếu không vừa khuôn mặt.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <i class="fas fa-shield-alt" style="color: var(--accent);" aria-hidden="true"></i>
                            <span><strong>Bảo hành thích nghi:</strong> Đổi tròng kính trong 30 ngày nếu mỏi mắt.</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-headset" style="color: var(--accent);" aria-hidden="true"></i>
                            <span><strong>Tư vấn thị lực:</strong> 1900 6789 (Hỗ trợ 8h00 - 21h30).</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="watch-tabs-section">
                <div class="watch-tabs-nav">
                    <button class="watch-tab-btn active" data-tab="description">Mô tả sản phẩm</button>
                    <button class="watch-tab-btn" data-tab="reviews">Đánh giá ({{ $reviewCount }})</button>
                </div>
                <div class="watch-tabs-content">
                    <div class="watch-tab-panel active" id="description">
                        <div class="watch-description-text">
                            {!! $descriptionHtml !!}
                        </div>
                    </div>
                    <div class="watch-tab-panel" id="reviews">
                        <div class="watch-comments-section">
                            <h6 class="watch-comments-title">Bình luận ({{ $reviewCount }})</h6>

                            @if (session('success'))
                                <div class="watch-review-alert success">{{ session('success') }}</div>
                            @endif

                            @if ($errors->any())
                                <div class="watch-review-alert error">{{ $errors->first() }}</div>
                            @endif

                            <div class="watch-comments-grid">
                                <div class="watch-comments-list">
                                    @forelse ($visibleReviews as $review)
                                        @php
                                            $avatar = trim((string) ($review->user->avatar_url ?? ''));
                                            $avatarSrc = $avatar === ''
                                                ? asset('upload/user-default.png')
                                                : (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')
                                                    ? $avatar
                                                    : asset(str_starts_with($avatar, 'upload/') ? $avatar : 'upload/' . $avatar));
                                        @endphp
                                        <div class="watch-comment-item">
                                            <div class="watch-comment-header">
                                                <img src="{{ $avatarSrc }}" alt="Avatar" class="watch-comment-avatar">
                                                <div class="watch-comment-meta">
                                                    <h6 class="watch-comment-author">{{ $review->user->full_name ?? 'Khách hàng' }}</h6>
                                                    <span class="watch-comment-date">{{ $review->created_at?->format('d/m/Y H:i') }}</span>
                                                </div>
                                                <div class="watch-review-stars" aria-label="{{ $review->rating }} sao">
                                                    @for ($star = 1; $star <= 5; $star++)
                                                        <i class="{{ $star <= (int) $review->rating ? 'fa' : 'far' }} fa-star"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                            <p class="watch-comment-text">{{ $review->content }}</p>
                                        </div>
                                    @empty
                                        <div class="watch-no-comments">
                                            <svg class="watch-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                                </path>
                                            </svg>
                                            <span class="watch-empty-text">Chưa có bình luận nào</span>
                                        </div>
                                    @endforelse

                                    @if ($visibleReviews->hasPages())
                                        @php
                                            $reviewPageStart = max(1, $visibleReviews->currentPage() - 2);
                                            $reviewPageEnd = min($visibleReviews->lastPage(), $reviewPageStart + 4);
                                            $reviewPageStart = max(1, $reviewPageEnd - 4);
                                        @endphp
                                        <div class="watch-review-pagination" aria-label="Phân trang bình luận">
                                            <a class="watch-review-page {{ $visibleReviews->onFirstPage() ? 'disabled' : '' }}"
                                                href="{{ $visibleReviews->previousPageUrl() ?: '#' }}" aria-label="Trang trước">
                                                <i class="fa fa-chevron-left"></i>
                                            </a>

                                            @for ($page = $reviewPageStart; $page <= $reviewPageEnd; $page++)
                                                <a class="watch-review-page {{ $page === $visibleReviews->currentPage() ? 'active' : '' }}"
                                                    href="{{ $visibleReviews->url($page) }}">{{ $page }}</a>
                                            @endfor

                                            <a class="watch-review-page {{ $visibleReviews->hasMorePages() ? '' : 'disabled' }}"
                                                href="{{ $visibleReviews->nextPageUrl() ?: '#' }}" aria-label="Trang sau">
                                                <i class="fa fa-chevron-right"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <div class="watch-comment-form-wrapper">
                                    @auth
                                        <div class="watch-comment-form">
                                            <h4 class="watch-form-title">Để lại đánh giá</h4>

                                            <form action="{{ route('products.reviews.store', $product) }}" method="post">
                                                @csrf
                                                <div class="watch-form-group">
                                                    <label class="watch-form-label">Số sao *</label>
                                                    <div class="watch-rating-input" aria-label="Chọn số sao">
                                                        @for ($star = 5; $star >= 1; $star--)
                                                            <input type="radio" id="rating-{{ $star }}" name="rating" value="{{ $star }}" @checked((int) old('rating', 5) === $star)>
                                                            <label for="rating-{{ $star }}" title="{{ $star }} sao"><i class="fa fa-star"></i></label>
                                                        @endfor
                                                    </div>
                                                </div>

                                                <div class="watch-form-group">
                                                    <label for="message" class="watch-form-label">Nội dung *</label>
                                                    <textarea id="message" name="content" required rows="5" class="watch-form-textarea"
                                                        placeholder="Nhập nội dung bình luận của bạn...">{{ old('content') }}</textarea>
                                                </div>

                                                <div class="watch-form-actions">
                                                    <button type="submit" class="watch-submit-btn">
                                                        <svg class="watch-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                        </svg>
                                                        Gửi đánh giá
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @else
                                        <div class="watch-comment-form watch-login-prompt">
                                            <div class="watch-login-content">
                                                <svg class="watch-login-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <h4 class="watch-login-title">Vui lòng đăng nhập để có thể bình luận</h4>
                                                <a href="{{ route('login') }}" class="watch-login-btn">Đăng nhập ngay</a>
                                            </div>
                                        </div>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($relatedProducts->isNotEmpty())
                <div class="watch-similar-products">
                    <h2 class="watch-similar-title">Sản phẩm tương tự</h2>
                    <div class="watch-products-grid">
                        @foreach ($relatedProducts as $product)
                            @include('partials.eyewear-product-card', ['product' => $product, 'showTryOn' => true])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <div id="vtoModal" class="vto-modal-overlay" aria-hidden="true">
        <div class="vto-modal-content vto-eyebuy-shell" role="dialog" aria-modal="true" aria-label="Thử kính trực tuyến">
            <section class="tryon-ai-page tryon-ai-modal tryon-ai-modal--eyebuy" data-tryon-app>
                <div class="tryon-eyebuy-viewer">
                    <button type="button" class="tryon-expand-fab" title="Phong to">
                        <i class="fas fa-expand-alt"></i>
                    </button>

                    <button type="button" class="tryon-framefit-pill" id="tryonAdjustGlasses" title="Tùy chỉnh kính">
                        <i class="fas fa-arrows-alt"></i>
                        <span>ADJUST</span>
                    </button>

                    <div class="tryon-ai-viewer">
                        <div id="JeelizVTOWidget">
                            <img id="tryonUploadedPreview" class="tryon-uploaded-preview" src="" alt="">
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
                                <div class="JeelizVTOWidgetLoadingText">Loading frames...</div>
                            </div>
                        </div>

                        <div class="tryon-no-model" id="tryonNoModel"></div>
                    </div>

                    <div class="tryon-ai-status" id="tryonStatus">Sẵn sàng thử kính.</div>
                    <div class="tryon-ai-products" id="tryonProductList" aria-hidden="true"></div>

                    <div class="tryon-upload-panel" id="tryonUploadPanel" aria-hidden="true">
                        <h2>Bước 1</h2>
                        <div class="tryon-upload-card" id="tryonUploadDropzone">
                            <input type="file" id="tryonImageInput" accept="image/*">
                            <h3>Tải ảnh lên</h3>
                            <button type="button" class="tryon-upload-plus" aria-label="Chọn ảnh">
                                <i class="fas fa-plus"></i>
                            </button>
                            <p><span>Chọn file</span> hoặc kéo ảnh vào đây</p>
                            <small>(dung lượng tối đa 5 MB)</small>
                            <p class="tryon-upload-note">Giữ khuôn mặt nhìn thẳng, ở giữa khung hình.<br>Không đeo kính khi chụp ảnh.</p>
                            <div class="tryon-upload-error" id="tryonUploadError"></div>
                        </div>
                    </div>

                    <div class="tryon-capture-bar" aria-label="Try-on controls">
                        <button type="button" class="tryon-round-control is-active" id="tryonStartCamera" title="Bật camera">
                            <i class="fas fa-camera"></i>
                        </button>
                        <button type="button" class="tryon-round-control tryon-round-control--image" id="tryonUploadImage" title="Chụp/đổi ảnh">
                            <i class="far fa-image"></i>
                        </button>
                    </div>
                </div>

                <aside class="tryon-eyebuy-side">
                    <button type="button" id="closeVtoModal" class="tryon-close-fab" title="Đóng">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="tryon-side-panel tryon-side-panel--product is-active" data-panel="product">
                        <div class="tryon-ai-selected">
                            <img id="tryonSelectedImage" src="{{ $firstTryOn['productImage'] ?? $product->image_url }}" alt="">
                            <div>
                                <h2 id="tryonSelectedName">{{ $firstTryOn['name'] ?? $product->name }}</h2>
                                <div class="tryon-selected-pricebox">
                                    <span>Giá sản phẩm</span>
                                    <strong id="tryonSelectedPrice">{{ $firstTryOn['priceText'] ?? number_format($product->display_price, 0, ',', '.') . 'd' }}</strong>
                                </div>
                                <p class="tryon-selected-desc" id="tryonSelectedDesc">{{ $firstTryOn['description'] ?? $plainDescription }}</p>
                                <div class="tryon-selected-meta">
                                    <div>
                                        <span>Thương hiệu</span>
                                        <strong id="tryonSelectedBrand">{{ $firstTryOn['brand'] ?? ($product->brand->name ?? '') }}</strong>
                                    </div>
                                    <div>
                                        <span>Chất liệu</span>
                                        <strong id="tryonSelectedMaterial">{{ $firstTryOn['material'] ?? ($product->frameMaterial->name ?? '') }}</strong>
                                    </div>
                                </div>
                                <a id="tryonDetailLink" href="{{ $firstTryOn['detailUrl'] ?? route('products.show', $product) }}">Xem chi tiết</a>
                            </div>
                        </div>

                        <div class="tryon-side-actions">
                            <div class="tryon-buy-row">
                                @if ($firstTryOnVariantId > 0 && $firstTryOnStock > 0)
                                    <form action="{{ route('cart.store') }}" method="post" class="tryon-ai-cart-form" id="tryonCartForm">
                                        @csrf
                                        <input type="hidden" name="variant_id" id="tryonCartVariantId" value="{{ $firstTryOn['variantId'] }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="tryon-ai-bag-btn">Thêm vào giỏ</button>
                                    </form>
                                @else
                                    <button type="button" class="tryon-ai-bag-btn" disabled>Thêm vào giỏ</button>
                                @endif
                                <button type="button" class="tryon-heart-outline" title="Yêu thích">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tryon-side-panel tryon-side-panel--upload" data-panel="upload">
                        <button type="button" class="tryon-fit-back" id="tryonUploadBack">
                            <i class="fas fa-redo"></i> Chụp ảnh lại
                        </button>
                        <h2>Thêm ảnh mới</h2>
                        <p>Chọn ảnh chính diện, rõ khuôn mặt để thử kính chính xác hơn.</p>
                        <button type="button" class="tryon-fit-cancel" id="tryonUploadCancel">Quay lại</button>
                    </div>
                </aside>

                <script type="application/json" id="tryonProductData">@json($tryOnPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jeelizGlassesVTOWidget/dist/JeelizVTOWidget.js') }}"></script>
    <script src="{{ asset('js/tryon-ai.js') }}?v=20260630-detail"></script>
    <script>
        function changeMainImage(thumbnail) {
            const mainImage = document.getElementById('mainProductImage');
            mainImage.src = thumbnail.src;

            document.querySelectorAll('.watch-thumb').forEach(t => t.classList.remove('active'));
            thumbnail.parentElement.classList.add('active');
        }

        function scrollThumbs(direction) {
            const list = document.getElementById('watchThumbnails');
            if (!list) return;

            const item = list.querySelector('.watch-thumb');
            const step = item ? item.offsetWidth + 12 : 120;

            list.scrollBy({
                left: direction * step,
                behavior: 'smooth'
            });
        }

        function increaseQty(max) {
            const input = document.getElementById('productQty');
            const currentValue = parseInt(input.value || '1');
            if (currentValue < max) input.value = currentValue + 1;
        }

        function decreaseQty() {
            const input = document.getElementById('productQty');
            const currentValue = parseInt(input.value || '1');
            if (currentValue > 1) input.value = currentValue - 1;
        }

        document.querySelectorAll('.watch-tab-btn').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                document.querySelectorAll('.watch-tab-btn').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.watch-tab-panel').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });

        if (window.location.hash === '#reviews' || document.querySelector('.watch-review-alert')) {
            document.querySelector('.watch-tab-btn[data-tab="reviews"]')?.click();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const vtoModal = document.getElementById('vtoModal');
            const openVtoModalBtn = document.getElementById('openVtoModal');
            const closeVtoModalBtn = document.getElementById('closeVtoModal');
            const adjustGlassesBtn = document.getElementById('tryonAdjustGlasses');

            function resizeVto() {
                window.JEELIZVTOWIDGET && window.JEELIZVTOWIDGET.resize && window.JEELIZVTOWIDGET.resize();
            }

            function closeModal() {
                vtoModal.classList.remove('active');
                vtoModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('vto-modal-lock');
                window.stopJeelizTryon && window.stopJeelizTryon();
            }

            if (openVtoModalBtn && vtoModal && closeVtoModalBtn) {
                const openModal = () => {
                    vtoModal.classList.add('active');
                    vtoModal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('vto-modal-lock');
                    requestAnimationFrame(() => {
                        window.startJeelizTryon && window.startJeelizTryon();
                        setTimeout(resizeVto, 250);
                        setTimeout(resizeVto, 850);
                    });
                };

                openVtoModalBtn.addEventListener('click', openModal);

                closeVtoModalBtn.addEventListener('click', closeModal);
                vtoModal.addEventListener('click', (event) => {
                    if (event.target === vtoModal) closeModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && vtoModal.classList.contains('active')) closeModal();
                });

                const params = new URLSearchParams(window.location.search);
                if (params.get('tryon') === '1' || window.location.hash === '#thu-kinh') {
                    openModal();
                }
            }

            adjustGlassesBtn && adjustGlassesBtn.addEventListener('click', () => {
                window.enterJeelizAdjustMode && window.enterJeelizAdjustMode();
            });
        });
    </script>
@endpush
