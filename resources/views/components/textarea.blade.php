@props(['name', 'label' => null, 'value' => null, 'required' => false, 'placeholder' => null, 'rows' => 3, 'wrapper' => 'mb-3'])
<div class="{{ $wrapper }}">
    @if ($label)
        <label class="form-label" for="{{ $name }}">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder ?? 'Enter ' . strtolower($label ?? 'text') }}"
              {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }} @required($required)>{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        <div class="invalid-feedback">Please enter a valid {{ strtolower($label ?? 'value') }}.</div>
    @enderror
</div>
