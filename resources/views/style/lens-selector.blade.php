@extends('layouts.app')

@section('title', 'Chọn tròng kính phù hợp - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/advisor-shared.css') }}?v={{ file_exists(public_path('css/views/advisor-shared.css')) ? filemtime(public_path('css/views/advisor-shared.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/views/lens-selector.css') }}?v={{ file_exists(public_path('css/views/lens-selector.css')) ? filemtime(public_path('css/views/lens-selector.css')) : time() }}">
@endpush

@section('content')
    @php
        $groupLabels = [
            'nhu-cau' => 'Theo nhu cầu',
            'tinh-nang' => 'Theo tính năng',
            'pho-thong' => 'Mức phổ thông',
            'cao-cap' => 'Mức cao cấp',
        ];
        $tabs = ['nhu-cau', 'tinh-nang', 'pho-thong'];
    @endphp

    <nav class="advisor-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}"><i class="fa fa-home" aria-hidden="true"></i> Trang chủ</a>
        <span aria-hidden="true">/</span>
        <strong>Chọn tròng kính phù hợp</strong>
    </nav>

    <section class="advisor-page lens-page" data-lens-app data-frame-name="{{ $frameName ?? '' }}" data-frame-price="{{ $framePrice }}">
        <div class="advisor-container">
            <header class="advisor-header">
                <h1>Chọn tròng kính phù hợp</h1>
                <p>Kết hợp các lớp tráng theo nhu cầu sử dụng, xem tạm tính rồi gửi yêu cầu tư vấn cho nhân viên cửa hàng.</p>
            </header>

            <div class="lens-layout">
                <div class="lens-main">
                    <div class="lens-tabs" role="tablist" aria-label="Nhóm tròng kính">
                        @foreach ($tabs as $index => $tabKey)
                            <button type="button" class="lens-tab {{ $index === 0 ? 'is-active' : '' }}" data-tab="{{ $tabKey }}" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                {{ $groupLabels[$tabKey] }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($tabs as $index => $tabKey)
                        <div class="lens-list" data-tab-panel="{{ $tabKey }}" @if ($index !== 0) hidden @endif>
                            @foreach ($lensOptions as $option)
                                @if (in_array($tabKey, $option['groups'], true))
                                    <label class="lens-item">
                                        <input type="radio" class="lens-item-check" value="{{ $option['code'] }}"
                                            data-name="{{ $option['name'] }}" data-price="{{ $option['price'] }}">
                                        <span class="lens-item-icon"><i class="fas {{ $option['icon'] }}" aria-hidden="true"></i></span>
                                        <span class="lens-item-body">
                                            <span class="lens-item-name">{{ $option['name'] }}</span>
                                            <span class="lens-item-desc">{{ $option['description'] }}</span>
                                        </span>
                                        <span class="lens-item-price">{{ number_format($option['price'], 0, ',', '.') }}đ</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    @endforeach

                    <div class="lens-trust-row">
                        <span><i class="fas fa-crosshairs" aria-hidden="true"></i> Đo mắt chuẩn xác</span>
                        <span><i class="fas fa-user-tie" aria-hidden="true"></i> Chuyên gia tư vấn</span>
                        <span><i class="fas fa-shield-alt" aria-hidden="true"></i> Bảo hành 12 tháng</span>
                        <span><i class="fas fa-undo" aria-hidden="true"></i> Đổi trả dễ dàng</span>
                    </div>
                </div>

                <aside class="lens-summary">
                    <h2>Đơn của bạn</h2>

                    @if ($frameName)
                        <div class="lens-summary-frame">
                            <span class="lens-summary-label">Gọng kính đã chọn</span>
                            <div class="lens-summary-row">
                                <span>{{ $frameName }}</span>
                                <strong>{{ number_format($framePrice, 0, ',', '.') }}đ</strong>
                            </div>
                        </div>
                    @else
                        <p class="lens-summary-empty-frame">Chưa chọn gọng kính. <a href="{{ route('products.index') }}">Xem gọng kính</a> trước khi chọn tròng.</p>
                    @endif

                    <div class="lens-summary-list" id="lensSummaryList">
                        <p class="lens-summary-placeholder" id="lensSummaryPlaceholder">Chưa chọn lớp tráng tròng kính nào.</p>
                    </div>

                    <div class="lens-summary-total">
                        <span>Tạm tính</span>
                        <strong id="lensSummaryTotal">{{ number_format($framePrice, 0, ',', '.') }}đ</strong>
                    </div>

                    <a href="#" id="lensContactBtn" class="advisor-btn advisor-btn--solid lens-summary-cta">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> Gửi yêu cầu tư vấn
                    </a>

                    @if ($frameVariantId)
                        <form action="{{ route('cart.store') }}" method="post" class="lens-summary-cart-form">
                            @csrf
                            <input type="hidden" name="variant_id" value="{{ $frameVariantId }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="advisor-btn advisor-btn--ghost lens-summary-cta">
                                <i class="fas fa-shopping-bag" aria-hidden="true"></i> Thêm vào giỏ hàng
                            </button>
                        </form>
                    @else
                        <button type="button" class="advisor-btn advisor-btn--ghost lens-summary-cta" disabled title="Chưa xác định gọng kính để thêm vào giỏ hàng">
                            <i class="fas fa-shopping-bag" aria-hidden="true"></i> Thêm vào giỏ hàng
                        </button>
                    @endif

                    <p class="lens-summary-note">"Thêm vào giỏ hàng" thêm gọng kính theo giá gốc; lớp tráng tròng kính bên trên sẽ được nhân viên xác nhận qua yêu cầu tư vấn.</p>
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const app = document.querySelector('[data-lens-app]');
            if (!app) return;

            const frameName = app.dataset.frameName || '';
            const framePrice = parseInt(app.dataset.framePrice || '0', 10);

            const tabs = app.querySelectorAll('.lens-tab');
            const panels = app.querySelectorAll('[data-tab-panel]');

            tabs.forEach((tab) => {
                tab.addEventListener('click', function () {
                    tabs.forEach((t) => { t.classList.remove('is-active'); t.setAttribute('aria-selected', 'false'); });
                    this.classList.add('is-active');
                    this.setAttribute('aria-selected', 'true');
                    panels.forEach((panel) => { panel.hidden = panel.dataset.tabPanel !== this.dataset.tab; });
                });
            });

            const summaryList = document.getElementById('lensSummaryList');
            const summaryPlaceholder = document.getElementById('lensSummaryPlaceholder');
            const summaryTotal = document.getElementById('lensSummaryTotal');
            const contactBtn = document.getElementById('lensContactBtn');
            const formatter = new Intl.NumberFormat('vi-VN');

            // Nhiều tab hiển thị trùng một số lựa chọn (nhu cầu/tính năng có thể
            // giao nhau) nên các input này KHÔNG dùng chung name — nếu dùng
            // chung name, việc set checked=true trên bản sao ở tab khác sẽ khiến
            // trình duyệt tự bỏ chọn ô người dùng vừa bấm (cùng name = cùng
            // nhóm loại trừ lẫn nhau). Tự quản lý việc chỉ-chọn-1 hoàn toàn bằng
            // JS thay vì dựa vào name của radio.
            function syncCheckbox(code, checked) {
                app.querySelectorAll('.lens-item-check[value="' + code + '"]').forEach((cb) => {
                    cb.checked = checked;
                });
            }

            function updateSummary() {
                const seen = new Map();
                app.querySelectorAll('.lens-item-check:checked').forEach((cb) => {
                    seen.set(cb.value, { name: cb.dataset.name, price: parseInt(cb.dataset.price, 10) });
                });

                summaryList.querySelectorAll('.lens-summary-row[data-lens-row]').forEach((row) => row.remove());

                let total = framePrice;
                if (seen.size === 0) {
                    summaryPlaceholder.hidden = false;
                } else {
                    summaryPlaceholder.hidden = true;
                    seen.forEach((item, code) => {
                        total += item.price;
                        const row = document.createElement('div');
                        row.className = 'lens-summary-row';
                        row.dataset.lensRow = code;
                        row.innerHTML = '<span>' + item.name + '</span><strong>' + formatter.format(item.price) + 'đ</strong>' +
                            '<button type="button" class="lens-remove-btn" data-remove="' + code + '" aria-label="Bỏ chọn ' + item.name + '"><i class="fas fa-times"></i></button>';
                        summaryList.insertBefore(row, summaryPlaceholder);
                    });
                }

                summaryTotal.textContent = formatter.format(total) + 'đ';

                let subject = 'Yêu cầu tư vấn tròng kính';
                let bodyLines = [];
                if (frameName) bodyLines.push('Gọng kính: ' + frameName);
                if (seen.size > 0) {
                    bodyLines.push('Tròng kính:');
                    seen.forEach((item) => bodyLines.push('- ' + item.name + ' (' + formatter.format(item.price) + 'đ)'));
                } else {
                    bodyLines.push('Tròng kính: chưa chọn lớp tráng nào, nhờ tư vấn thêm.');
                }
                bodyLines.push('Tạm tính: ' + formatter.format(total) + 'đ');
                contactBtn.href = 'mailto:hello@atelieroptique.vn?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(bodyLines.join('\n'));
            }

            app.addEventListener('change', function (event) {
                if (!event.target.classList.contains('lens-item-check')) return;

                if (event.target.checked) {
                    // Chỉ cho chọn 1 loại tròng kính duy nhất: bỏ chọn mọi mã
                    // khác, đồng thời đồng bộ các bản sao cùng mã ở tab khác.
                    app.querySelectorAll('.lens-item-check').forEach((cb) => {
                        cb.checked = cb.value === event.target.value;
                    });
                } else {
                    syncCheckbox(event.target.value, false);
                }

                updateSummary();
            });

            summaryList.addEventListener('click', function (event) {
                const btn = event.target.closest('[data-remove]');
                if (!btn) return;
                syncCheckbox(btn.dataset.remove, false);
                updateSummary();
            });

            updateSummary();
        });
    </script>
@endpush
