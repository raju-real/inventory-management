@props(['status' => 'Due'])
<span class="badge {{ ['Paid' => 'tone-green', 'Partial' => 'tone-amber', 'Due' => 'tone-red'][$status] ?? 'tone-blue' }}">{{ $status }}</span>
