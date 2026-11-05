@php
    $active = request()->routeIs($item->route)
        || (str_ends_with($item->route, '.index') && $item->key !== 'stock' && request()->routeIs(str_replace('.index', '.*', $item->route)));
@endphp
<a class="side-link {{ $active ? 'active' : '' }}" href="{{ route($item->route) }}">
    <i class="bi {{ $item->icon }}"></i><span>{{ $item->name }}</span>
</a>
