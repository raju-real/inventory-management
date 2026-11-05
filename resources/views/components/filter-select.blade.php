@props(['name', 'label', 'options' => [], 'placeholder' => null])
<div class="col-md-2">
    <label class="form-label small" for="{{ $name }}">{{ $label }}</label>
    <select class="form-select" id="{{ $name }}" name="{{ $name }}">
        <option value="">{{ $placeholder ?? 'All' }}</option>
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected((string) request($name) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
</div>
