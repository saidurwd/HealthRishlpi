@props(['name', 'label', 'value' => null, 'required' => false])
<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="required text-danger">*</span>
        @endif
    </label>
    <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['rows' => 3])->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
