@extends('layouts.app')

@section('title', 'Hỗ trợ - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/help-center/styles.css') }}?v={{ filemtime(public_path('css/views/help-center/styles.css')) }}">
@endpush

@section('content')
<section class="hc-uq" aria-label="Trung tâm hỗ trợ khách hàng">
    <header class="hc-uq-header">
        <div class="hc-uq-wrap hc-uq-head-inner">
            <a class="hc-uq-brand" href="{{ route('pages.support') }}">
                <span class="hc-uq-mark">HT</span>
                <span>
                    <strong>Customer Service</strong>
                    <em>Trung tâm hỗ trợ khách hàng</em>
                </span>
            </a>
            <div class="hc-uq-actions">
                <a href="{{ route('pages.support') }}"><i class="fa fa-search"></i> Tìm kiếm</a>
                <button type="button"><i class="fa fa-bars"></i> Menu</button>
                <span>English | Tiếng Việt</span>
            </div>
        </div>
    </header>

    <section class="hc-uq-hero">
        <div class="hc-uq-wrap">
            <h1>Chúng tôi có thể hỗ trợ bạn như thế nào?</h1>
            <form class="hc-uq-search" action="{{ route('pages.support') }}" method="get">
                <i class="fa fa-search"></i>
                <input type="search" name="q" value="{{ $query ?? '' }}" placeholder="Nhập từ khóa: giao hàng, thanh toán, đổi trả, đơn mua...">
                <button type="submit">Tìm kiếm</button>
            </form>
        </div>
    </section>

    @if (($query ?? '') !== '')
        <section class="hc-uq-search-results" aria-label="Kết quả tìm kiếm hỗ trợ">
            <div class="hc-uq-wrap">
                <div class="hc-uq-block">
                    <div class="hc-uq-title">
                        <h2>Kết quả tìm kiếm cho "{{ $query }}"</h2>
                        <p>Tìm thấy {{ $supportResults->count() }} nội dung phù hợp.</p>
                    </div>

                    @if ($supportResults->isEmpty())
                        <div class="hc-uq-no-results">
                            Không tìm thấy nội dung phù hợp. Hãy thử từ khóa khác như "giao hàng", "thanh toán", "đổi trả" hoặc "đơn mua".
                        </div>
                    @else
                        <div class="hc-uq-result-list">
                            @foreach ($supportResults as $item)
                                <a class="hc-uq-result-item" href="{{ $item['url'] }}">
                                    <span>{{ $item['category'] }}</span>
                                    <strong>{{ $item['title'] }}</strong>
                                    <em>{{ $item['description'] }}</em>
                                    <i class="fa fa-angle-right"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <nav class="hc-uq-shortcuts" aria-label="Liên kết nhanh">
        <div class="hc-uq-wrap">
            <a href="{{ route('account.orders.index') }}">Theo dõi đơn hàng</a>
            <a href="{{ route('cart.index') }}">Giỏ hàng</a>
            <a href="{{ route('products.index') }}">Cửa hàng</a>
            <a href="{{ route('returns.index') }}">Hoàn/Đổi</a>
            <a href="{{ route('pages.contact') }}">Liên hệ</a>
        </div>
    </nav>

    <main class="hc-uq-main">
        <div class="hc-uq-wrap">
            <section class="hc-uq-block">
                <div class="hc-uq-title">
                    <h2>Câu hỏi thường gặp</h2>
                </div>
                <div class="hc-uq-faq-grid">
                    <a href="{{ route('pages.support', ['q' => 'đơn hàng']) }}"><i class="fa fa-angle-right"></i><span>Làm sao để kiểm tra trạng thái đơn hàng?</span></a>
                    <a href="{{ route('pages.support', ['q' => 'giao hàng']) }}"><i class="fa fa-angle-right"></i><span>Thời gian giao hàng dự kiến là bao lâu?</span></a>
                    <a href="{{ route('pages.support', ['q' => 'thanh toán']) }}"><i class="fa fa-angle-right"></i><span>Có thể thanh toán khi nhận hàng không?</span></a>
                    <a href="{{ route('pages.support', ['q' => 'đổi trả']) }}"><i class="fa fa-angle-right"></i><span>Điều kiện gửi yêu cầu hoàn/đổi sau khi nhận hàng</span></a>
                </div>
            </section>

            <section class="hc-uq-block">
                <div class="hc-uq-title">
                    <h2>Quản lý đơn hàng online của bạn</h2>
                    <p>Nhấn vào các lựa chọn bên dưới để được hỗ trợ về vận chuyển, thanh toán hoặc đổi/trả cho đơn hàng trực tuyến.</p>
                </div>
                <div class="hc-uq-order-grid">
                    <a href="{{ route('account.orders.index') }}">
                        <i class="fa fa-shopping-bag"></i>
                        <strong>Đơn mua của tôi</strong>
                        <span>Xem danh sách đơn hàng và trạng thái xử lý.</span>
                    </a>
                    <a href="{{ route('checkout.index') }}">
                        <i class="fa fa-credit-card"></i>
                        <strong>Thanh toán</strong>
                        <span>Kiểm tra thông tin giao hàng và phương thức thanh toán.</span>
                    </a>
                    <a href="{{ route('returns.index') }}">
                        <i class="fa fa-refresh"></i>
                        <strong>Cách đổi/trả sản phẩm online</strong>
                        <span>Gửi yêu cầu sau khi đơn giao thành công và còn trong thời hạn hỗ trợ.</span>
                    </a>
                </div>
            </section>

            <section class="hc-uq-block">
                <div class="hc-uq-title">
                    <h2>Tìm kiếm theo danh mục</h2>
                </div>
                <div class="hc-uq-category-grid">
                    <article>
                        <h3><i class="fa fa-truck"></i>Giao hàng</h3>
                        <ul>
                            <li><a href="{{ route('pages.support', ['q' => 'phương thức giao hàng']) }}">Phương thức và thời gian giao hàng</a></li>
                            <li><a href="{{ route('pages.support', ['q' => 'phí vận chuyển']) }}">Phí vận chuyển</a></li>
                        </ul>
                        <button type="button">Hiển thị thêm</button>
                    </article>
                    <article>
                        <h3><i class="fa fa-credit-card"></i>Thanh toán</h3>
                        <ul>
                            <li><a href="{{ route('checkout.index') }}">Thanh toán</a></li>
                            <li><a href="{{ route('pages.support', ['q' => 'bảo mật thanh toán']) }}">Bảo mật thanh toán</a></li>
                        </ul>
                        <button type="button">Hiển thị thêm</button>
                    </article>
                    <article>
                        <h3><i class="fa fa-refresh"></i>Hoàn/Đổi</h3>
                        <ul>
                            <li><a href="{{ route('returns.index') }}">Gửi yêu cầu hoàn/đổi</a></li>
                            <li><a href="{{ route('pages.support', ['q' => 'điều kiện đổi trả']) }}">Điều kiện tiếp nhận</a></li>
                        </ul>
                        <button type="button">Hiển thị thêm</button>
                    </article>
                </div>
            </section>

            <section class="hc-uq-lower">
                <div class="hc-uq-block hc-uq-notices">
                    <div class="hc-uq-title">
                        <h2>Thông báo</h2>
                    </div>
                    <a href="{{ route('pages.support', ['q' => 'miễn phí vận chuyển']) }}">
                        <strong>Miễn phí vận chuyển</strong>
                        <span>Đơn hàng từ 1.000.000đ được hỗ trợ phí vận chuyển.</span>
                    </a>
                    <a href="{{ route('pages.support', ['q' => 'hỗ trợ sau bán hàng']) }}">
                        <strong>Hỗ trợ sau bán hàng</strong>
                        <span>Gửi yêu cầu đổi/trả trực tiếp từ chi tiết đơn hàng.</span>
                    </a>
                </div>

                <div class="hc-uq-block hc-uq-contact">
                    <div class="hc-uq-title">
                        <h2>Liên hệ hỗ trợ khách hàng</h2>
                    </div>
                    <div class="hc-uq-chat">
                        <i class="fa fa-comments"></i>
                        <div>
                            <strong>CHAT VỚI CHÚNG TÔI</strong>
                            <p>Hỗ trợ kiểm tra đơn hàng, sản phẩm kính, giao hàng và yêu cầu sau bán hàng.</p>
                        </div>
                    </div>
                    <a class="hc-uq-contact-btn" href="{{ route('pages.contact') }}">Liên hệ ngay</a>
                </div>
            </section>
        </div>
    </main>
</section>
@endsection
