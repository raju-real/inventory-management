{{-- Shared detail page of a purchase or sale. Needs: $invoice, $party, $date, $prefix, $menuKey, $title, $partyLabel, $partyRoute, $paymentRoute, $paymentMenu, $paymentParam, $returnRoute, $returnMenu --}}
<x-page-header :title="$title . ' ' . $invoice->reference_no" :sub="$date->format('d M Y') . ' · recorded by ' . ($invoice->creator?->name ?? '-')">
    <a class="btn btn-light" href="{{ route($prefix . '.index') }}">Back</a>
    <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="{{ route($prefix . '.pdf', $invoice) }}"><i class="bi bi-printer me-1"></i>Print / PDF</a>
    @if ($prefix === 'sales')
        <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="{{ route('sales.receipt', $invoice) }}"><i class="bi bi-receipt-cutoff me-1"></i>POS Receipt</a>
        <button type="button" class="btn btn-primary" title="Print POS receipt now (F9)" aria-label="Print POS receipt now" data-direct-print="{{ route('sales.receipt', $invoice) }}" data-shortcut="F9" aria-keyshortcuts="F9"><i class="bi bi-printer-fill"></i></button>
    @endif
    <a class="btn btn-outline-primary" href="{{ route($prefix . '.pdf', [$invoice, 'download' => 1]) }}"><i class="bi bi-download me-1"></i>Download</a>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailModal"><i class="bi bi-envelope me-1"></i>Email</button>
    @unless ($invoice->isCancelled())
        @if ((float) $invoice->due_amount > 0)
            @access($paymentMenu, 'create')<a class="btn btn-primary btn-submit" href="{{ route($paymentRoute, [$paymentParam => $invoice->unique_id]) }}"><i class="bi bi-cash-coin me-1"></i>Add Payment</a>@endaccess
        @endif
        @access($returnMenu, 'create')<a class="btn btn-outline-secondary btn-submit" href="{{ route($returnRoute, ['invoice' => $invoice->reference_no]) }}"><i class="bi bi-arrow-return-left me-1"></i>Return</a>@endaccess
        @access($menuKey, 'delete')<button class="btn btn-outline-danger" data-cancel-url="{{ route($prefix . '.cancel', $invoice) }}" data-name="{{ $invoice->reference_no }}"><i class="bi bi-x-circle me-1"></i>Cancel</button>@endaccess
    @endunless
</x-page-header>

@if ($invoice->isCancelled())
    <div class="alert alert-danger">
        <strong>Cancelled</strong> on {{ $invoice->cancelled_at?->format('d M Y H:i') }} by {{ $invoice->canceller?->name }}. Reason: {{ $invoice->cancel_reason }}
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Products</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Product</th><th class="text-end">Quantity</th><th class="text-end">Returned</th><th class="text-end">Unit price</th>@if ($prefix === 'sales')<th class="text-end">VAT</th>@endif<th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($invoice->details as $detail)
                        <tr>
                            <td>{{ $detail->product?->name }}<div class="small text-muted">{{ $detail->product?->sku }}</div></td>
                            <td class="num">{{ qty($detail->quantity) }} {{ $detail->product?->unit?->short_name }}</td>
                            <td class="num">{{ qty($detail->returned_quantity) }}</td>
                            <td class="num">{{ number_format($detail->unit_price, 2) }}</td>
                            @if ($prefix === 'sales')<td class="num">{{ number_format($detail->vat_amount, 2) }}</td>@endif
                            <td class="num">{{ number_format($detail->total, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Payments</div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Reference</th><th>Date</th><th>Method</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->reference_no }}</td><td>{{ $payment->payment_date->format('d M Y') }}</td><td class="text-capitalize">{{ $payment->method }}</td>
                            <td class="num">{{ number_format($payment->amount, 2) }}</td><td><x-doc-status :status="$payment->status" /></td>
                        </tr>
                    @empty <x-empty-row :cols="5" text="No payments yet." /> @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($invoice->returns->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">Returns</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Reference</th><th>Date</th><th class="text-end">Quantity</th><th class="text-end">Value</th><th class="text-end">Refund</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($invoice->returns as $return)
                            <tr>
                                <td><a href="{{ route(str_replace('.create', '.show', $returnRoute), $return) }}">{{ $return->reference_no }}</a></td><td>{{ $return->return_date->format('d M Y') }}</td>
                                <td class="num">{{ qty($return->total_quantity) }}</td><td class="num">{{ number_format($return->total_amount, 2) }}</td><td class="num">{{ number_format($return->refund_amount, 2) }}</td>
                                <td><x-doc-status :status="$return->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header">{{ $partyLabel }}</div><div class="card-body">
            <a class="fw-semibold text-decoration-none" href="{{ route($partyRoute, $party) }}">{{ $party->name }}</a>
            <div class="small text-muted">{{ $party->mobile }}</div>
            <div class="small text-muted">{{ $party->email }}</div>
            <div class="small text-muted">{{ $party->address }}</div>
            @if ($prefix === 'sales' && ! $party->is_walk_in)
                <div class="small mt-2"><i class="bi bi-stars me-1"></i>Points balance: <b>{{ number_format($party->points) }}</b>@if ($invoice->points_earned) · earned {{ $invoice->points_earned }} on this sale @endif</div>
            @endif
        </div></div>
        <div class="card"><div class="card-header d-flex justify-content-between"><span>Summary</span>@unless ($invoice->isCancelled())<x-pay-status :status="$invoice->payment_status" />@endunless</div><div class="card-body">
            <div class="summary-row"><span>Total quantity</span><span>{{ qty($invoice->total_quantity) }}</span></div>
            <div class="summary-row"><span>Subtotal</span><span>{{ money($invoice->subtotal) }}</span></div>
            <div class="summary-row"><span>{{ $prefix === 'sales' ? $invoice->discount_label : 'Discount' }}</span><span>- {{ money($invoice->discount) }}</span></div>
            @if ($prefix === 'sales')<div class="summary-row"><span>{{ $invoice->vat_label }}</span><span>+ {{ money($invoice->vat_amount) }}</span></div>@endif
            @if ((float) ($invoice->other_charges ?? 0) > 0)<div class="summary-row"><span>Other charges</span><span>{{ money($invoice->other_charges) }}</span></div>@endif
            <div class="summary-row total"><span>Grand total</span><span>{{ money($invoice->grand_total) }}</span></div>
            @if ((float) ($invoice->points_amount ?? 0) > 0)<div class="summary-row"><span>Points used ({{ $invoice->points_redeemed }})</span><span>- {{ money($invoice->points_amount) }}</span></div>@endif
            <div class="summary-row"><span>Paid</span><span>{{ money($invoice->paid_amount) }}</span></div>
            @if ((float) $invoice->returned_amount > 0)<div class="summary-row"><span>Returned (net of refunds)</span><span>{{ money($invoice->returned_amount) }}</span></div>@endif
            <div class="summary-row fw-semibold"><span>Due</span><span class="{{ (float) $invoice->due_amount > 0 ? 'out' : '' }}">{{ money($invoice->due_amount) }}</span></div>
            @if ($invoice->notes)<hr><div class="small text-muted">{{ $invoice->notes }}</div>@endif
        </div></div>
    </div>
</div>

<div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content needs-validation" method="POST" action="{{ route($prefix . '.email', $invoice) }}" novalidate>@csrf
            <div class="modal-header"><h2 class="modal-title h5">Email invoice {{ $invoice->reference_no }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <x-input name="email" type="email" label="Send to" placeholder="name@example.com" :value="$party->email" required maxlength="255" feedback="Please enter a valid email address." />
                <x-textarea name="message" label="Message" placeholder="Optional message to include in the email" rows="3" maxlength="1000" wrapper="mb-0" />
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary btn-submit"><i class="bi bi-send me-1"></i>Send invoice</button></div>
        </form>
    </div>
</div>
