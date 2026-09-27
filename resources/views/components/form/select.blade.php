@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'empty' => null, 'searchable' => false])
{{--
    CActiveForm dropDownList(). `empty` adds a first blank option with that
    text; `searchable` turns it into a type-to-search box (replaces select2).
    An option may be ['label' => ..., 'chain' => ..., 'style' => ...] (chain
    is matched by data-chained selects; a 'value' key overrides the array
    key, for lists where values repeat), and a list of options under a
    string key becomes an <optgroup> with that label.
--}}
@php
    $selected = (string) old($name, $value);
    $renderOption = function ($optionValue, $option) use ($selected) {
        $optionValue = is_array($option) && array_key_exists('value', $option) ? $option['value'] : $optionValue;
        $text = is_array($option) ? $option['label'] : $option;
        $chain = is_array($option) && isset($option['chain']) ? ' data-chain="'.e($option['chain']).'"' : '';
        $style = is_array($option) && isset($option['style']) ? ' style="'.e($option['style']).'"' : '';

        return '<option value="'.e($optionValue).'"'.$chain.$style.($selected === (string) $optionValue ? ' selected' : '').'>'.e($text).'</option>';
    };
@endphp
<div class="mb-3">
    @if ($label !== '')
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="required text-danger">*</span>
            @endif
        </label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'js-searchable' => $searchable, 'is-invalid' => $errors->has($name)]) }}>
        @if ($empty !== null)
            <option value="">{{ $empty }}</option>
        @endif
        @foreach ($options as $optionValue => $option)
            @if (is_array($option) && ! isset($option['label']))
                <optgroup label="{{ $optionValue }}">
                    @foreach ($option as $groupValue => $groupOption)
                        {!! $renderOption($groupValue, $groupOption) !!}
                    @endforeach
                </optgroup>
            @else
                {!! $renderOption($optionValue, $option) !!}
            @endif
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
