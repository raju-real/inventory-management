@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'placeholder' => null, 'help' => null, 'wrapper' => 'mb-3', 'feedback' => null])
@php
    $id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name));
    $field = str_replace(['[', ']'], ['.', ''], $name);
    $current = in_array($type, ['password', 'file'], true) ? null : old($field, $value);
    $hint = $placeholder ?? ($label ? 'Enter ' . strtolower($label) : '');
    $isDate = $type === 'date';
@endphp
<div class="{{ $wrapper }}">
    @if ($label)
        <label class="form-label" for="{{ $id }}">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
    @endif
    <input type="{{ $isDate ? 'text' : $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $current }}" placeholder="{{ $hint }}"
           @if ($isDate) data-datepicker autocomplete="off" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" @if ($attributes->has('min')) data-date-start-date="{{ $attributes->get('min') }}" @endif @endif
           {{ $attributes->except($isDate ? ['id', 'min'] : ['id'])->class(['form-control', 'date-input' => $isDate, 'is-invalid' => $errors->has($field)]) }} @required($required)>
    @error($field)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        <div class="invalid-feedback">{{ $feedback ?? 'Please enter a valid ' . strtolower($label ?? 'value') . '.' }}</div>
    @enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
