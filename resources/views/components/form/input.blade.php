@props(['name', 'label', 'value' => null, 'type' => 'text', 'required' => false])
{{-- One labelled field: CActiveForm labelEx() + textField() + error() --}}
<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="required text-danger">*</span>
        @endif
    </label>
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
