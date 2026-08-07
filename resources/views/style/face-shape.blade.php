@extends('layouts.app')

@section('title', 'Tìm dáng kính phù hợp - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/advisor-shared.css') }}?v={{ file_exists(public_path('css/views/advisor-shared.css')) ? filemtime(public_path('css/views/advisor-shared.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/views/face-shape.css') }}?v={{ file_exists(public_path('css/views/face-shape.css')) ? filemtime(public_path('css/views/face-shape.css')) : time() }}">
@endpush

@section('content')
    <nav class="advisor-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}"><i class="fa fa-home" aria-hidden="true"></i> Trang chủ</a>
        <span aria-hidden="true">/</span>
        <strong>Tìm dáng kính phù hợp</strong>
    </nav>

    <section class="advisor-page fs-page" data-fs-app>
        <div class="advisor-container">
            <header class="advisor-header">
                <h1>Tìm dáng kính phù hợp</h1>
                <p>Chọn khuôn mặt tôn lên nét riêng của bạn — mỗi khuôn mặt hợp với vài dáng gọng khác nhau.</p>
            </header>

            <ol class="advisor-steps">
                <li class="is-active" data-step-label="1"><span>Nhận diện khuôn mặt</span></li>
                <li data-step-label="2"><span>Gợi ý dáng kính</span></li>
                <li data-step-label="3"><span>Xem sản phẩm</span></li>
            </ol>

            <div class="fs-layout">
                <div class="fs-photo-col">
                    <div class="fs-photo-frame" id="fsPhotoFrame">
                        <img id="fsPhotoPreview" alt="" hidden>
                        <div class="fs-photo-placeholder" id="fsPhotoPlaceholder">
                            <i class="far fa-image" aria-hidden="true"></i>
                            <p>Thêm ảnh chính diện để so sánh trực quan khi chọn khuôn mặt (không bắt buộc).</p>
                        </div>
                    </div>
                    <label class="fs-upload-btn advisor-btn advisor-btn--ghost">
                        <i class="fas fa-camera" aria-hidden="true"></i> Thêm ảnh của bạn
                        <input type="file" accept="image/*" id="fsPhotoInput" class="visually-hidden">
                    </label>
                    <p class="fs-photo-hint">Ảnh chỉ hiển thị trên trình duyệt của bạn, không được lưu lại.</p>
                </div>

                <div class="fs-result-col">
                    <h2 class="fs-question">Khuôn mặt của bạn gần với dáng nào?</h2>

                    <div class="fs-shape-grid" role="group" aria-label="Chọn khuôn mặt">
                        @foreach ($faceShapes as $key => $shape)
                            <button type="button" class="fs-shape-btn" data-face="{{ $key }}">
                                <span class="fs-face-icon fs-face-icon--{{ strtolower(str_replace('_', '-', $key)) }}" aria-hidden="true"></span>
                                <span class="fs-shape-name">{{ $shape['name'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="fs-detail" id="fsDetail" hidden aria-live="polite">
                        @foreach ($faceShapes as $key => $shape)
                            <div class="fs-detail-panel" data-face-detail="{{ $key }}" hidden>
                                <div class="fs-detail-head">
                                    <span class="fs-face-icon fs-face-icon--{{ strtolower(str_replace('_', '-', $key)) }} fs-face-icon--lg" aria-hidden="true"></span>
                                    <div>
                                        <h3>Khuôn mặt {{ $shape['name'] }}</h3>
                                        <p>{{ $shape['summary'] }}</p>
                                    </div>
                                </div>

                                <h4 class="fs-subhead">Đặc điểm</h4>
                                <ul class="fs-traits">
                                    @foreach ($shape['traits'] as $trait)
                                        <li>{{ $trait }}</li>
                                    @endforeach
                                </ul>

                                <h4 class="fs-subhead">Dáng kính phù hợp</h4>
                                <div class="fs-recommend-row">
                                    @foreach ($shape['recommend'] as $code)
                                        <span class="fs-frame-chip">
                                            <span class="fs-frame-icon fs-frame-icon--{{ strtolower(str_replace('_', '-', $code)) }}" aria-hidden="true"></span>
                                            {{ $frameShapeNames[$code] ?? $code }}
                                        </span>
                                    @endforeach
                                </div>

                                @if ($shape['recommendedShapeIds']->isNotEmpty())
                                    <a class="advisor-btn advisor-btn--solid fs-cta"
                                        href="{{ route('products.index', ['shape' => $shape['recommendedShapeIds']->all()]) }}">
                                        Xem gợi ý sản phẩm
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="fs-tip">
                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                        <span><strong>Mẹo nhỏ:</strong> Chọn gọng kính có chiều rộng tương đương hoặc lớn hơn chiều rộng gò má để tạo cân đối cho khuôn mặt.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const app = document.querySelector('[data-fs-app]');
            if (!app) return;

            const shapeButtons = app.querySelectorAll('.fs-shape-btn');
            const detailBox = document.getElementById('fsDetail');
            const detailPanels = app.querySelectorAll('[data-face-detail]');

            shapeButtons.forEach((btn) => {
                btn.addEventListener('click', function () {
                    shapeButtons.forEach((b) => b.classList.remove('is-selected'));
                    this.classList.add('is-selected');

                    const face = this.dataset.face;
                    detailPanels.forEach((panel) => {
                        panel.hidden = panel.dataset.faceDetail !== face;
                    });
                    detailBox.hidden = false;
                    detailBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });

            const photoInput = document.getElementById('fsPhotoInput');
            const photoPreview = document.getElementById('fsPhotoPreview');
            const photoPlaceholder = document.getElementById('fsPhotoPlaceholder');

            photoInput && photoInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function (event) {
                    photoPreview.src = event.target.result;
                    photoPreview.hidden = false;
                    photoPlaceholder.hidden = true;
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush
