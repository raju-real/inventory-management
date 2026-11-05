@props(['name' => 'image', 'label' => 'Image', 'current' => null, 'removable' => true, 'accept' => '.jpg,.jpeg,.png,.webp', 'maxKb' => 2048, 'help' => 'Optional. JPG, PNG or WEBP, up to 2 MB.'])
<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    <div class="d-flex align-items-center gap-3">
        <img id="{{ $name }}-preview" @if ($current) src="{{ $current }}" @endif alt="" class="thumb {{ $current ? '' : 'd-none' }}" style="width:64px;height:64px">
        <div class="flex-grow-1">
            <input type="file" id="{{ $name }}" name="{{ $name }}" accept="{{ $accept }}" data-max-kb="{{ $maxKb }}" data-preview="{{ $name }}-preview" placeholder="Choose {{ strtolower($label) }}"
                   class="form-control @error($name) is-invalid @enderror">
            <div class="form-text">{{ $help }}</div>
            @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @if ($current && $removable)
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" id="remove_{{ $name }}" name="remove_{{ $name }}">
                    <label class="form-check-label small" for="remove_{{ $name }}">Remove current {{ strtolower($label) }}</label>
                </div>
            @endif
        </div>
    </div>
</div>
