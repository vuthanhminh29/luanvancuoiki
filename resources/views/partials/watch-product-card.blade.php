{{-- Wrapper tương thích ngược cho tên component cũ watch-product-card --}}
@include('partials.eyewear-product-card', [
    'product' => $product,
    'showTryOn' => $showTryOn ?? true
])
