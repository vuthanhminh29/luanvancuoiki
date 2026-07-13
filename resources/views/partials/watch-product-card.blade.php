@php
    $discount = $product->sale_price ? max(0, round((($product->base_price - $product->sale_price) / max(1, $product->base_price)) * 100)) : 0;
    $firstVariant = $product->variants->firstWhere('status', 'ACTIVE') ?? $product->variants->first();
    $showTryOn = $showTryOn ?? false;
@endphp

<div class="watch-product-card">
    @if ($discount > 0)
        <div class="watch-discount-badge">-{{ $discount }}%</div>
    @endif
    <div class="watch-product-img-wrapper">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="watch-product-img">
        <div class="watch-product-overlay">
            <div class="watch-product-actions">
                <a href="{{ $product->image_url }}" class="image-popup watch-action-btn"><span class="arrow_expand"></span></a>
                <a href="{{ route('products.show', $product) }}" class="watch-action-btn"><i class="fas fa-search"></i></a>
                @if ($showTryOn)
                    <a href="{{ route('tryon', ['id_sp' => $product->id]) }}" class="watch-action-btn tryon-action-btn"><i class="fas fa-glasses"></i></a>
                @endif
                @if ($firstVariant)
                    <form action="{{ route('cart.store') }}" method="post" class="watch-action-form">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="watch-action-btn"><i class="fas fa-shopping-bag"></i></button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="watch-product-info">
        <h3 class="watch-product-name">
            <a href="{{ route('products.show', $product) }}" class="watch-product-link">{{ $product->name }}</a>
        </h3>
        <div class="watch-product-price">
            <span class="watch-price-current">{{ number_format($product->display_price, 0, ',', '.') }}d</span>
            @if ($product->sale_price)
                <span class="watch-price-old">{{ number_format($product->base_price, 0, ',', '.') }}d</span>
            @endif
        </div>
        @if ($showTryOn)
            <a href="{{ route('tryon', ['id_sp' => $product->id]) }}" class="tryon-link-btn">
                <i class="fas fa-glasses"></i>
                Thử kính AI
            </a>
        @endif
        @if ($firstVariant)
            <form action="{{ route('cart.store') }}" method="post">
                @csrf
                <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="watch-add-cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    Mua ngay
                </button>
            </form>
        @endif
    </div>
</div>
