@extends('layouts.app')
@section('title', $damage->reference_no)
@section('content')
<x-page-header :title="'Damage ' . $damage->reference_no" :sub="$damage->damage_date->format('d M Y') . ' · recorded by ' . ($damage->creator?->name ?? '-')">
    <a class="btn btn-light" href="{{ route('damages.index') }}">Back</a>
    @unless ($damage->isCancelled())
        @access('damages', 'delete')<button class="btn btn-outline-danger" data-cancel-url="{{ route('damages.cancel', $damage) }}" data-name="{{ $damage->reference_no }}"><i class="bi bi-x-circle me-1"></i>Cancel</button>@endaccess
    @endunless
</x-page-header>
@if ($damage->isCancelled())
    <div class="alert alert-danger"><strong>Cancelled</strong> on {{ $damage->cancelled_at?->format('d M Y H:i') }} by {{ $damage->canceller?->name }}. Reason: {{ $damage->cancel_reason }}</div>
@endif
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Product</th><th class="text-end">Quantity</th><th>Reason</th></tr></thead>
            <tbody>
            @foreach ($damage->details as $detail)
                <tr><td>{{ $detail->product?->name }}<div class="small text-muted">{{ $detail->product?->sku }}</div></td><td class="num">{{ qty($detail->quantity) }} {{ $detail->product?->unit?->short_name }}</td><td>{{ $detail->reason }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if ($damage->notes)<div class="card-body border-top small text-muted">{{ $damage->notes }}</div>@endif
</div>
@endsection
