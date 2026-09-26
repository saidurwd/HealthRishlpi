@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'empty' => null, 'searchable' => false])
{{--
    CActiveForm dropDownList(). `empty` adds a first blank option with that
    text; `searchable` turns it into a type-to-search box (replaces select2).
--}}
@php $selected = (string) old($name, $value); @endphp
<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="required text-danger">*</span>
        @endif
    </label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'js-searchable' => $searchable, 'is-invalid' => $errors->has($name)]) }}>
        @if ($empty !== null)
            <option value="">{{ $empty }}</option>
        @endif
        @foreach ($options as $optionValue => $optionText)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionText }}</option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
