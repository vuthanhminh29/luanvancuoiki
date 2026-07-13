@php
    $layout = $layout ?? 'classic';
    $allFields = collect($fieldsToRender);
    $main = $allFields->reject(fn ($field) => $field['side'] ?? false);
    $side = $allFields->filter(fn ($field) => $field['side'] ?? false);
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if (in_array($layout, ['pa', 'ma'], true))
    <div class="{{ $layout === 'pa' ? 'pa-layout' : 'ma-layout' }}">
        <div class="{{ $layout === 'pa' ? 'pa-card' : 'ma-card' }}">
            <div class="{{ $layout === 'pa' ? 'pa-form-grid' : 'ma-form-grid' }}">
                @foreach ($main as $field)
                    @include('admin.shared.form-field', ['field' => $field, 'layout' => $layout])
                @endforeach
            </div>
        </div>
        @if ($side->count())
            <div class="{{ $layout === 'pa' ? 'pa-card' : 'ma-card' }}">
                @foreach ($side as $field)
                    @include('admin.shared.form-field', ['field' => $field, 'layout' => $layout])
                @endforeach
            </div>
        @endif
    </div>
@elseif ($layout === 'wa')
    <div class="wa-form-grid">
        @foreach ($allFields as $field)
            @include('admin.shared.form-field', ['field' => $field, 'layout' => $layout])
        @endforeach
    </div>
@else
    @foreach ($allFields as $field)
        @include('admin.shared.form-field', ['field' => $field, 'layout' => $layout])
    @endforeach
@endif
