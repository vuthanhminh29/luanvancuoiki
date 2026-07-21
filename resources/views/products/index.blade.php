@extends('layouts.app')

@section('title', 'Sản phẩm - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/product/catalog.css') }}?v={{ filemtime(public_path('css/views/product/catalog.css')) }}">
@endpush

@section('content')
    @php
        $selected = fn (string $key) => collect((array) request()->input($key, []))->map(fn ($value) => (string) $value)->all();
        $checked = fn (string $key, $value) => in_array((string) $value, $selected($key), true);
        $filterKeys = ['category', 'brand', 'color', 'size', 'shape', 'material', 'uv'];
        $filterCount = collect($filterKeys)->sum(fn ($key) => count(array_filter((array) request()->input($key, []))));
        $filterCount += request()->boolean('sale') ? 1 : 0;
        $filterCount += request()->filled('from_price') ? 1 : 0;
        $filterCount += request()->filled('to_price') ? 1 : 0;
        $currentSort = (string) ($filters['sort'] ?? '');
        $currentFrom = (int) preg_replace('/\D/', '', (string) ($filters['from_price'] ?? ''));
        $currentTo = (int) preg_replace('/\D/', '', (string) ($filters['to_price'] ?? ''));
        $productBanners = collect($productBanners ?? []);
        $productBannerCount = $productBanners->count();
        $clearUrl = route('products.index');
        $catalogUrl = function (array $overrides = []) {
            $params = request()->query();
            foreach ($overrides as $key => $value) {
                if ($value === null || $value === '' || $value === []) {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
            }
            unset($params['page']);

            return route('products.index', $params);
        };
    @endphp

    <section class="ebd-catalog-page">
        <div class="ebd-container">
            <header class="ebd-catalog-heading">
                <h1>Cửa hàng kính mắt</h1>
                <p>Chọn gọng kính, kính mát và màu kính phù hợp với phong cách của bạn.</p>
            </header>

            <nav class="ebd-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Trang chủ</a>
                <span><i class="fa fa-angle-right"></i></span>
                <strong>Sản phẩm</strong>
            </nav>

            <div class="ebd-catalog-topline">
                <div class="ebd-filter-count">Bộ lọc ({{ $filterCount }})</div>
                <div class="ebd-chip-row">
                    <a class="ebd-chip {{ $currentSort === 'popular' ? 'active' : '' }}" href="{{ $catalogUrl(['sort' => 'popular']) }}">Bán chạy</a>
                    <a class="ebd-chip {{ request()->boolean('sale') ? 'active' : '' }}" href="{{ $catalogUrl(['sale' => 1]) }}">Đang giảm giá</a>
                    <a class="ebd-chip {{ $currentTo === 2000000 ? 'active' : '' }}" href="{{ $catalogUrl(['from_price' => 0, 'to_price' => 2000000]) }}">Dưới 2 triệu</a>
                    <a class="ebd-chip" href="{{ $clearUrl }}">Xóa lọc</a>
                </div>
                <label class="ebd-sort-control">
                    <span>Sắp xếp:</span>
                    <select id="catalogSort">
                        <option value="" @selected($currentSort === '')>Mới nhất</option>
                        <option value="popular" @selected($currentSort === 'popular')>Phổ biến</option>
                        <option value="price_asc" @selected($currentSort === 'price_asc')>Giá thấp đến cao</option>
                        <option value="price_desc" @selected($currentSort === 'price_desc')>Giá cao đến thấp</option>
                        <option value="name_asc" @selected($currentSort === 'name_asc')>Tên A-Z</option>
                        <option value="sale" @selected($currentSort === 'sale')>Giảm giá nhiều</option>
                    </select>
                </label>
            </div>

            <div class="ebd-catalog-layout">
                <form id="catalogFilterForm" action="{{ route('products.index') }}" method="get" class="ebd-filter-sidebar">
                    @if ($currentSort !== '')
                        <input type="hidden" name="sort" value="{{ $currentSort }}">
                    @endif

                    <div class="ebd-filter-tools">
                        <span>Bộ lọc</span>
                        <a href="{{ $clearUrl }}">Xóa tất cả</a>
                    </div>

                    <section class="ebd-filter-section is-open">
                        <button type="button" class="ebd-filter-title">
                            <span>Danh mục</span>
                            <i class="fa fa-minus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            @foreach ($categories as $category)
                                <label class="ebd-check-row">
                                    <input type="checkbox" name="category[]" value="{{ $category->id }}" @checked($checked('category', $category->id))>
                                    <span>{{ $category->name }}</span>
                                    <em>({{ $category->products_count ?? 0 }})</em>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="ebd-filter-section is-open">
                        <button type="button" class="ebd-filter-title">
                            <span>Màu kính</span>
                            <i class="fa fa-minus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            @foreach ($colors as $color)
                                <label class="ebd-check-row has-swatch">
                                    <input type="checkbox" name="color[]" value="{{ $color->id }}" @checked($checked('color', $color->id))>
                                    <span class="ebd-color-swatch" style="background: {{ $color->hex_code ?: '#ddd' }}"></span>
                                    <span>{{ $color->name }}</span>
                                    <em></em>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="ebd-filter-section">
                        <button type="button" class="ebd-filter-title">
                            <span>Size tròng</span>
                            <i class="fa fa-plus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            @foreach ($lensSizes as $size)
                                <label class="ebd-check-row">
                                    <input type="checkbox" name="size[]" value="{{ $size->id }}" @checked($checked('size', $size->id))>
                                    <span>{{ $size->name }}</span>
                                    <em></em>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="ebd-filter-section">
                        <button type="button" class="ebd-filter-title">
                            <span>Dáng gọng</span>
                            <i class="fa fa-plus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            @foreach ($frameShapes as $shape)
                                <label class="ebd-check-row">
                                    <input type="checkbox" name="shape[]" value="{{ $shape->id }}" @checked($checked('shape', $shape->id))>
                                    <span>{{ $shape->name }}</span>
                                    <em></em>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="ebd-filter-section">
                        <button type="button" class="ebd-filter-title">
                            <span>Chất liệu gọng</span>
                            <i class="fa fa-plus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            @foreach ($frameMaterials as $material)
                                <label class="ebd-check-row">
                                    <input type="checkbox" name="material[]" value="{{ $material->id }}" @checked($checked('material', $material->id))>
                                    <span>{{ $material->name }}</span>
                                    <em></em>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="ebd-filter-section">
                        <button type="button" class="ebd-filter-title">
                            <span>UV protection</span>
                            <i class="fa fa-plus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            @foreach ($uvOptions as $uv)
                                <label class="ebd-check-row">
                                    <input type="checkbox" name="uv[]" value="{{ $uv }}" @checked($checked('uv', $uv))>
                                    <span>{{ $uv }}</span>
                                    <em></em>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="ebd-filter-section is-open">
                        <button type="button" class="ebd-filter-title">
                            <span>Giá</span>
                            <i class="fa fa-minus"></i>
                        </button>
                        <div class="ebd-filter-body">
                            <div class="ebd-price-fields">
                                <label>
                                    <span>Từ</span>
                                    <input type="text" name="from_price" inputmode="numeric" value="{{ $currentFrom ? number_format($currentFrom) : '' }}" placeholder="{{ number_format((int) ($priceRange->min_price ?? 0)) }}">
                                </label>
                                <label>
                                    <span>Đến</span>
                                    <input type="text" name="to_price" inputmode="numeric" value="{{ $currentTo ? number_format($currentTo) : '' }}" placeholder="{{ number_format((int) ($priceRange->max_price ?? 0)) }}">
                                </label>
                            </div>
                            <label class="ebd-check-row">
                                <input type="checkbox" name="sale" value="1" @checked(request()->boolean('sale'))>
                                <span>Đang có khuyến mãi</span>
                            </label>
                        </div>
                    </section>
                </form>

                <main class="ebd-product-area">
                    <div class="ebd-promo-banner {{ $productBannerCount ? 'has-db-banner' : '' }}">
                        @if ($productBannerCount > 1)
                            <div id="product-banner-carousel" class="carousel slide" data-ride="carousel">
                                <div class="carousel-inner">
                                    @forelse ($productBanners as $index => $banner)
                                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                            <a href="{{ $banner->link_url ?: '#' }}">
                                                <img src="{{ $banner->image_src }}" alt="{{ $banner->title }}">
                                            </a>
                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                                <a class="carousel-control-prev" href="#product-banner-carousel" data-slide="prev" aria-label="Banner truoc">
                                    <span class="carousel-control-prev-icon"></span>
                                </a>
                                <a class="carousel-control-next" href="#product-banner-carousel" data-slide="next" aria-label="Banner sau">
                                    <span class="carousel-control-next-icon"></span>
                                </a>
                            </div>
                        @endif
                        @if ($productBannerCount === 1)
                            @forelse ($productBanners as $banner)
                                <a href="{{ $banner->link_url ?: '#' }}">
                                    <img src="{{ $banner->image_src }}" alt="{{ $banner->title }}">
                                </a>
                            @empty
                            @endforelse
                        @endif
                        <img src="{{ asset('upload/banner/banner-kinh-3.jpg') }}" alt="Khuyến mãi kính mắt">
                    </div>

                    <div class="ebd-result-bar">
                        <span><strong>{{ $products->total() }}</strong> sản phẩm</span>
                        @if ($filterCount > 0)
                            <a href="{{ $clearUrl }}">Xóa bộ lọc</a>
                        @endif
                    </div>

                    @if ($products->count())
                        <div class="ebd-products-grid">
                            @foreach ($products as $product)
                                @php
                                    $discount = $product->sale_price ? max(0, round((($product->base_price - $product->sale_price) / max(1, $product->base_price)) * 100)) : 0;
                                    $firstVariant = $product->variants->firstWhere('status', 'ACTIVE') ?? $product->variants->first();
                                    $hexes = $product->variants->pluck('color.hex_code')->filter()->unique()->take(5);
                                @endphp
                                <article class="ebd-product-card">
                                    @if ($discount > 0)
                                        <div class="ebd-discount-badge">-{{ $discount }}%</div>
                                    @endif
                                    <a href="{{ route('products.show', $product) }}" class="ebd-product-media">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                    </a>
                                    <div class="ebd-product-info">
                                        <div class="ebd-card-meta">
                                            <span>{{ $product->brand->name ?? 'RayBan' }}</span>
                                            <span>{{ $product->frameShape->name ?? '' }}</span>
                                        </div>
                                        <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
                                        <div class="ebd-product-specs">
                                            <span>{{ $product->frameMaterial->name ?? 'Acetate' }}</span>
                                            <span>{{ $product->uv_protection ?: 'UV400' }}</span>
                                            @if ($product->variants->pluck('lensSize.name')->filter()->unique()->isNotEmpty())
                                                <span>Size {{ $product->variants->pluck('lensSize.name')->filter()->unique()->join(', ') }}</span>
                                            @endif
                                        </div>
                                        <div class="ebd-swatches">
                                            @foreach ($hexes as $hex)
                                                <span style="background: {{ $hex }}"></span>
                                            @endforeach
                                        </div>
                                        <div class="ebd-price-row">
                                            <strong>{{ number_format($product->display_price, 0, ',', '.') }}d</strong>
                                            @if ($product->sale_price)
                                                <del>{{ number_format($product->base_price, 0, ',', '.') }}d</del>
                                            @endif
                                        </div>
                                        <div class="ebd-card-actions">
                                            <a href="{{ route('tryon', ['id_sp' => $product->id]) }}" class="ebd-tryon-btn"><i class="fas fa-glasses"></i> Thử kính</a>
                                            @if ($firstVariant)
                                                <form action="{{ route('cart.store') }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="ebd-cart-btn"><i class="fas fa-shopping-bag"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($products->lastPage() > 1)
                            <div class="ebd-pagination">
                                @for ($page = 1; $page <= $products->lastPage(); $page++)
                                    <a href="{{ $products->url($page) }}" class="{{ $products->currentPage() === $page ? 'active' : '' }}">{{ $page }}</a>
                                @endfor
                            </div>
                        @endif
                    @else
                        <div class="ebd-empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>Không tìm thấy sản phẩm</h3>
                            <p>Hãy thử bỏ bớt điều kiện lọc hoặc quay lại danh sách tất cả sản phẩm.</p>
                            <a href="{{ $clearUrl }}">Xóa bộ lọc</a>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            function cleanPrice(value) {
                return String(value || "").replace(/[^0-9]/g, "");
            }

            function formatPrice(value) {
                const number = cleanPrice(value);
                return number ? Number(number).toLocaleString("en-US") : "";
            }

            $(".ebd-price-fields input").on("input", function() {
                this.value = formatPrice(this.value);
            });

            $(".ebd-price-fields input").on("change", function() {
                $("#catalogFilterForm").trigger("submit");
            });

            $(".ebd-filter-title").on("click", function() {
                const section = $(this).closest(".ebd-filter-section");
                section.toggleClass("is-open");
                const icon = $(this).find("i");
                icon.toggleClass("fa-plus", !section.hasClass("is-open"));
                icon.toggleClass("fa-minus", section.hasClass("is-open"));
            });

            $("#catalogSort").on("change", function() {
                const url = new URL(window.location.href);
                if (this.value) {
                    url.searchParams.set("sort", this.value);
                } else {
                    url.searchParams.delete("sort");
                }
                url.searchParams.delete("page");
                window.location.href = url.toString();
            });

            $("#catalogFilterForm").on("submit", function() {
                $(this).find("input[name='page']").remove();
                $(this).find(".ebd-price-fields input").each(function() {
                    this.value = cleanPrice(this.value);
                    if (!this.value) this.disabled = true;
                });
            });

            $("#catalogFilterForm input[type='checkbox']").on("change", function() {
                const form = $("#catalogFilterForm");
                form.find("input[name='page']").remove();
                form.trigger("submit");
            });
        });
    </script>
@endpush
