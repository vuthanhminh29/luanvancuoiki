<div class="product__item">
    <div class="product__item__pic set-bg" style="background-image: url('{{ $product->image_url }}')">
        @if ($product->sale_price)
            <div class="label sale">Sale</div>
        @endif
        <ul class="product__hover">
            <li><a href="{{ $product->image_url }}" class="image-popup"><span class="arrow_expand"></span></a></li>
            <li><a href="{{ route('products.show', $product) }}"><span class="icon_search_alt"></span></a></li>
        </ul>
    </div>
    <div class="product__item__text">
        <h6><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h6>
        <div class="rating">
            <i class="fa fa-star"></i>
            <i class="fa fa-star"></i>
            <i class="fa fa-star"></i>
            <i class="fa fa-star"></i>
            <i class="fa fa-star"></i>
        </div>
        <div class="product__price">
            {{ number_format($product->display_price, 0, ',', '.') }}d
            @if ($product->sale_price)
                <span>{{ number_format($product->base_price, 0, ',', '.') }}d</span>
            @endif
        </div>
    </div>
</div>
