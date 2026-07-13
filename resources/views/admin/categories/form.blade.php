@extends('admin.layouts.app')

@section('title', $title)

@section('content')
<div class="container-fluid pt-4" style="margin-bottom: 110px;">
    <form class="row g-4" action="{{ $action }}" method="post" enctype="multipart/form-data">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <div class="col-sm-12 col-xl-9">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">
                    <a href="{{ $backRoute }}" class="link-not-hover">Danh mục</a>
                    / {{ $title }}
                </h6>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <label for="floatingInput">Tên danh mục</label>
                <div class="form-floating mb-4">
                    <input name="name" type="text" value="{{ old('name', $category?->name) }}" class="form-control" id="floatingInput">
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <label for="floatingSlug">Slug</label>
                <div class="form-floating mb-4">
                    <input name="slug" type="text" value="{{ old('slug', $category?->slug) }}" class="form-control" id="floatingSlug">
                    @error('slug')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <label for="floatingDescription">Mô tả</label>
                <div class="form-floating mb-4">
                    <textarea name="description" class="form-control" id="floatingDescription" style="height: 120px">{{ old('description', $category?->description) }}</textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <label for="floatingSelect">Trạng thái</label>
                <div class="form-floating mb-3">
                    @php($currentStatus = old('status', $category?->status ?? 'ACTIVE'))
                    <select name="status" class="form-select" id="floatingSelect">
                        <option value="ACTIVE" @selected($currentStatus === 'ACTIVE' || $currentStatus == 1)>Hiển thị</option>
                        <option value="INACTIVE" @selected($currentStatus === 'INACTIVE' || $currentStatus == 0)>Tạm ẩn</option>
                    </select>
                    @error('status')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-xl-3">
            <div class="bg-light rounded h-100 p-4">
                <div class="mb-3">
                    <label for="formFileSm" class="form-label">Hình ảnh (JPG, PNG)</label><br>
                    @error('image_url')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <input style="background-color: #fff" name="image_url" class="form-control form-control-sm" id="formFileSm" type="file">
                    @if ($category?->image_src)
                        <div class="my-2">
                            <img src="{{ $category->image_src }}" width="100%" class="img-thumbnail" alt="{{ $category->name }}">
                        </div>
                    @endif
                </div>
                <h6 class="mb-4">
                    <input type="submit" value="{{ $submitLabel }}" class="btn btn-custom">
                </h6>
            </div>
        </div>
    </form>
</div>
@endsection
