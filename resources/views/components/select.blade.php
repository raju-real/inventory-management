@props(['name', 'label' => null, 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null, 'help' => null, 'wrapper' => 'mb-3'])
@php
    $current = old($name, $value);
    $current = is_bool($current) ? (int) $current : $current;
@endphp
<div class="{{ $wrapper }}">
    @if ($label)
        <div class="d-flex justify-content-between align-items-center">
            <label class="form-label" for="{{ $name }}">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
            {{ $labelAction ?? '' }}
        </div>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }} @required($required)>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder ?? 'Select ' . strtolower($label ?? 'an option') }}</option>
        @endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected($current !== null && (string) $current === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        <div class="invalid-feedback">Please select {{ strtolower($label ?? 'an option') }}.</div>
    @enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
