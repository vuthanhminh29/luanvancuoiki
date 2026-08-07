@extends('admin.layouts.app')

@section('title', $title)

@section('content')
<div class="container-fluid pt-4" style="margin-bottom: 110px;">
    <form class="row g-4" action="{{ $action }}" method="post">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <div class="col-sm-12 col-xl-9">
            <div class="bg-light rounded h-100 p-4">
                <h6 class="mb-4">
                    <a href="{{ $backRoute }}" class="link-not-hover">Tròng kính</a>
                    / {{ $title }}
                </h6>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="row">
                    <div class="col-sm-6">
                        <label for="floatingCode">Mã (viết hoa, không dấu, VD: CHONG_UV)</label>
                        <div class="form-floating mb-4">
                            <input name="code" type="text" value="{{ old('code', $lensOption?->code) }}" class="form-control text-uppercase" id="floatingCode" placeholder="CHONG_UV">
                            @error('code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="floatingIcon">Icon (class Font Awesome, VD: fa-sun)</label>
                        <div class="form-floating mb-4">
                            <input name="icon" type="text" value="{{ old('icon', $lensOption?->icon ?? 'fa-circle') }}" class="form-control" id="floatingIcon" placeholder="fa-sun">
                            @error('icon')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <label for="floatingName">Tên tròng kính</label>
                <div class="form-floating mb-4">
                    <input name="name" type="text" value="{{ old('name', $lensOption?->name) }}" class="form-control" id="floatingName">
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <label for="floatingDescription">Mô tả</label>
                <div class="form-floating mb-4">
                    <textarea name="description" class="form-control" id="floatingDescription" style="height: 100px">{{ old('description', $lensOption?->description) }}</textarea>
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <label for="floatingPrice">Giá (đ)</label>
                        <div class="form-floating mb-4">
                            <input name="price" type="number" step="1000" min="0" value="{{ old('price', $lensOption?->price) }}" class="form-control" id="floatingPrice">
                            @error('price')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="floatingSortOrder">Thứ tự hiển thị (số nhỏ lên trước)</label>
                        <div class="form-floating mb-4">
                            <input name="sort_order" type="number" value="{{ old('sort_order', $lensOption?->sort_order ?? 0) }}" class="form-control" id="floatingSortOrder">
                            @error('sort_order')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <label class="d-block mb-2">Nhóm hiển thị (tab trên trang tư vấn)</label>
                @php($selectedGroups = old('groups', $lensOption?->groups ?? []))
                <div class="mb-4 d-flex flex-wrap gap-3">
                    @foreach ($groupLabels as $groupKey => $groupLabel)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="groups[]" value="{{ $groupKey }}" id="group-{{ $groupKey }}" @checked(in_array($groupKey, $selectedGroups, true))>
                            <label class="form-check-label" for="group-{{ $groupKey }}">{{ $groupLabel }}</label>
                        </div>
                    @endforeach
                </div>
                @error('groups')
                    <span class="text-danger d-block mb-3">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="col-sm-12 col-xl-3">
            <div class="bg-light rounded h-100 p-4">
                <label for="floatingSelect">Trạng thái</label>
                <div class="form-floating mb-4">
                    @php($currentStatus = old('status', $lensOption?->status ?? 'ACTIVE'))
                    <select name="status" class="form-select" id="floatingSelect">
                        <option value="ACTIVE" @selected($currentStatus === 'ACTIVE')>Hiển thị</option>
                        <option value="INACTIVE" @selected($currentStatus === 'INACTIVE')>Tạm ẩn</option>
                    </select>
                    @error('status')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <h6 class="mb-4">
                    <input type="submit" value="{{ $submitLabel }}" class="btn btn-custom">
                </h6>
            </div>
        </div>
    </form>
</div>
@endsection
