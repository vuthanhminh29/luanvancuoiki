@php
    $discount = $product->sale_price ? max(0, round((($product->base_price - $product->sale_price) / max(1, $product->base_price)) * 100)) : 0;
    $firstVariant = $product->variants->firstWhere('status', 'ACTIVE') ?? $product->variants->first();
    $showTryOn = $showTryOn ?? true;

    // Parser tên sản phẩm nếu dữ liệu CSDL chứa chuỗi JSON
    $displayName = $product->name;
    if (is_string($displayName) && (str_starts_with(trim($displayName), '{') || str_starts_with(trim($displayName), '['))) {
        $decoded = json_decode($displayName, true);
        if (is_array($decoded)) {
            $displayName = $decoded['NAME'] ?? $decoded['name'] ?? $decoded['title'] ?? (is_array(reset($decoded)) ? (reset($decoded)['NAME'] ?? reset($decoded)['name'] ?? 'Gọng kính Atelier Optique') : reset($decoded));
        }
    }
    if (!is_string($displayName) || empty($displayName)) {
        $displayName = 'Gọng kính Atelier Optique';
    }

    // Parser thương hiệu nếu CSDL chứa JSON
    $displayBrand = $product->brand;
    if (is_string($displayBrand) && (str_starts_with(trim($displayBrand), '{') || str_starts_with(trim($displayBrand), '['))) {
        $decodedB = json_decode($displayBrand, true);
        if (is_array($decodedB)) {
            $displayBrand = $decodedB['BRAND'] ?? $decodedB['brand'] ?? reset($decodedB);
        }
    }
    if (!is_string($displayBrand) || empty($displayBrand)) {
        $displayBrand = $product->category?->name ?: 'Ray-Ban';
    }

    $fallbackImage = 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?q=80&w=600&auto=format&fit=crop';
    $productImage = $product->image_url ?: $fallbackImage;
@endphp

<article class="eyewear-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; position: relative; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column; height: 100%;">
    @if ($discount > 0)
        <span style="position: absolute; top: 12px; left: 12px; z-index: 5; background: #b0322b; color: #ffffff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">-{{ $discount }}%</span>
    @endif

    <button type="button" aria-label="Yêu thích" style="position: absolute; top: 12px; right: 12px; z-index: 5; background: rgba(255,255,255,0.85); backdrop-filter: blur(4px); border: none; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 14px; cursor: pointer; transition: color 0.2s;">
        <i class="far fa-heart"></i>
    </button>

    <div class="eyewear-card__media" style="background: #f8fafc; height: 200px; display: flex; align-items: center; justify-content: center; padding: 16px; position: relative; overflow: hidden;">
        <a href="{{ route('products.show', $product) }}" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
            <img src="{{ $productImage }}" alt="{{ $displayName }}" style="max-height: 160px; max-width: 100%; object-fit: contain; transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
        </a>
    </div>

    <div class="eyewear-card__body" style="padding: 16px; display: flex; flex-direction: column; flex: 1;">
        <span style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px;">
            {{ $displayBrand }}
        </span>

        <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 8px; line-height: 1.35; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
            <a href="{{ route('products.show', $product) }}" style="color: inherit; text-decoration: none;">{{ $displayName }}</a>
        </h3>

        <div class="eyewear-card__spec" style="font-family: var(--font-mono); font-size: 12px; color: var(--accent); font-weight: 600; letter-spacing: 0.04em; margin-bottom: 12px;">
            @if (!empty($product->frame_size))
                {{ str_replace([' ', '□', '-'], [' ', '▭', '-'], $product->frame_size) }}
            @else
                52 ▭ 18 &mdash; 145
            @endif
        </div>

        <div class="eyewear-card__price mt-auto pt-2" style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
            <span style="font-size: 16px; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums;">{{ number_format($product->display_price, 0, ',', '.') }}₫</span>
            @if ($product->sale_price)
                <span style="font-size: 12px; color: var(--ink-soft); text-decoration: line-through; font-variant-numeric: tabular-nums;">{{ number_format($product->base_price, 0, ',', '.') }}₫</span>
            @endif
        </div>

        @if ($showTryOn)
            <a href="{{ route('tryon', ['id_sp' => $product->id]) }}" class="btn w-100 d-inline-flex align-items-center justify-content-center gap-2" style="border: 1px solid #0e5c63; color: #0e5c63; background: transparent; font-weight: 600; font-size: 12px; border-radius: 4px; padding: 7px 12px; transition: all 0.2s;">
                <i class="fas fa-camera" aria-hidden="true"></i> TRẢI NGHIỆM AI
            </a>
        @endif
    </div>
</article>
