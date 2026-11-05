@props(['active' => true])
<span class="badge {{ $active ? 'tone-green' : 'tone-red' }}">{{ $active ? 'Active' : 'Inactive' }}</span>
