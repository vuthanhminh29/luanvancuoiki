@extends('layouts.app')

@section('title', $post->title . ' - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/blog/blog-details.css') }}?v={{ filemtime(public_path('css/views/blog/blog-details.css')) }}">
@endpush

@section('content')
<div class="bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex items-center space-x-2 text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fa fa-home"></i> Trang chủ
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('blog.index') }}" class="text-gray-600 hover:text-gray-900">Bài viết</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium truncate max-w-xs">{{ $post->title }}</span>
        </div>
    </div>
</div>

<div class="bg-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <article class="lg:col-span-8">
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="p-8 border-b border-gray-200">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full mb-4">
                            <i class="fas fa-tag text-gray-500"></i>
                            {{ $post->category->name ?? 'Blog' }}
                        </span>

                        <h1 class="text-3xl font-bold text-gray-900 mb-6 leading-tight">
                            {{ $post->title }}
                        </h1>

                        <div class="flex items-center gap-4 text-sm text-gray-600 flex-wrap">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-user text-gray-500"></i>
                                <span>Admin</span>
                            </div>
                            <span class="text-gray-300">-</span>
                            <div class="flex items-center gap-2">
                                <i class="far fa-calendar-alt text-gray-500"></i>
                                <span>{{ optional($post->published_at ?? $post->created_at)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full h-96 bg-gray-100">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                    </div>

                    <div class="p-8 prose prose-gray max-w-none">
                        {!! $post->content ?: '<p>' . e($post->summary) . '</p>' !!}
                    </div>

                    <div class="mx-8 mb-8 p-6 bg-gray-50 border-l-4 border-gray-900 rounded">
                        <p class="text-base text-gray-700 italic">
                            Cảm ơn bạn đã dành thời gian đọc bài viết của chúng tôi!
                        </p>
                    </div>

                    <div class="p-8 border-t border-gray-200">
                        <h5 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-share-alt text-gray-600"></i>
                            Chia sẻ bài viết
                        </h5>
                        <div class="flex gap-3">
                            <a href="#" class="share-btn share-facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="share-btn share-twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="share-btn share-linkedin"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="share-btn share-pinterest"><i class="fab fa-pinterest-p"></i></a>
                        </div>
                    </div>
                </div>
            </article>

            <aside class="lg:col-span-4">
                <div class="lg:sticky lg:top-4 space-y-6">
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-folder-open text-gray-600"></i>
                            Chuyên mục
                        </h4>
                        <ul class="space-y-2 list-none">
                            <li>
                                <a href="{{ route('blog.index') }}" class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded transition-colors group">
                                    <span class="flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-gray-600"></i>Tất cả</span>
                                </a>
                            </li>
                            @foreach ($categories as $category)
                                <li>
                                    <a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded transition-colors group">
                                        <span class="flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-gray-600"></i>{{ $category->name }}</span>
                                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded">({{ $category->posts_count }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-fire text-gray-600"></i>
                            Bài viết mới
                        </h4>
                        <div class="space-y-4">
                            @foreach ($recentPosts as $recentPost)
                                <a href="{{ route('blog.show', $recentPost) }}" class="flex gap-3 group">
                                    <div class="flex-shrink-0 w-20 h-20 rounded overflow-hidden bg-gray-100">
                                        <img src="{{ $recentPost->image_url }}" alt="{{ $recentPost->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-sm font-medium text-gray-900 mb-1 line-clamp-2 leading-tight group-hover:text-gray-600 transition-colors">{{ $recentPost->title }}</h5>
                                        <span class="text-xs text-gray-500 flex items-center gap-1">{{ optional($recentPost->published_at ?? $recentPost->created_at)->format('d/m/Y') }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
