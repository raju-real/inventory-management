@props(['title', 'sub' => null])
<div class="page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if ($sub)<div class="sub">{{ $sub }}</div>@endif
    </div>
    <div class="d-flex flex-wrap gap-2 no-print">{{ $slot }}</div>
</div>
