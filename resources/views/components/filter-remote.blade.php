{{-- A filter dropdown that searches as you type (Select2 + /lookup). type: customers, suppliers or products. --}}
@props(['name', 'label', 'type', 'placeholder' => null, 'required' => false, 'col' => 'col-md-3'])
@php
    $selected = \App\Http\Controllers\LookupController::selected($type, request($name));
    $placeholder = $placeholder ?? ($required ? 'Select ' . strtolower($label) : 'All');
@endphp
<div class="{{ $col }}">
    <label class="form-label small" for="{{ $name }}">{{ $label }}@if ($required) <span class="req">*</span>@endif</label>
    <select class="form-select js-remote-select" id="{{ $name }}" name="{{ $name }}" data-url="{{ route('lookup', $type) }}"
            data-placeholder="{{ $placeholder }}" data-search-hint="{{ $type === 'products' ? 'Type name, code or barcode' : 'Type name or mobile' }}" @required($required)>
        <option value="">{{ $placeholder }}</option>
        @if ($selected)
            <option value="{{ $selected['id'] }}" selected>{{ $selected['text'] }}</option>
        @endif
    </select>
</div>
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/select2/select2-bootstrap-5-theme.min.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/vendor/select2/select2.min.js') }}"></script>
        <script src="{{ asset('assets/js/remote-select.js') }}"></script>
    @endpush
@endonce
