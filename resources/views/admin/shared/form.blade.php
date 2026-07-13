@extends('admin.layouts.app')

@section('title', $title)

@php
    $style = $formStyle ?? 'classic';
    $mainFields = collect($fields)->reject(fn ($field) => $field['side'] ?? false);
    $sideFields = collect($fields)->filter(fn ($field) => $field['side'] ?? false);

    $inputClass = match ($style) {
        'pa' => 'pa-input',
        'ma' => 'ma-input',
        'wa' => 'wa-input',
        'banner' => 'form-input',
        default => 'form-control',
    };
    $selectClass = match ($style) {
        'pa' => 'pa-select',
        'ma' => 'ma-select',
        'wa' => 'wa-select',
        'banner' => 'form-select',
        default => 'form-select',
    };
    $textareaClass = match ($style) {
        'pa' => 'pa-textarea',
        'ma' => 'ma-textarea',
        'wa' => 'wa-input',
        'banner' => 'form-input',
        default => 'form-control',
    };
    $fileClass = match ($style) {
        'pa' => 'pa-file',
        'banner' => 'form-file',
        default => 'form-control form-control-sm',
    };
@endphp

@once
    @push('styles')
    <style>
        .pa-page,.ma-page,.wa-page{background:#f4f7fb;min-height:calc(100vh - 72px);padding:24px}
        .pa-head,.ma-head,.wa-toolbar{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
        .pa-kicker,.ma-kicker{color:#0f766e;font-size:13px;font-weight:800;letter-spacing:.04em;margin-bottom:6px;text-transform:uppercase}
        .pa-title,.ma-title,.wa-title h4{color:#101828;font-size:26px;font-weight:900;line-height:1.2;margin:0}
        .pa-subtitle,.ma-subtitle,.wa-title p,.ma-hint{color:#667085;font-size:14px;margin:8px 0 0}
        .pa-actions,.ma-actions,.wa-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end}
        .pa-btn,.ma-btn,.wa-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:8px;color:#344054;display:inline-flex;font-size:14px;font-weight:800;gap:8px;min-height:40px;padding:0 14px;text-decoration:none;cursor:pointer}
        .pa-btn.primary,.ma-btn.primary,.wa-btn.primary{background:#0f766e;border-color:#0f766e;color:#fff}
        .pa-card,.ma-card,.wa-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);padding:18px}
        .wa-card{padding:0}.wa-form{padding:18px}.wa-card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e5e7eb}.wa-card-head h6{margin:0;font-weight:900}
        .pa-layout,.ma-layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px}.pa-form-grid,.ma-form-grid,.wa-form-grid{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
        .wa-form-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
        .pa-field,.ma-field,.wa-field,.form-group{margin-bottom:14px}
        .pa-label,.ma-label,.wa-field label,.form-label{color:#344054;display:block;font-size:13px;font-weight:800;margin-bottom:7px}
        .pa-input,.pa-select,.pa-textarea,.pa-file,.ma-input,.ma-select,.ma-textarea,.wa-input,.wa-select,.form-input,.form-select,.form-file{background:#fff;border:1px solid #d0d5dd;border-radius:8px;color:#101828;font-size:14px;min-height:42px;padding:9px 12px;width:100%}
        .pa-textarea,.ma-textarea{min-height:130px;resize:vertical}
        .ma-textarea{min-height:110px}.pa-file,.form-file{padding:8px}
        .pa-input:focus,.pa-select:focus,.pa-textarea:focus,.ma-input:focus,.ma-select:focus,.ma-textarea:focus,.wa-input:focus,.wa-select:focus,.form-input:focus,.form-select:focus{border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.12);outline:none}
        .pa-error,.ma-error,.error-text{color:#dc2626;display:block;font-size:13px;margin-top:5px}
        .product-container{padding-top:1.5rem}.product-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.5rem}.product-card{background:white;border-radius:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.1);padding:1.5rem;border:1px solid #eee}.product-title{font-size:1.125rem;font-weight:700;color:#111827;margin-bottom:1.5rem}.product-title a{color:#3b82f6;text-decoration:none}.btn-submit{display:inline-block;padding:.625rem 1.5rem;font-size:.875rem;font-weight:700;color:white;background-color:#3b82f6;border:none;border-radius:.375rem;cursor:pointer}
        .btn-submit:hover{background-color:#2563eb}
        @media(max-width:900px){.pa-page,.ma-page,.wa-page{padding:16px}.pa-head,.ma-head,.wa-toolbar{display:block}.pa-actions,.ma-actions,.wa-actions{justify-content:flex-start;margin-top:14px}.pa-layout,.ma-layout,.product-grid{grid-template-columns:1fr}.pa-form-grid,.ma-form-grid,.wa-form-grid{grid-template-columns:1fr}}
    </style>
    @endpush
@endonce

@section('content')
@if ($style === 'pa')
    <div class="pa-page">
        <form method="post" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @isset($method) @method($method) @endisset
            <div class="pa-head">
                <div>
                    <div class="pa-kicker">Quản trị sản phẩm</div>
                    <h1 class="pa-title">{{ $title }}</h1>
                    @isset($subtitle)<p class="pa-subtitle">{{ $subtitle }}</p>@endisset
                </div>
                <div class="pa-actions">
                    @isset($backRoute)<a class="pa-btn" href="{{ $backRoute }}"><i class="fas fa-arrow-left"></i> Quay lại</a>@endisset
                    <button class="pa-btn primary" type="submit"><i class="fas fa-save"></i> {{ $submitLabel ?? 'Lưu' }}</button>
                </div>
            </div>
            @include('admin.shared.form-fields', ['fieldsToRender' => $fields, 'layout' => 'pa'])
        </form>
    </div>
@elseif ($style === 'ma')
    <div class="ma-page">
        <form method="post" action="{{ $action }}">
            @csrf
            @isset($method) @method($method) @endisset
            <div class="ma-head">
                <div>
                    <div class="ma-kicker">Quản trị thành viên</div>
                    <h1 class="ma-title">{{ $title }}</h1>
                    @isset($subtitle)<p class="ma-subtitle">{{ $subtitle }}</p>@endisset
                </div>
                <div class="ma-actions">
                    @isset($backRoute)<a class="ma-btn" href="{{ $backRoute }}"><i class="fas fa-arrow-left"></i> Quay lại</a>@endisset
                    <button class="ma-btn primary" type="submit"><i class="fas fa-save"></i> {{ $submitLabel ?? 'Lưu tài khoản' }}</button>
                </div>
            </div>
            @include('admin.shared.form-fields', ['fieldsToRender' => $fields, 'layout' => 'ma'])
        </form>
    </div>
@elseif ($style === 'wa')
    <div class="wa-page">
        <div class="wa-toolbar">
            <div class="wa-title">
                <h4>{{ $title }}</h4>
                @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
            </div>
            <div class="wa-actions">@isset($backRoute)<a class="wa-btn" href="{{ $backRoute }}"><i class="fa fa-arrow-left"></i> Quay lại kho</a>@endisset</div>
        </div>
        <form method="post" action="{{ $action }}" class="wa-card">
            @csrf
            <div class="wa-card-head">
                <h6>Thông tin phiếu</h6>
                <button class="wa-btn primary" type="submit"><i class="fa fa-save"></i> {{ $submitLabel ?? 'Lưu phiếu kho' }}</button>
            </div>
            <div class="wa-form">@include('admin.shared.form-fields', ['fieldsToRender' => $fields, 'layout' => 'wa'])</div>
        </form>
    </div>
@elseif ($style === 'banner')
    <div class="container-fluid product-container">
        <form class="product-grid" method="post" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @isset($method) @method($method) @endisset
            <div class="product-card">
                <h6 class="product-title">@isset($backRoute)<a href="{{ $backRoute }}">Banner</a> / @endisset{{ $title }}</h6>
                @include('admin.shared.form-fields', ['fieldsToRender' => $mainFields, 'layout' => 'banner'])
            </div>
            <div class="product-card">
                @include('admin.shared.form-fields', ['fieldsToRender' => $sideFields, 'layout' => 'banner'])
                <button class="btn-submit" type="submit">{{ $submitLabel ?? 'Đăng Banner' }}</button>
            </div>
        </form>
    </div>
@else
    <div class="container-fluid pt-4" style="margin-bottom:110px;">
        <form class="row g-4" method="post" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @isset($method) @method($method) @endisset
            <div class="col-sm-12 col-xl-9">
                <div class="bg-light rounded h-100 p-4">
                    <h6 class="mb-4">@isset($backRoute)<a href="{{ $backRoute }}" class="link-not-hover">{{ $sectionTitle ?? $title }}</a> / @endisset{{ $title }}</h6>
                    @include('admin.shared.form-fields', ['fieldsToRender' => $mainFields, 'layout' => 'classic'])
                </div>
            </div>
            <div class="col-sm-12 col-xl-3">
                <div class="bg-light rounded h-100 p-4">
                    @include('admin.shared.form-fields', ['fieldsToRender' => $sideFields, 'layout' => 'classic'])
                    <h6 class="mb-4"><button class="btn btn-custom btn-primary" type="submit">{{ $submitLabel ?? 'Đăng' }}</button></h6>
                </div>
            </div>
        </form>
    </div>
@endif
@endsection
