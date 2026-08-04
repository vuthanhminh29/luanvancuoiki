@extends('layouts.app')

@section('title', ($selectedCategory ? $selectedCategory->name . ' - ' : '') . 'Bài viết - ' . config('app.name'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/views/blog/blogs.css') }}?v={{ filemtime(public_path('css/views/blog/blogs.css')) }}">
@endpush

@section('content')
<div class="blog-breadcrumb bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex items-center space-x-2 text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fa fa-home"></i> Trang chủ
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('blog.index') }}" class="text-gray-600 hover:text-gray-900">Bài viết</a>
            @if ($selectedCategory)
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-medium">{{ $selectedCategory->name }}</span>
            @endif
        </div>
    </div>
</div>

<div class="blog-page bg-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="blog-layout grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="blog-main lg:col-span-8">
                <div class="blog-list-head mb-8">
                    <h1 class="blog-title text-3xl font-bold text-gray-900">
                        {{ $selectedCategory ? 'Chuyên mục: ' . $selectedCategory->name : 'Bài viết' }}
                    </h1>
                    <p class="blog-subtitle mt-2 text-gray-600">
                        {{ $selectedCategory ? 'Các bài viết thuộc chuyên mục ' . $selectedCategory->name . '.' : 'Tin tức và chia sẻ mới nhất từ WARFARER.' }}
                    </p>
                </div>

                <div class="blog-grid grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    @forelse ($posts as $post)
                        <article class="blog-card bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow group">
                            <a href="{{ route('blog.show', $post) }}" class="blog-card-media block relative overflow-hidden aspect-video bg-gray-100">
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="blog-card-image w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </a>

                            <div class="blog-card-body p-4">
                                <div class="blog-card-meta flex items-center gap-4 text-xs text-gray-500 mb-3">
                                    @if ($post->category)
                                        <a class="blog-card-chip" href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <i class="far fa-calendar-alt"></i>
                                        {{ optional($post->published_at ?? $post->created_at)->format('d/m/Y') }}
                                    </span>
                                    <span class="blog-card-admin flex items-center gap-1">
                                        <i class="fas fa-user"></i>
                                        Quản trị viên
                                    </span>
                                </div>

                                <h3 class="blog-card-title text-base font-semibold text-gray-900 mb-3 line-clamp-2 leading-tight group-hover:text-gray-600 transition-colors">
                                    <a href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
                                </h3>

                                @if ($post->summary)
                                    <p class="blog-card-summary">{{ $post->summary }}</p>
                                @endif

                                <a href="{{ route('blog.show', $post) }}" class="blog-read-more inline-flex items-center text-sm font-medium text-gray-900 hover:text-gray-600 gap-2 group/link">
                                    Đọc thêm
                                    <i class="fas fa-arrow-right text-xs group-hover/link:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="blog-empty md:col-span-2 bg-white border border-gray-200 rounded-lg p-8 text-center text-gray-600">
                            Chưa có bài viết nào trong chuyên mục này.
                        </div>
                    @endforelse
                </div>

                <div class="blog-pagination text-center">
                    {{ $posts->links() }}
                </div>
            </div>

            <aside class="blog-sidebar lg:col-span-4">
                <div class="lg:sticky lg:top-4 space-y-6">
                    <div class="blog-side-card bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="blog-side-title text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-folder-open text-gray-600"></i>
                            Chuyên mục
                        </h4>
                        <ul class="blog-category-list space-y-2 list-none">
                            <li>
                                <a href="{{ route('blog.index') }}" class="blog-category-link {{ $selectedCategory ? '' : 'active' }} flex items-center justify-between px-3 py-2 text-sm {{ $selectedCategory ? 'text-gray-700 hover:bg-gray-50' : 'bg-gray-900 text-white' }} rounded transition-colors group">
                                    <span class="flex items-center gap-2">
                                        <i class="fas fa-chevron-right text-xs {{ $selectedCategory ? 'text-gray-400 group-hover:text-gray-600' : 'text-white' }}"></i>
                                        Tất cả
                                    </span>
                                </a>
                            </li>
                            @foreach ($categories as $category)
                                @php $isActiveCategory = $selectedCategory?->is($category); @endphp
                                <li>
                                    <a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="blog-category-link {{ $isActiveCategory ? 'active' : '' }} flex items-center justify-between px-3 py-2 text-sm {{ $isActiveCategory ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50' }} rounded transition-colors group">
                                        <span class="flex items-center gap-2">
                                            <i class="fas fa-chevron-right text-xs {{ $isActiveCategory ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                                            {{ $category->name }}
                                        </span>
                                        <span class="text-xs font-medium {{ $isActiveCategory ? 'text-gray-900 bg-white' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded">({{ $category->posts_count }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="blog-side-card bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="blog-side-title text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-fire text-gray-600"></i>
                            Bài viết mới
                        </h4>
                        <div class="blog-recent-list space-y-4">
                            @foreach ($recentPosts as $recentPost)
                                <a href="{{ route('blog.show', $recentPost) }}" class="blog-recent-item flex gap-3 group">
                                    <div class="blog-recent-media flex-shrink-0 w-20 h-20 rounded overflow-hidden bg-gray-100">
                                        <img src="{{ $recentPost->image_url }}" alt="{{ $recentPost->title }}" class="blog-recent-image w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h5 class="blog-recent-title text-sm font-medium text-gray-900 mb-1 line-clamp-2 leading-tight group-hover:text-gray-600 transition-colors">
                                            {{ $recentPost->title }}
                                        </h5>
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            {{ optional($recentPost->published_at ?? $recentPost->created_at)->format('d/m/Y') }}
                                        </span>
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
