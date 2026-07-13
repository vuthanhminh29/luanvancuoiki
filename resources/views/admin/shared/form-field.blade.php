@php
    $layout = $layout ?? 'classic';
    $classes = match ($layout) {
        'pa' => ['field' => 'pa-field', 'label' => 'pa-label', 'input' => 'pa-input', 'select' => 'pa-select', 'textarea' => 'pa-textarea', 'file' => 'pa-file', 'error' => 'pa-error'],
        'ma' => ['field' => 'ma-field', 'label' => 'ma-label', 'input' => 'ma-input', 'select' => 'ma-select', 'textarea' => 'ma-textarea', 'file' => 'ma-input', 'error' => 'ma-error'],
        'wa' => ['field' => 'wa-field', 'label' => '', 'input' => 'wa-input', 'select' => 'wa-select', 'textarea' => 'wa-input', 'file' => 'wa-input', 'error' => 'error-text'],
        'banner' => ['field' => 'form-group', 'label' => 'form-label', 'input' => 'form-input', 'select' => 'form-select', 'textarea' => 'form-input', 'file' => 'form-file', 'error' => 'error-text'],
        default => ['field' => 'mb-3', 'label' => 'form-label', 'input' => 'form-control', 'select' => 'form-select', 'textarea' => 'form-control', 'file' => 'form-control form-control-sm', 'error' => 'text-danger'],
    };
    $name = $field['name'];
    $label = $field['label'];
    $type = $field['type'] ?? 'text';
    $id = $field['id'] ?? $name;
    $value = old($name, $field['value'] ?? '');
@endphp

<div class="{{ $classes['field'] }}">
    <label class="{{ $classes['label'] }}" for="{{ $id }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" class="{{ $classes['textarea'] }} @error($name) is-invalid @enderror" rows="{{ $field['rows'] ?? 4 }}">{{ $value }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="{{ $classes['select'] }} @error($name) is-invalid @enderror" @if(!empty($field['required'])) required @endif>
            @if (!empty($field['placeholder']))
                <option value="">{{ $field['placeholder'] }}</option>
            @endif
            @foreach (($field['options'] ?? []) as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'file')
        <input id="{{ $id }}" name="{{ $name }}" class="{{ $classes['file'] }} @error($name) is-invalid @enderror" type="file" accept="{{ $field['accept'] ?? 'image/*' }}">
        @if (!empty($field['preview']))
            <div class="mt-2">
                <img src="{{ $field['preview'] }}" alt="{{ $label }}" style="width:100%;max-width:180px;border-radius:8px;border:1px solid #ddd;object-fit:cover;">
            </div>
        @endif
    @else
        <input id="{{ $id }}" name="{{ $name }}" class="{{ $classes['input'] }} @error($name) is-invalid @enderror" type="{{ $type }}" value="{{ $value }}" @if(!empty($field['required'])) required @endif>
    @endif
    @error($name)
        <span class="{{ $classes['error'] }}">{{ $message }}</span>
    @enderror
    @if (!empty($field['hint']))
        <div class="ma-hint">{{ $field['hint'] }}</div>
    @endif
</div>
