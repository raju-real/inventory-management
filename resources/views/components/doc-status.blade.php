@props(['status' => 'posted'])
<span class="badge {{ $status === 'posted' ? 'tone-green' : 'tone-red' }}">{{ ucfirst($status) }}</span>
