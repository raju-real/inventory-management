{{-- Shared detail of a return. Needs: $return, $title, $prefix, $menuKey, $party, $partyLabel, $invoice, $invoiceRoute, $withCondition --}}
<x-page-header :title="$title . ' ' . $return->reference_no" :sub="$return->return_date->format('d M Y') . ' · recorded by ' . ($return->creator?->name ?? '-')">
    <a class="btn btn-light" href="{{ route($prefix . '.index') }}">Back</a>
    @unless ($return->isCancelled())
        @access($menuKey, 'delete')<button class="btn btn-outline-danger" data-cancel-url="{{ route($prefix . '.cancel', $return) }}" data-name="{{ $return->reference_no }}"><i class="bi bi-x-circle me-1"></i>Cancel</button>@endaccess
    @endunless
</x-page-header>
@if ($return->isCancelled())
    <div class="alert alert-danger"><strong>Cancelled</strong> on {{ $return->cancelled_at?->format('d M Y H:i') }} by {{ $return->canceller?->name }}. Reason: {{ $return->cancel_reason }}</div>
@endif
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Returned products</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Product</th>@if ($withCondition)<th>Condition</th>@endif<th class="text-end">Quantity</th><th class="text-end">Unit value</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($return->details as $detail)
                        <tr>
                            <td>{{ $detail->product?->name }}<div class="small text-muted">{{ $detail->product?->sku }}</div></td>
                            @if ($withCondition)<td><span class="badge {{ $detail->condition === 'good' ? 'tone-green' : 'tone-red' }}">{{ ucfirst($detail->condition) }}</span></td>@endif
                            <td class="num">{{ qty($detail->quantity) }}</td>
                            <td class="num">{{ number_format($detail->unit_price, 2) }}</td>
                            <td class="num">{{ number_format($detail->total, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Details</div><div class="card-body">
            <div class="summary-row"><span>{{ $partyLabel }}</span><span>{{ $party?->name }}</span></div>
            <div class="summary-row"><span>Original invoice</span><a href="{{ route($invoiceRoute, $invoice) }}">{{ $invoice?->reference_no }}</a></div>
            <div class="summary-row"><span>Reason</span><span class="text-end">{{ $return->reason }}</span></div>
            <div class="summary-row"><span>Total quantity</span><span>{{ qty($return->total_quantity) }}</span></div>
            <div class="summary-row total"><span>Return value</span><span>{{ money($return->total_amount) }}</span></div>
            <div class="summary-row"><span>Refund</span><span>{{ money($return->refund_amount) }}</span></div>
            @if ($return->notes)<hr><div class="small text-muted">{{ $return->notes }}</div>@endif
        </div></div>
    </div>
</div>
