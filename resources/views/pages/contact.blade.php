@extends('layouts.app')

@section('title', 'Liên hệ - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/contact.css') }}?v={{ filemtime(public_path('css/views/contact.css')) }}">
@endpush

@section('content')
<div class="bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex items-center space-x-2 text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fa fa-home"></i> Trang chủ
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">Liên hệ</span>
        </div>
    </div>
</div>

<div class="bg-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-base font-semibold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fa fa-map-marker text-gray-600"></i>
                        Thông tin lien lac
                    </h4>

                    <div class="space-y-4">
                        <div class="flex gap-4 p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-gray-200">
                            <div class="flex-shrink-0 w-12 h-12 bg-gray-900 rounded-full flex items-center justify-center text-white">
                                <i class="fa fa-map-marker"></i>
                            </div>
                            <div class="flex-1">
                                <h6 class="text-sm font-semibold text-gray-900 mb-1">Địa chỉ</h6>
                                <p class="text-sm text-gray-600">Trần Quang Khải, Tân Định, Hồ Chí Minh 700000, Việt Nam</p>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-gray-200">
                            <div class="flex-shrink-0 w-12 h-12 bg-gray-900 rounded-full flex items-center justify-center text-white">
                                <i class="fa fa-phone"></i>
                            </div>
                            <div class="flex-1">
                                <h6 class="text-sm font-semibold text-gray-900 mb-1">Số điện thoại</h6>
                                <a href="tel:0123456789" class="text-sm text-gray-600 hover:text-gray-900">0123456789</a>
                            </div>
                        </div>

                        <div class="flex gap-4 p-4 bg-gray-50 rounded-lg hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-gray-200">
                            <div class="flex-shrink-0 w-12 h-12 bg-gray-900 rounded-full flex items-center justify-center text-white">
                                <i class="fa fa-envelope"></i>
                            </div>
                            <div class="flex-1">
                                <h6 class="text-sm font-semibold text-gray-900 mb-1">Email hỗ trợ</h6>
                                <a href="mailto:hotro@gmail.com" class="text-sm text-gray-600 hover:text-gray-900">hotro@gmail.com</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h4 class="text-base font-semibold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fa fa-paper-plane text-gray-600"></i>
                        Gửi tin nhắn
                    </h4>

                    <form action="#" class="space-y-4">
                        <div><input type="text" placeholder="Họ tên *" required class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-100"></div>
                        <div><input type="email" placeholder="Email *" required class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-100"></div>
                        <div><input type="text" placeholder="Số điện thoại" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded focus:outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-100"></div>
                        <div><textarea placeholder="Tin nhan *" rows="5" required class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded resize-y focus:outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-100"></textarea></div>

                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-gray-900 rounded hover:bg-gray-800 transition-colors">
                            <i class="fa fa-paper-plane"></i>
                            Gửi tin nhắn
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:sticky lg:top-6 h-fit">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.2524688181966!2d106.6903445!3d10.791965399999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317529f81e9d147b%3A0xb9864c4a68f55da!2zS8OtbmggSOG6o2kgVHJp4buBdQ!5e0!3m2!1svi!2s!4v1775234963183!5m2!1svi!2s"
                        width="100%" height="450" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade" class="view-contact-inline-1"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
